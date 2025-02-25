@extends('layouts.app')

@section('title', 'Order #' . $order->order_id . ' - Mad Krapow')

@section('content')
<div class="container py-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('orders.index') }}">My Orders</a></li>
            <li class="breadcrumb-item active" aria-current="page">Order #{{ $order->order_id }}</li>
        </ol>
    </nav>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="mb-0">Order #{{ $order->order_id }}</h1>
            <p class="text-muted">Placed on {{ $order->created_at->format('F j, Y, g:i a') }}</p>
        </div>
        <div class="col-md-6 text-md-end">
            <div class="d-flex justify-content-md-end gap-2">
                <a href="{{ route('orders.track', $order->order_id) }}" class="btn btn-outline-primary">
                    <i class="bi bi-truck me-1"></i> Track Order
                </a>
                @if(in_array($order->status, ['pending', 'processing']))
                    <form action="{{ route('orders.cancel', $order->order_id) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this order?')">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="bi bi-x-circle me-1"></i> Cancel Order
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Order Items</h5>
                </div>
                <div class="card-body">
                    @foreach($order->orderItems as $item)
                        <div class="row mb-4">
                            <div class="col-md-2 col-4">
                                @if($item->product->image_path)
                                    <img src="{{ asset('storage/' . $item->product->image_path) }}" class="img-fluid rounded" alt="{{ $item->product->product_name }}">
                                @else
                                    <img src="/pesmadkrapow.png" class="img-fluid rounded" alt="{{ $item->product->product_name }}">
                                @endif
                            </div>
                            <div class="col-md-10 col-8">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5 class="mb-0">
                                        <a href="{{ route('products.show', $item->product->product_id) }}" class="text-decoration-none">
                                            {{ $item->product->product_name }}
                                        </a>
                                    </h5>
                                    <span class="text-success fw-bold">RM {{ number_format($item->price_at_purchase, 2) }}</span>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-muted">Quantity: {{ $item->quantity }}</span>
                                    </div>
                                    
                                    <div>
                                        <span class="fw-bold">Subtotal: RM {{ number_format($item->quantity * $item->price_at_purchase, 2) }}</span>
                                    </div>
                                </div>
                                
                                @if($order->status == 'delivered')
                                    <div class="mt-3">
                                        @if(!$item->hasReview)
                                            <a href="{{ route('reviews.create', ['product_id' => $item->product->product_id]) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-star me-1"></i> Write a Review
                                            </a>
                                        @else
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle me-1"></i> Reviewed
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        @if(!$loop->last)
                            <hr>
                        @endif
                    @endforeach
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Shipping Information</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-2"><strong>Address:</strong></p>
                            <p class="mb-3">{{ $order->shipping->shipping_address }}</p>
                            
                            <p class="mb-2"><strong>Delivery Method:</strong></p>
                            <p class="mb-0">
                                @if($order->shipping->delivery_method == 'standard')
                                    Standard Delivery (3-5 business days)
                                @elseif($order->shipping->delivery_method == 'express')
                                    Express Delivery (1-2 business days)
                                @elseif($order->shipping->delivery_method == 'pickup')
                                    Self Pickup
                                @else
                                    {{ ucfirst($order->shipping->delivery_method) }}
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Payment Information</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-2"><strong>Payment Method:</strong></p>
                            <p class="mb-3">
                                @if($order->payment->payment_method == 'credit_card')
                                    Credit Card
                                @elseif($order->payment->payment_method == 'paypal')
                                    PayPal
                                @elseif($order->payment->payment_method == 'bank_transfer')
                                    Bank Transfer
                                @else
                                    {{ ucfirst($order->payment->payment_method) }}
                                @endif
                            </p>
                            
                            <p class="mb-2"><strong>Payment Status:</strong></p>
                            <p class="mb-0">
                                @if($order->payment->status == 'pending')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @elseif($order->payment->status == 'completed')
                                    <span class="badge bg-success">Completed</span>
                                @elseif($order->payment->status == 'failed')
                                    <span class="badge bg-danger">Failed</span>
                                @elseif($order->payment->status == 'refunded')
                                    <span class="badge bg-info">Refunded</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($order->payment->status) }}</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span>RM {{ number_format($order->total_amount - $shippingCost, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Shipping:</span>
                        <span>
                            @if($shippingCost > 0)
                                RM {{ number_format($shippingCost, 2) }}
                            @else
                                Free
                            @endif
                        </span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-0">
                        <span class="fw-bold">Total:</span>
                        <span class="fw-bold">RM {{ number_format($order->total_amount, 2) }}</span>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Order Status</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Current Status:</h5>
                        @if($order->status == 'pending')
                            <span class="badge bg-warning text-dark">Pending</span>
                        @elseif($order->status == 'processing')
                            <span class="badge bg-info">Processing</span>
                        @elseif($order->status == 'shipped')
                            <span class="badge bg-primary">Shipped</span>
                        @elseif($order->status == 'delivered')
                            <span class="badge bg-success">Delivered</span>
                        @elseif($order->status == 'cancelled')
                            <span class="badge bg-danger">Cancelled</span>
                        @else
                            <span class="badge bg-secondary">{{ ucfirst($order->status) }}</span>
                        @endif
                    </div>
                    
                    <div class="timeline">
                        <div class="timeline-item {{ in_array($order->status, ['pending', 'processing', 'shipped', 'delivered']) ? 'active' : '' }}">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0">Order Placed</h6>
                                <p class="text-muted small mb-0">{{ $order->created_at->format('M d, Y, g:i a') }}</p>
                            </div>
                        </div>
                        
                        <div class="timeline-item {{ in_array($order->status, ['processing', 'shipped', 'delivered']) ? 'active' : '' }}">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0">Processing</h6>
                                @if(in_array($order->status, ['processing', 'shipped', 'delivered']))
                                    <p class="text-muted small mb-0">{{ $order->updated_at->format('M d, Y, g:i a') }}</p>
                                @endif
                            </div>
                        </div>
                        
                        <div class="timeline-item {{ in_array($order->status, ['shipped', 'delivered']) ? 'active' : '' }}">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0">Shipped</h6>
                                @if(in_array($order->status, ['shipped', 'delivered']))
                                    <p class="text-muted small mb-0">{{ $order->shipping->shipping_date->format('M d, Y') }}</p>
                                @endif
                            </div>
                        </div>
                        
                        <div class="timeline-item {{ $order->status == 'delivered' ? 'active' : '' }}">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0">Delivered</h6>
                                @if($order->status == 'delivered')
                                    <p class="text-muted small mb-0">{{ $order->shipping->delivery_date->format('M d, Y') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Need Help?</h5>
                </div>
                <div class="card-body">
                    <p>If you have any questions or issues with your order, please contact our customer support.</p>
                    <div class="d-grid">
                        <a href="#" class="btn btn-outline-primary">
                            <i class="bi bi-chat-dots me-1"></i> Contact Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
    margin-top: 20px;
}

.timeline:before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    height: 100%;
    width: 2px;
    background-color: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
    opacity: 0.5;
}

.timeline-item.active {
    opacity: 1;
}

.timeline-marker {
    position: absolute;
    left: -30px;
    top: 0;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background-color: #e9ecef;
    border: 2px solid #fff;
}

.timeline-item.active .timeline-marker {
    background-color: #0d6efd;
}

.timeline-content {
    padding-bottom: 10px;
}
</style>
@endsection
