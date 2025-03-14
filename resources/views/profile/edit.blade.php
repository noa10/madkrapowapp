@extends('layouts.app')

@section('title', 'My Profile - Mad Krapow')

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
                    <a href="{{ route('profile.edit') }}" class="list-group-item list-group-item-action active">
                        <i class="bi bi-person-fill me-2"></i> Profile
                    </a>
                    <a href="{{ route('orders.index') }}" class="list-group-item list-group-item-action">
                        <i class="bi bi-box-seam me-2"></i> Orders
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
        
        <!-- Main Content -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Personal Information</h4>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show mb-4">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show mb-4">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if (session('info'))
                        <div class="alert alert-info alert-dismissible fade show mb-4">
                            {{ session('info') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form action="{{ route('profile.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" 
                                class="form-control @error('name') is-invalid @enderror">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" 
                                class="form-control @error('email') is-invalid @enderror">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea name="address" id="address" rows="3" 
                                class="form-control @error('address') is-invalid @enderror">{{ old('address', $user->address) }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Connected Accounts</h4>
                </div>
                <div class="card-body">
                    <!-- Grab Connection -->
                    <div class="border-bottom pb-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5>Grab</h5>
                                <p class="text-muted">Connect your Grab account to earn loyalty points and see your tier.</p>
                                
                                @if($grabConnected && $grabTier)
                                    <div class="mt-2">
                                        @php
                                            $badgeClass = match($grabTier) {
                                                'platinum' => 'bg-purple text-white',
                                                'gold' => 'bg-warning text-dark',
                                                'silver' => 'bg-secondary text-white',
                                                'member' => 'bg-success text-white',
                                                default => 'bg-info text-white'
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }} p-2">
                                            Tier: {{ $tierDisplayName }}
                                        </span>
                                        <a href="{{ route('grab.tier.refresh') }}" class="btn btn-sm btn-outline-primary ms-2">
                                            <i class="bi bi-arrow-clockwise"></i> Refresh
                                        </a>
                                    </div>
                                @elseif($grabConnected && $grabError)
                                    <div class="mt-2 text-danger small">
                                        {{ $grabError }}
                                    </div>
                                @endif
                            </div>
                            
                            <div>
                                @if($grabConnected)
                                    <form method="POST" action="{{ route('auth.grab.disconnect') }}">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-danger">
                                            Disconnect
                                        </button>
                                    </form>
                                @else
                                    <a href="{{ route('auth.grab') }}" class="btn btn-success">
                                        Connect
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <!-- Other social connections would go here -->
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 