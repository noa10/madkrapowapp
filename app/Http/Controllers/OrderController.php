<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\MadkrapowOrder;
use App\Models\MadkrapowShipping;
use App\Models\MadkrapowPayment;
use Carbon\Carbon;

class OrderController extends Controller
{
    /**
     * Display a listing of the user's orders
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $orders = MadkrapowOrder::where('user_id', Auth::id())
            ->with(['orderItems.product', 'shipping', 'payment'])
            ->orderBy('order_date', 'desc')
            ->paginate(10);

        return view('orders.index', compact('orders'));
    }

    /**
     * Display the specified order
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    // Find the show method in your OrderController and modify it to include the shipping cost
    public function show($id)
    {
        $order = MadkrapowOrder::with(['user', 'orderItems.product', 'shipping', 'payment'])
            ->where('order_id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
        
        // Add this line to get the shipping cost from the order
        $shippingCost = $order->shipping_cost ?? 0;
        
        return view('orders.show', compact('order', 'shippingCost'));
    }
    /**
     * Display the order tracking page
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function track($id)
    {
        $order = MadkrapowOrder::where('order_id', $id)
            ->where('user_id', Auth::id())
            ->with(['orderItems.product', 'shipping', 'payment'])
            ->firstOrFail();

        return view('orders.track', compact('order'));
    }

    /**
     * Cancel the specified order
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function cancel(Request $request, $id)
    {
        $order = MadkrapowOrder::where('order_id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Check if order can be cancelled
        if (in_array($order->status, ['completed', 'cancelled'])) {
            return redirect()->route('orders.show', $id)->with('error', 'This order cannot be cancelled.');
        }

        // Update order status
        $order->status = 'cancelled';
        $order->save();

        // Update shipping status
        $shipping = MadkrapowShipping::where('order_id', $id)->first();
        if ($shipping) {
            $shipping->status = 'cancelled';
            $shipping->save();
        }

        return redirect()->route('orders.show', $id)->with('success', 'Order has been cancelled successfully.');
    }

    /**
     * Display a listing of all orders (admin)
     *
     * @return \Illuminate\Http\Response
     */
    public function adminIndex()
    {
        // This should be protected by admin middleware in a real application
        $orders = MadkrapowOrder::with(['user', 'orderItems.product', 'shipping', 'payment'])
            ->orderBy('order_date', 'desc')
            ->paginate(20);

        return view('admin.orders.index', compact('orders'));
    }

    /**
     * Display the specified order (admin)
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function adminShow($id)
    {
        // This should be protected by admin middleware in a real application
        $order = MadkrapowOrder::where('order_id', $id)
            ->with(['user', 'orderItems.product', 'shipping', 'payment'])
            ->firstOrFail();

        return view('admin.orders.show', compact('order'));
    }

    /**
     * Update the order status (admin)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function updateStatus(Request $request, $id)
    {
        // This should be protected by admin middleware in a real application
        $request->validate([
            'status' => 'required|in:pending,processing,shipped,completed,cancelled',
        ]);

        $order = MadkrapowOrder::where('order_id', $id)->firstOrFail();
        $order->status = $request->status;
        $order->save();

        return redirect()->route('admin.orders.show', $id)->with('success', 'Order status updated successfully.');
    }

    /**
     * Update the shipping status (admin)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function updateShippingStatus(Request $request, $id)
    {
        // This should be protected by admin middleware in a real application
        $request->validate([
            'status' => 'required|in:processing,shipped,delivered,cancelled',
            'tracking_number' => 'nullable|string|max:255',
        ]);

        $shipping = MadkrapowShipping::where('order_id', $id)->firstOrFail();
        $shipping->status = $request->status;
        
        if ($request->filled('tracking_number')) {
            $shipping->tracking_number = $request->tracking_number;
        }
        
        // Update shipping date if status is changed to shipped
        if ($request->status === 'shipped' && $shipping->status !== 'shipped') {
            $shipping->shipping_date = Carbon::now();
        }
        
        // Update delivery date if status is changed to delivered
        if ($request->status === 'delivered' && $shipping->status !== 'delivered') {
            $shipping->delivery_date = Carbon::now();
            
            // Also update order status to completed
            $order = MadkrapowOrder::where('order_id', $id)->first();
            if ($order) {
                $order->status = 'completed';
                $order->save();
            }
        }
        
        $shipping->save();

        return redirect()->route('admin.orders.show', $id)->with('success', 'Shipping status updated successfully.');
    }

    /**
     * Update the payment status (admin)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function updatePaymentStatus(Request $request, $id)
    {
        // This should be protected by admin middleware in a real application
        $request->validate([
            'status' => 'required|in:pending,completed,failed,refunded',
        ]);

        $payment = MadkrapowPayment::where('order_id', $id)->firstOrFail();
        $payment->status = $request->status;
        
        // Update payment date if status is changed to completed
        if ($request->status === 'completed' && $payment->status !== 'completed') {
            $payment->payment_date = Carbon::now();
        }
        
        $payment->save();

        return redirect()->route('admin.orders.show', $id)->with('success', 'Payment status updated successfully.');
    }
}
