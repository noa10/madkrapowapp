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

    /**
     * Display the checkout page
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to proceed with checkout');
        }

        // Get cart items for the current user
        $cartItems = MadkrapowCartItem::where('user_id', Auth::id())
            ->with('product')
            ->get();

        // If cart is empty, redirect to cart page
        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty');
        }

        // Calculate subtotal
        $subtotal = 0;
        foreach ($cartItems as $item) {
            $subtotal += $item->product->price * $item->quantity;
        }

        return view('checkout.index', [
            'cartItems' => $cartItems,
            'subtotal' => $subtotal,
            'shippingFee' => $this->standardShippingFee,
            'expressShippingFee' => $this->expressShippingFee
        ]);
    }

    /**
     * Process the checkout
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function process(Request $request)
    {
        // Validate the request
        $request->validate([
            'payment_method' => 'required|in:credit_card,online_banking,e_wallet',
            'shipping_method' => 'required|in:standard,express',
            'terms_agree' => 'required|accepted',
        ]);

        // If using credit card, validate card details
        if ($request->payment_method === 'credit_card') {
            $request->validate([
                'card_number' => 'required|string|min:16|max:19',
                'expiry_date' => 'required|string|regex:/^(0[1-9]|1[0-2])\/([0-9]{2})$/',
                'cvv' => 'required|string|min:3|max:4',
                'card_name' => 'required|string|max:255',
            ]);
        }

        // If not using profile address, validate shipping details
        if (!$request->has('use_profile_address')) {
            $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'required|string|max:20',
                'address' => 'required|string|max:255',
                'city' => 'required|string|max:255',
                'postal_code' => 'required|string|max:10',
                'state' => 'required|string|max:255',
            ]);
        }

        // Get cart items for the current user
        $cartItems = MadkrapowCartItem::where('user_id', Auth::id())
            ->with('product')
            ->get();

        // If cart is empty, redirect to cart page
        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty');
        }

        // Calculate subtotal
        $subtotal = 0;
        foreach ($cartItems as $item) {
            // Check if product is in stock
            if ($item->product->stock_quantity < $item->quantity) {
                return redirect()->route('checkout.index')->with('error', 'Sorry, ' . $item->product->product_name . ' is out of stock or has insufficient quantity.');
            }
            
            $subtotal += $item->product->price * $item->quantity;
        }

        // Calculate shipping cost
        $shippingCost = 0;
        if ($request->shipping_method === 'express') {
            $shippingCost = $this->expressShippingFee;
        } else {
            // Standard shipping is free if subtotal is above threshold
            $shippingCost = $subtotal >= $this->freeShippingThreshold ? 0 : $this->standardShippingFee;
        }

        // Calculate total amount
        $totalAmount = $subtotal + $shippingCost;

        // Start a database transaction
        DB::beginTransaction();

        try {
            // Create order
            $order = new MadkrapowOrder();
            $order->user_id = Auth::id();
            $order->order_date = Carbon::now();
            $order->total_amount = $totalAmount;
            $order->status = 'pending';
            $order->save();

            // Create shipping record
            $shipping = new MadkrapowShipping();
            $shipping->shipping_id = $order->order_id; // Use order_id as shipping_id
            $shipping->order_id = $order->order_id;
            
            // Set shipping address
            if ($request->has('use_profile_address')) {
                $user = Auth::user();
                $shipping->shipping_address = $user->address;
                // You might need to split the address into components or store the full address
                // This depends on how your user address is structured
            } else {
                $shipping->shipping_address = $request->address . ', ' . $request->city . ', ' . $request->postal_code . ', ' . $request->state . ', Malaysia';
            }
            
            $shipping->shipping_date = Carbon::now();
            $shipping->delivery_method = $request->shipping_method;
            $shipping->status = 'processing';
            $shipping->save();

            // Create payment record
            $payment = new MadkrapowPayment();
            $payment->payment_id = $order->order_id; // Use order_id as payment_id
            $payment->order_id = $order->order_id;
            $payment->payment_date = Carbon::now();
            $payment->amount = $totalAmount;
            $payment->payment_method = $request->payment_method;
            $payment->status = 'completed'; // Assuming payment is successful immediately
            $payment->save();

            // Create order items and update product stock
            foreach ($cartItems as $item) {
                $orderItem = new MadkrapowOrderItem();
                $orderItem->order_item_id = uniqid('item_'); // Generate a unique ID
                $orderItem->order_id = $order->order_id;
                $orderItem->product_id = $item->product_id;
                $orderItem->quantity = $item->quantity;
                $orderItem->price_at_purchase = $item->product->price;
                $orderItem->save();

                // Update product stock
                $product = MadkrapowProduct::find($item->product_id);
                $product->stock_quantity -= $item->quantity;
                $product->save();
            }

            // Clear the user's cart
            MadkrapowCartItem::where('user_id', Auth::id())->delete();

            // Commit the transaction
            DB::commit();

            // Redirect to confirmation page
            return redirect()->route('checkout.confirmation', ['order_id' => $order->order_id]);
        } catch (\Exception $e) {
            // Rollback the transaction if something goes wrong
            DB::rollback();
            
            // Log the error
            \Log::error('Checkout error: ' . $e->getMessage());
            
            // Redirect back with error
            return redirect()->route('checkout.index')->with('error', 'An error occurred during checkout. Please try again.');
        }
    }

    /**
     * Display the order confirmation page
     *
     * @param  string  $order_id
     * @return \Illuminate\Http\Response
     */
    public function confirmation($order_id)
    {
        // Get the order with related data
        $order = MadkrapowOrder::with(['user', 'orderItems.product', 'shipping', 'payment'])
            ->where('order_id', $order_id)
            ->where('user_id', Auth::id()) // Ensure the order belongs to the current user
            ->firstOrFail();

        // Calculate shipping cost
        $shippingCost = 0;
        if ($order->shipping->delivery_method === 'express') {
            $shippingCost = $this->expressShippingFee;
        } else {
            // Calculate subtotal to determine if shipping was free
            $subtotal = 0;
            foreach ($order->orderItems as $item) {
                $subtotal += $item->price_at_purchase * $item->quantity;
            }
            
            $shippingCost = $subtotal >= $this->freeShippingThreshold ? 0 : $this->standardShippingFee;
        }

        return view('checkout.confirmation', [
            'order' => $order,
            'shippingCost' => $shippingCost
        ]);
    }
}
