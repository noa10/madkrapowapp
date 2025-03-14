@extends('layouts.app')

@section('title', 'Loyalty Rewards - Mad Krapow')

@section('content')
<div class="container py-5">
    <div class="row">
        <!-- Sidebar / Account Navigation -->
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
                    <a href="{{ route('orders.index') }}" class="list-group-item list-group-item-action">
                        <i class="bi bi-box-seam me-2"></i> Orders
                    </a>
                    <a href="{{ route('loyalty.index') }}" class="list-group-item list-group-item-action active">
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

        <!-- Main Content -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Grab Loyalty Rewards</h4>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if(session('info'))
                        <div class="alert alert-info">
                            {{ session('info') }}
                        </div>
                    @endif

                    <!-- Loyalty Tier Section -->
                    <h5>Your Grab Loyalty Tier</h5>
                    
                    @if($grabTier)
                        <div class="mb-4">
                            <span class="badge {{ $tierClasses['bg'] }} {{ $tierClasses['text'] }} p-2">
                                <i class="bi bi-circle-fill {{ $tierClasses['dot'] }} me-1"></i>
                                {{ $tierDisplayName }}
                            </span>
                            <p class="mt-2">Enjoy special benefits and rewards with your {{ $tierDisplayName }} tier!</p>
                        </div>
                    @elseif($grabTierError)
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            {{ $grabTierError }}
                        </div>
                        <div class="mt-3">
                            <a href="{{ route('auth.grab') }}" class="btn btn-outline-primary">
                                <i class="bi bi-link-45deg me-1"></i> Connect Grab Account
                            </a>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            Connect your Grab account to see your loyalty tier and earn points!
                        </div>
                        <div class="mt-3">
                            <a href="{{ route('auth.grab') }}" class="btn btn-outline-primary">
                                <i class="bi bi-link-45deg me-1"></i> Connect Grab Account
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Recent Orders Section -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Recent Orders</h4>
                </div>
                <div class="card-body">
                    <h5>Earn Points for Your Purchases</h5>
                    <p>Your completed orders are eligible for Grab rewards points!</p>

                    @if($recentOrders->isEmpty())
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            You don't have any completed orders yet.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Date</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Points</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentOrders as $order)
                                    <tr>
                                        <td>
                                            <a href="{{ route('orders.show', $order->order_id) }}">
                                                #{{ $order->order_id }}
                                            </a>
                                        </td>
                                        <td>{{ $order->order_date->format('M d, Y') }}</td>
                                        <td>${{ number_format($order->total_amount, 2) }}</td>
                                        <td>
                                            <span class="badge bg-success">{{ ucfirst($order->status) }}</span>
                                        </td>
                                        <td>
                                            @if($order->points_awarded)
                                                <span class="badge bg-success">
                                                    <i class="bi bi-check-circle-fill me-1"></i> Awarded
                                                </span>
                                            @else
                                                <form action="{{ route('loyalty.award-points', $order->order_id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-plus-circle me-1"></i> Earn Points
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <div class="mt-3">
                        <div class="alert alert-secondary">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            <strong>Note:</strong> Points will be awarded asynchronously and may take a few minutes to appear in your Grab account.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 