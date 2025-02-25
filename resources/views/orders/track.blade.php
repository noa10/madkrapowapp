@extends('layouts.app')

@section('title', 'Track Order - Mad Krapow')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Track Your Order</h4>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Order #{{ $order->order_id }}</h5>
                            <span class="badge bg-{{ $order->status === 'completed' ? 'success' : ($order->status === 'cancelled' ? 'danger' : 'primary') }}">
                                {{ ucfirst($order->status) }}
                            </span>
                        </div>
                        <p class="text-muted">Ordered on {{ $order->order_date->format('F d, Y') }}</p>
                    </div>
                    
                    <!-- Order Tracking Timeline -->
                    <div class="position-relative mb-4 pb-2">
                        <div class="position-absolute top-0 bottom-0 start-0 ms-4 border-start border-2 border-primary"></div>
                        
                        <!-- Order Placed -->
                        <div class="d-flex mb-4">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; z-index: 1; position: relative;">
                                    <i class="bi bi-check-lg"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1">Order Placed</h6>
                                <p class="text-muted mb-0 small">{{ $order->order_date->format('F d, Y - h:i A') }}</p>
                                <p class="mb-0 small">Your order has been placed successfully.</p>
                            </div>
                        </div>
                        
                        <!-- Payment Confirmed -->
                        <div class="d-flex mb-4">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle {{ $order->payment->status === 'completed' ? 'bg-primary' : 'bg-light border' }} text-{{ $order->payment->status === 'completed' ? 'white' : 'muted' }} d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; z-index: 1; position: relative;">
                                    <i class="bi bi-credit-card"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1">Payment {{ ucfirst($order->payment->status) }}</h6>
                                @if($order->payment->status === 'completed')
                                    <p class="text-muted mb-0 small">{{ $order->payment->payment_date->format('F d, Y - h:i A') }}</p>
                                    <p class="mb-0 small">Payment of RM {{ number_format($order->payment->amount, 2) }} has been confirmed.</p>
                                @else
                                    <p class="text-muted mb-0 small">Pending</p>
                                    <p class="mb-0 small">We're waiting for your payment to be confirmed.</p>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Processing -->
                        <div class="d-flex mb-4">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle {{ in_array($order->status, ['processing', 'shipped', 'completed']) ? 'bg-primary' : 'bg-light border' }} text-{{ in_array($order->status, ['processing', 'shipped', 'completed']) ? 'white' : 'muted' }} d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; z-index: 1; position: relative;">
                                    <i class="bi bi-box-seam"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1">Order Processing</h6>
                                @if(in_array($order->status, ['processing', 'shipped', 'completed']))
                                    <p class="text-muted mb-0 small">{{ $order->updated_at->format('F d, Y - h:i A') }}</p>
                                    <p class="mb-0 small">Your order is being prepared for shipping.</p>
                                @else
                                    <p class="text-muted mb-0 small">Pending</p>
                                    <p class="mb-0 small">Your order will be processed soon.</p>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Shipped -->
                        <div class="d-flex mb-4">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle {{ in_array($order->shipping->status, ['shipped', 'delivered']) ? 'bg-primary' : 'bg-light border' }} text-{{ in_array($order->shipping->status, ['shipped', 'delivered']) ? 'white' : 'muted' }} d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; z-index: 1; position: relative;">
                                    <i class="bi bi-truck"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1">Order Shipped</h6>
                                @if(in_array($order->shipping->status, ['shipped', 'delivered']))
                                    <p class="text-muted mb-0 small">{{ $order->shipping->shipping_date->format('F d, Y - h:i A') }}</p>
                                    <p class="mb-0 small">Your order has been shipped via {{ ucfirst($order->shipping->delivery_method) }} shipping.</p>
                                    @if($order->shipping->tracking_number)
                                        <p class="mb-0 small">Tracking Number: <strong>{{ $order->shipping->tracking_number }}</strong></p>
                                    @endif
                                @else
                                    <p class="text-muted mb-0 small">Pending</p>
                                    <p class="mb-0 small">Your order will be shipped soon.</p>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Delivered -->
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle {{ $order->shipping->status === 'delivered' ? 'bg-success' : 'bg-light border' }} text-{{ $order->shipping->status === 'delivered' ? 'white' : 'muted' }} d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; z-index: 1; position: relative;">
                                    <i class="bi bi-house-check"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1">Order Delivered</h6>
                                @if($order->shipping->status === 'delivered')
                                    <p class="text-muted mb-0 small">{{ $order->shipping->delivery_date->format('F d, Y - h:i A') }}</p>
                                    <p class="mb-0 small">Your order has been delivered successfully.</p>
                                @else
                                    <p class="text-muted mb-0 small">Pending</p>
                                    <p class="mb-0 small">
                                        @if($order->shipping->status === 'shipped')
                                            Estimated delivery: 
                                            @if($order->shipping->delivery_method === 'standard')
                                                {{ $order->shipping->shipping_date->addDays(5)->format('F d, Y') }}
                                            @else
                                                {{ $order->shipping->shipping_date->addDays(2)->format('F d, Y') }}
                                            @endif
                                        @else
                                            Your order will be delivered soon after shipping.
                                        @endif
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <!-- Shipping Information -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Shipping Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <h6 class="text-muted mb-2">Shipping Address</h6>
                                    <address>
                                        {{ $order->shipping->shipping_address }}
                                    </address>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-2">Shipping Method</h6>
                                    <p class="mb-0">{{ ucfirst($order->shipping->delivery_method) }} Shipping</p>
                                    <p class="mb-0 text-muted small">
                                        @if($order->shipping->delivery_method === 'standard')
                                            3-5 business days
                                        @else
                                            1-2 business days
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Items -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Order Items</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Product</th>
                                            <th class="text-center">Quantity</th>
                                            <th class="text-end">Price</th>
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
                                                <td class="text-end">RM {{ number_format($item->price_at_purchase * $item->quantity, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <td colspan="2" class="text-end fw-bold">Total:</td>
                                            <td class="text-end fw-bold">RM {{ number_format($order->total_amount, 2) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('orders.index') }}" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left me-2"></i> Back to Orders
                        </a>
                        <div>
                            @if($order->status !== 'cancelled' && $order->status !== 'completed')
                                <form action="{{ route('orders.cancel', $order->order_id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to cancel this order?')">
                                        <i class="bi bi-x-circle me-2"></i> Cancel Order
                                    </button>
                                </form>
                            @endif
                            
                            @if($order->status === 'completed')
                                <a href="{{ route('reviews.create', $order->orderItems[0]->product_id) }}" class="btn btn-primary">
                                    <i class="bi bi-star me-2"></i> Write a Review
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Need Help Section -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Need Help?</h5>
                </div>
                <div class="card-body">
                    <p>If you have any questions or concerns about your order, please contact our customer support team.</p>
                    <div class="d-flex flex-column flex-md-row gap-3">
                        <a href="#" class="btn btn-outline-primary">
                            <i class="bi bi-chat-dots me-2"></i> Live Chat
                        </a>
                        <a href="mailto:support@madkrapow.com" class="btn btn-outline-primary">
                            <i class="bi bi-envelope me-2"></i> Email Support
                        </a>
                        <a href="tel:+60123456789" class="btn btn-outline-primary">
                            <i class="bi bi-telephone me-2"></i> Call Us
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
