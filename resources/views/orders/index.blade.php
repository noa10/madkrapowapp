@extends('layouts.app')

@section('title', 'My Orders - Mad Krapow')

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">My Account</h4>
                </div>
                <div class="card-body text-center mb-3">
                    <i class="bi bi-person-circle display-4"></i>
                    <h5 class="mt-2">{{ Auth::user()->name }}</h5>
                    <p class="text-muted">{{ Auth::user()->email }}</p>
                </div>
                <div class="list-group list-group-flush">
                    <a href="{{ route('dashboard') }}" class="list-group-item list-group-item-action">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                    <a href="{{ route('profile.edit') }}" class="list-group-item list-group-item-action">
                        <i class="bi bi-person-fill me-2"></i> Profile
                    </a>
                    <a href="{{ route('orders.index') }}" class="list-group-item list-group-item-action active">
                        <i class="bi bi-box-seam me-2"></i> My Orders
                    </a>
                    <a href="{{ route('loyalty.index') }}" class="list-group-item list-group-item-action">
                        <i class="bi bi-award me-2"></i> Loyalty Rewards
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
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">My Orders</h4>
                </div>
                <div class="card-body">
                    @if($orders->total() > 0)
                        <div class="alert alert-info mb-4">
                            Showing {{ $orders->firstItem() }} to {{ $orders->lastItem() }} of {{ $orders->total() }} orders
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Date</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($orders as $order)
                                        <tr>
                                            <td>#{{ $order->order_id }}</td>
                                            <td>{{ $order->created_at->format('M d, Y') }}</td>
                                            <td>RM {{ number_format($order->total_amount, 2) }}</td>
                                            <td>
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
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('orders.show', $order->order_id) }}" class="btn btn-outline-primary">
                                                        <i class="bi bi-eye"></i> View
                                                    </a>
                                                    <a href="{{ route('orders.track', $order->order_id) }}" class="btn btn-outline-info">
                                                        <i class="bi bi-truck"></i> Track
                                                    </a>
                                                    @if(in_array($order->status, ['pending', 'processing']))
                                                        <form action="{{ route('orders.cancel', $order->order_id) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this order?')">
                                                            @csrf
                                                            <button type="submit" class="btn btn-outline-danger">
                                                                <i class="bi bi-x-circle"></i> Cancel
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-flex justify-content-center mt-4">
                            {{ $orders->links() }}
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-bag-x display-1 text-muted mb-3"></i>
                            <h3>No orders found</h3>
                            <p class="mb-4">You haven't placed any orders yet.</p>
                            <a href="{{ route('products.index') }}" class="btn btn-primary">
                                <i class="bi bi-shop me-1"></i> Browse Products
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
