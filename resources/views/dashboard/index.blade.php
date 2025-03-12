@extends('layouts.app')

@section('title', 'Dashboard - Mad Krapow')

@section('content')
<div class="container py-5">
    <div class="row">
        <!-- Sidebar / Account Navigation -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">My Account</h4>
                </div>
                <div class="card-body text-center py-4">
                    <div class="avatar-circle mx-auto mb-3 bg-primary text-white d-flex align-items-center justify-content-center">
                        <span class="avatar-text display-4">{{ substr($user->name, 0, 1) }}</span>
                    </div>
                    <h5 class="mb-1">{{ $user->name }}</h5>
                    <p class="text-muted">{{ $user->email }}</p>
                </div>
                <div class="list-group list-group-flush">
                    <a href="{{ route('dashboard') }}" class="list-group-item list-group-item-action active">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                    <a href="{{ route('profile') }}" class="list-group-item list-group-item-action">
                        <i class="bi bi-person-fill me-2"></i> Profile
                    </a>
                    <a href="{{ route('orders.index') }}" class="list-group-item list-group-item-action">
                        <i class="bi bi-box-seam me-2"></i> My Orders
                    </a>
                    <a href="{{ route('cart.index') }}" class="list-group-item list-group-item-action">
                        <i class="bi bi-cart me-2"></i> Shopping Cart
                    </a>
                    <a href="#" class="list-group-item list-group-item-action" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-8">
            <!-- Welcome Banner -->
            <div class="card shadow-sm mb-4 bg-primary text-white">
                <div class="card-body py-4">
                    <h2 class="mb-2">Welcome back, {{ explode(' ', $user->name)[0] }}!</h2>
                    <p class="mb-0">Thanks for being a Mad Krapow customer. Here's a summary of your account and orders.</p>
                </div>
            </div>
            
            <!-- Order Statistics -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="card shadow-sm h-100">
                        <div class="card-body text-center py-4">
                            <div class="stat-icon mb-3 bg-light rounded-circle p-3 d-inline-block">
                                <i class="bi bi-box-seam fs-3 text-primary"></i>
                            </div>
                            <h3 class="stat-value mb-1">{{ $orderCount }}</h3>
                            <p class="text-muted mb-0">Total Orders</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="card shadow-sm h-100">
                        <div class="card-body text-center py-4">
                            <div class="stat-icon mb-3 bg-light rounded-circle p-3 d-inline-block">
                                <i class="bi bi-hourglass-split fs-3 text-warning"></i>
                            </div>
                            <h3 class="stat-value mb-1">{{ $pendingOrderCount }}</h3>
                            <p class="text-muted mb-0">Pending Orders</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body text-center py-4">
                            <div class="stat-icon mb-3 bg-light rounded-circle p-3 d-inline-block">
                                <i class="bi bi-check-circle fs-3 text-success"></i>
                            </div>
                            <h3 class="stat-value mb-1">{{ $completedOrderCount }}</h3>
                            <p class="text-muted mb-0">Completed Orders</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0">Recent Orders</h5>
                    <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    @if($recentOrders->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Date</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentOrders as $order)
                                        <tr>
                                            <td>{{ $order->order_number ?? $order->order_id }}</td>
                                            <td>{{ $order->order_date ? $order->order_date->format('M d, Y') : $order->created_at->format('M d, Y') }}</td>
                                            <td>RM {{ number_format($order->total_amount, 2) }}</td>
                                            <td>
                                                <span class="badge bg-{{ $order->status === 'completed' ? 'success' : ($order->status === 'cancelled' ? 'danger' : 'primary') }}">
                                                    {{ ucfirst($order->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('orders.show', $order->order_id) }}" class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-4 text-center">
                            <div class="mb-3">
                                <i class="bi bi-bag-x fs-1 text-muted"></i>
                            </div>
                            <h5>No orders yet</h5>
                            <p class="text-muted mb-3">You haven't placed any orders with us yet.</p>
                            <a href="{{ route('products.index') }}" class="btn btn-primary">Browse Products</a>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Recommended Products -->
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Recommended For You</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($recommendedProducts as $product)
                            <div class="col-md-6 col-lg-3 mb-4">
                                <div class="card product-card h-100 border-0 shadow-sm">
                                    <img src="{{ $product->image_url ?? asset('images/products/placeholder.svg') }}" 
                                         class="card-img-top" alt="{{ $product->name }}">
                                    <div class="card-body">
                                        <h6 class="card-title">{{ $product->name }}</h6>
                                        <p class="card-text fw-bold">RM {{ number_format($product->price, 2) }}</p>
                                        <a href="{{ route('products.show', $product->product_id) }}" class="btn btn-sm btn-primary w-100">View Product</a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .avatar-circle {
        width: 80px;
        height: 80px;
        border-radius: 50%;
    }
    
    .stat-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 60px;
        height: 60px;
    }
    
    .product-card .card-img-top {
        height: 150px;
        object-fit: cover;
    }
    
    @media (max-width: 767.98px) {
        .stat-value {
            font-size: 1.5rem;
        }
    }
</style>
@endsection 