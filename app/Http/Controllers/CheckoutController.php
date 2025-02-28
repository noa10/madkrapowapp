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
use Carbon\Carbon;

class CheckoutController extends Controller
{
    protected $standardShippingFee = 10.00;
    protected $expressShippingFee = 20.00;
    protected $freeShippingThreshold = 100.00;

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
        
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
        $paymentIntent = \Stripe\PaymentIntent::create([
            'amount' => $totalAmount * 100, // amount in cents
            'currency' => 'myr',
            'payment_method_types' => ['card'],
            // Remove the return_url since we're not confirming the payment yet
        ]);
        $clientSecret = $paymentIntent->client_secret;
        
        // Add shippingFee to the view data
        return view('checkout.index', compact('subtotal', 'standardShippingFee', 'expressShippingFee', 'clientSecret'));
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

        $totalAmount = $subtotal + $shippingCost;

        DB::beginTransaction();

        try {
            // Create order
            $order = new MadkrapowOrder();
            $order->user_id = Auth::id();
            $order->order_date = Carbon::now();
            $order->total_amount = $totalAmount;
            $order->status = 'pending';
            
            // Store calculated shipping cost
            $order->shipping_cost = $shippingCost;
            if (!$order->save()) {
                throw new \Exception('Order not saved successfully. Validation: ' . json_encode($order->getErrors()));
            }

            \Log::info('Order created with ID: ' . $order->order_id); // Changed from $order->id to $order->order_id

            // Create shipping record with address details
            $shipping = new MadkrapowShipping();
            $shipping->order_id = $order->order_id; // Changed from $order->id to $order->order_id
            
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

            // Process Stripe payment
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
            
            // Handle different payment methods
            if ($request->payment_method === 'credit_card') {
                // Check if we have a payment method from the request
                if (!$request->has('stripePaymentMethod')) {
                    throw new \Exception('No payment method provided');
                }
                
                $paymentIntent = \Stripe\PaymentIntent::create([
                    'amount' => $totalAmount * 100, // Convert to cents
                    'currency' => 'myr',
                    'payment_method' => $request->stripePaymentMethod,
                    'confirmation_method' => 'manual',
                    'confirm' => true,
                    'return_url' => route('checkout.confirmation', ['id' => $order->order_id]), // Changed from $order->id
                    'metadata' => [
                        'order_id' => $order->order_id, // Changed from $order->id
                        'user_id' => Auth::id()
                    ]
                ]);

                if ($paymentIntent->status !== 'succeeded') {
                    throw new \Exception('Payment failed: ' . $paymentIntent->last_payment_error->message);
                }

                // Create payment record with Stripe reference (around line 144-145)
                $payment = new MadkrapowPayment();
                $payment->order_id = $order->order_id; // Changed from $order->id
                $payment->payment_date = Carbon::now();
                $payment->amount = $totalAmount;
                $payment->payment_method = 'stripe';
                $payment->stripe_payment_id = $paymentIntent->id;
                $payment->status = 'completed';
            } else if ($request->payment_method === 'online_banking') {
                // For online banking, we'll create a pending payment that will be updated later
                $payment = new MadkrapowPayment();
                $payment->order_id = $order->order_id; // Changed from $order->id
                $payment->payment_date = null; // Will be set when payment is confirmed
                $payment->amount = $totalAmount;
                $payment->payment_method = 'bank_transfer';
                $payment->status = 'pending';
            } else {
                throw new \Exception('Invalid payment method');
            }
            
            $payment->save();

            // Create order items and update product stock atomically
            foreach ($cartItems as $item) {
                $orderItem = new MadkrapowOrderItem();
                $orderItem->order_id = $order->order_id; // Changed from $order->id
                $orderItem->product_id = $item->product_id;
                $orderItem->quantity = $item->quantity;
                $orderItem->price_at_purchase = $item->product->price;
                $orderItem->save();

                // Atomic stock decrement
                // Find where the product stock is being updated, likely in the process method
                // Change this:
                $product = MadkrapowProduct::find($item->product_id);
                $product->decrement('stock_quantity', $item->quantity);
                
                // To this:
                $product = MadkrapowProduct::where('product_id', $item->product_id)->first();
                if ($product) {
                    $product->decrement('stock_quantity', $item->quantity);
                }
            }

            // Clear the user's cart
            MadkrapowCartItem::where('user_id', Auth::id())->delete();

            // Commit the transaction
            DB::commit();

            // At the end of the try block (around line 196)
            return redirect()->route('checkout.confirmation', ['id' => $order->order_id]); // Changed from $order->id
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Checkout error: ' . $e->getMessage());
            return redirect()->route('checkout.index')->with('error', 'Checkout failed: ' . $e->getMessage());
        }
    }

    public function confirmation($id)
    {
        $order = MadkrapowOrder::with(['user', 'orderItems.product', 'shipping', 'payment'])
            ->where('order_id', $id) // Changed from 'id' to 'order_id'
            ->where('user_id', Auth::id())
            ->firstOrFail();
    
        // Use stored shipping cost instead of recalculating
        return view('checkout.confirmation', [
            'order' => $order,
            'shippingCost' => $order->shipping_cost
        ]);
    }
}
