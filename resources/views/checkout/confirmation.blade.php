@extends('layouts.app')

@section('title', 'Order Confirmation - Mad Krapow')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-success mb-4">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="bi bi-check-circle-fill me-2"></i> Order Confirmed</h4>
                </div>
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
                    </div>
                    <h2 class="mb-3">Thank You for Your Order!</h2>
                    <p class="lead mb-4">Your order has been placed successfully.</p>
                    <div class="d-flex justify-content-center mb-4">
                        <div class="px-4 py-2 bg-light rounded-3">
                            <p class="mb-0">Order Number: <strong>{{ $order->order_id }}</strong></p>
                        </div>
                    </div>
                    <p>A confirmation email has been sent to <strong>{{ $order->user->email }}</strong></p>
                </div>
            </div>

            <!-- Order Details -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Order Details</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Order Date</h6>
                            <p class="mb-0">{{ $order->order_date->format('F d, Y') }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Payment Method</h6>
                            <p class="mb-0">{{ ucfirst(str_replace('_', ' ', $order->payment->payment_method)) }}</p>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Shipping Address</h6>
                            <address>
                                {{ $order->shipping->shipping_address }}<br>
                                {{ $order->shipping->city }}, {{ $order->shipping->postal_code }}<br>
                                {{ $order->shipping->state }}, Malaysia
                            </address>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Shipping Method</h6>
                            <p class="mb-0">{{ ucfirst($order->shipping->delivery_method) }} Shipping</p>
                            <p class="mb-0 text-muted small">
                                @if($order->shipping->delivery_method == 'standard')
                                    Estimated delivery: 3-5 business days
                                @else
                                    Estimated delivery: 1-2 business days
                                @endif
                            </p>
                        </div>
                    </div>
                    
                    <h6 class="mb-3">Order Items</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->orderItems as $item)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0 me-3">
                                                    <img src="{{ $item->product->image_path ? asset('storage/' . $item->product->image_path) : '/pesmadkrapow.png' }}" 
                                                        class="img-thumbnail" alt="{{ $item->product->product_name }}" style="width: 50px; height: 50px; object-fit: cover;">
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">{{ $item->product->product_name }}</h6>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">{{ $item->quantity }}</td>
                                        <td class="text-end">RM {{ number_format($item->price_at_purchase, 2) }}</td>
                                        <td class="text-end">RM {{ number_format($item->price_at_purchase * $item->quantity, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end">Subtotal</td>
                                    <td class="text-end">RM {{ number_format($order->total_amount - $shippingCost, 2) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end">Shipping</td>
                                    <td class="text-end">
                                        @if($shippingCost > 0)
                                            RM {{ number_format($shippingCost, 2) }}
                                        @else
                                            <span class="text-success">Free</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end fw-bold">Total</td>
                                    <td class="text-end fw-bold">RM {{ number_format($order->total_amount, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- What's Next -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">What's Next?</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-3 mb-md-0">
                            <div class="mb-3">
                                <i class="bi bi-envelope-check text-primary" style="font-size: 2rem;"></i>
                            </div>
                            <h6>Check Your Email</h6>
                            <p class="small text-muted">We've sent a confirmation email with all the details of your order.</p>
                        </div>
                        <div class="col-md-4 text-center mb-3 mb-md-0">
                            <div class="mb-3">
                                <i class="bi bi-truck text-primary" style="font-size: 2rem;"></i>
                            </div>
                            <h6>Track Your Order</h6>
                            <p class="small text-muted">You can track your order status in your account dashboard.</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="mb-3">
                                <i class="bi bi-chat-dots text-primary" style="font-size: 2rem;"></i>
                            </div>
                            <h6>Need Help?</h6>
                            <p class="small text-muted">Contact our customer support if you have any questions.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="{{ route('products.index') }}" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left me-2"></i> Continue Shopping
                </a>
                <a href="{{ route('orders.show', $order->order_id) }}" class="btn btn-primary">
                    <i class="bi bi-eye me-2"></i> View Order Details
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
