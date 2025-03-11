<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\MadkrapowOrder;
use App\Models\MadkrapowOrderItem;
use App\Models\MadkrapowShipping;
use App\Models\MadkrapowPayment;
use App\Models\MadkrapowCartItem;
use App\Models\MadkrapowProduct;
use App\Services\BillplzService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    protected $standardShippingFee = 10.00;
    protected $expressShippingFee = 20.00;
    protected $freeShippingThreshold = 100.00;
    protected $billplzService;

    public function __construct(BillplzService $billplzService)
    {
        $this->billplzService = $billplzService;
    }

    public function index()
    {
        $cartItems = MadkrapowCartItem::where('user_id', Auth::id())
            ->with('product')
            ->get();
    
        $subtotal = $cartItems->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });
    
        $standardShippingFee = $this->standardShippingFee;
        $expressShippingFee = $this->expressShippingFee;
        
        // Determine shipping cost based on free shipping threshold
        $shippingCost = ($subtotal >= $this->freeShippingThreshold) ? 0 : $standardShippingFee;
        $totalAmount = $subtotal + $shippingCost;
        
        // Add shippingFee to the view data
        return view('checkout.index', compact('subtotal', 'standardShippingFee', 'expressShippingFee'));
    }

    public function process(Request $request)
    {
        $cartItems = MadkrapowCartItem::where('user_id', Auth::id())
            ->with('product')
            ->get();

        $subtotal = $cartItems->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });

        $shippingCost = $request->shipping_method === 'express' ? $this->expressShippingFee : $this->standardShippingFee;

        // Apply free shipping if over threshold
        if ($request->shipping_method === 'standard' && $subtotal >= $this->freeShippingThreshold) {
            $shippingCost = 0;
        }

        $totalAmount = $subtotal + $shippingCost;

        DB::beginTransaction();

        try {
            // Create order
            $order = new MadkrapowOrder();
            $order->user_id = Auth::id();
            $order->order_date = Carbon::now();
            $order->total_amount = $totalAmount;
            $order->status = 'pending';
            $order->order_number = 'MK' . time() . mt_rand(1000, 9999); // Generate unique order number
            
            // Store calculated shipping cost
            $order->shipping_cost = $shippingCost;
            if (!$order->save()) {
                throw new \Exception('Order not saved successfully. Validation: ' . json_encode($order->getErrors()));
            }

            Log::info('Order created with ID: ' . $order->order_id);

            // Create shipping record with address details
            $shipping = new MadkrapowShipping();
            $shipping->order_id = $order->order_id;
            
            // Check if using profile address
            if ($request->has('use_profile_address') && $request->use_profile_address == 1) {
                $user = Auth::user();
                // Split the address into components or use appropriate fields from user profile
                $addressParts = explode("\n", $user->address);
                $shipping->address_line1 = $addressParts[0] ?? '';
                $shipping->address_line2 = isset($addressParts[1]) ? $addressParts[1] : '';
                // You may need to adjust these based on how user address is stored
                $shipping->city = $user->city ?? '';
                $shipping->state = $user->state ?? '';
                $shipping->postal_code = $user->postal_code ?? '';
                $shipping->country = 'Malaysia'; // Default country
            } else {
                // Use form submitted address
                $shipping->address_line1 = $request->input('address');
                $shipping->address_line2 = ''; // Optional field
                $shipping->city = $request->input('city');
                $shipping->state = $request->input('state');
                $shipping->postal_code = $request->input('postal_code');
                $shipping->country = 'Malaysia'; // Default country
            }
            
            // Add delivery method
            $shipping->delivery_method = $request->shipping_method;
            
            if (!$shipping->save()) {
                throw new \Exception('Shipping info not saved: ' . json_encode($shipping->getErrors()));
            }

            // Create payment record for Billplz (pending)
            $payment = new MadkrapowPayment();
            $payment->order_id = $order->order_id;
            $payment->payment_date = null; // Will be set when payment is confirmed
            $payment->amount = $totalAmount;
            $payment->payment_method = 'billplz';
            $payment->status = 'pending';
            $payment->save();

            // Create order items and update product stock atomically
            foreach ($cartItems as $item) {
                $orderItem = new MadkrapowOrderItem();
                $orderItem->order_id = $order->order_id;
                $orderItem->product_id = $item->product_id;
                $orderItem->quantity = $item->quantity;
                $orderItem->price_at_purchase = $item->product->price;
                $orderItem->save();

                // Atomic stock decrement
                $product = MadkrapowProduct::where('product_id', $item->product_id)->first();
                if ($product) {
                    $product->decrement('stock_quantity', $item->quantity);
                }
            }

            // Clear the user's cart
            MadkrapowCartItem::where('user_id', Auth::id())->delete();

            // Commit the transaction
            DB::commit();

            // Store order ID in session for callback reference
            session(['billplz_order_id' => $order->order_number]);
            session(['billplz_pending_order_id' => $order->getKey()]); // Use getKey() to get the primary key value
            
            // Debug order values
            Log::info('Order before redirect', [
                'primary_key' => $order->getKey(), // This will get the value of the primary key (order_id)
                'order_number' => $order->order_number ?? 'N/A'
            ]);
            
            // Use the primary key for redirection
            $primaryKey = $order->getKey();
            
            if (empty($primaryKey)) {
                throw new \Exception('Order primary key is null - cannot proceed with payment');
            }
            
            // Redirect using the query parameter with the primary key
            return redirect("/payments/billplz/initiate?orderId={$primaryKey}");
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Checkout error: ' . $e->getMessage());
            return redirect()->route('checkout.index')->with('error', 'Checkout failed: ' . $e->getMessage());
        }
    }

    public function confirmation($id)
    {
        // Try to find order by id first, then by order_id if that fails
        $order = MadkrapowOrder::with(['user', 'orderItems.product', 'shipping', 'payment'])
            ->where(function($query) use ($id) {
                $query->where('id', $id)
                    ->orWhere('order_id', $id);
            })
            ->where('user_id', Auth::id())
            ->firstOrFail();
    
        // Use stored shipping cost instead of recalculating
        return view('checkout.confirmation', [
            'order' => $order,
            'shippingCost' => $order->shipping_cost
        ]);
    }
}
