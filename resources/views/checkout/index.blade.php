@extends('layouts.app')

@section('title', 'Checkout - Mad Krapow')

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Checkout</h4>
                </div>
                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('checkout.process') }}" method="POST" id="checkout-form">
                        @csrf
                        
                        <!-- Shipping Information -->
                        <div class="mb-4">
                            <h5 class="mb-3">Shipping Information</h5>
                            
                            @if(Auth::user()->address)
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="use_profile_address" id="use_profile_address" value="1" checked>
                                        <label class="form-check-label" for="use_profile_address">
                                            Use my profile address
                                        </label>
                                    </div>
                                    <div class="mt-2 p-3 bg-light rounded" id="profile-address-display">
                                        <address class="mb-0">
                                            {{ Auth::user()->name }}<br>
                                            {{ Auth::user()->address }}<br>
                                            {{ Auth::user()->email }}<br>
                                            {{ Auth::user()->phone ?? 'No phone number provided' }}
                                        </address>
                                    </div>
                                </div>
                            @endif
                            
                            <div id="shipping-form" class="{{ Auth::user()->address ? 'd-none' : '' }}">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('first_name') is-invalid @enderror" id="first_name" name="first_name" value="{{ old('first_name') }}">
                                        @error('first_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('last_name') is-invalid @enderror" id="last_name" name="last_name" value="{{ old('last_name') }}">
                                        @error('last_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', Auth::user()->email) }}">
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}">
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address') }}">
                                    @error('address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('city') is-invalid @enderror" id="city" name="city" value="{{ old('city') }}">
                                        @error('city')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="state" class="form-label">State <span class="text-danger">*</span></label>
                                        <select class="form-select @error('state') is-invalid @enderror" id="state" name="state">
                                            <option value="">Select State</option>
                                            <option value="Johor" {{ old('state') == 'Johor' ? 'selected' : '' }}>Johor</option>
                                            <option value="Kedah" {{ old('state') == 'Kedah' ? 'selected' : '' }}>Kedah</option>
                                            <option value="Kelantan" {{ old('state') == 'Kelantan' ? 'selected' : '' }}>Kelantan</option>
                                            <option value="Kuala Lumpur" {{ old('state') == 'Kuala Lumpur' ? 'selected' : '' }}>Kuala Lumpur</option>
                                            <option value="Labuan" {{ old('state') == 'Labuan' ? 'selected' : '' }}>Labuan</option>
                                            <option value="Melaka" {{ old('state') == 'Melaka' ? 'selected' : '' }}>Melaka</option>
                                            <option value="Negeri Sembilan" {{ old('state') == 'Negeri Sembilan' ? 'selected' : '' }}>Negeri Sembilan</option>
                                            <option value="Pahang" {{ old('state') == 'Pahang' ? 'selected' : '' }}>Pahang</option>
                                            <option value="Penang" {{ old('state') == 'Penang' ? 'selected' : '' }}>Penang</option>
                                            <option value="Perak" {{ old('state') == 'Perak' ? 'selected' : '' }}>Perak</option>
                                            <option value="Perlis" {{ old('state') == 'Perlis' ? 'selected' : '' }}>Perlis</option>
                                            <option value="Putrajaya" {{ old('state') == 'Putrajaya' ? 'selected' : '' }}>Putrajaya</option>
                                            <option value="Sabah" {{ old('state') == 'Sabah' ? 'selected' : '' }}>Sabah</option>
                                            <option value="Sarawak" {{ old('state') == 'Sarawak' ? 'selected' : '' }}>Sarawak</option>
                                            <option value="Selangor" {{ old('state') == 'Selangor' ? 'selected' : '' }}>Selangor</option>
                                            <option value="Terengganu" {{ old('state') == 'Terengganu' ? 'selected' : '' }}>Terengganu</option>
                                        </select>
                                        @error('state')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="postal_code" class="form-label">Postal Code <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('postal_code') is-invalid @enderror" id="postal_code" name="postal_code" value="{{ old('postal_code') }}">
                                        @error('postal_code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Shipping Method -->
                        <div class="mb-4">
                            <h5 class="mb-3">Shipping Method</h5>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="shipping_method" id="shipping_standard" value="standard" checked>
                                        <label class="form-check-label" for="shipping_standard">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong>Standard Shipping</strong>
                                                    <p class="text-muted mb-0 small">3-5 business days</p>
                                                </div>
                                                <div>
                                                    @if($subtotal >= 100)
                                                        <span class="text-success">Free</span>
                                                    @else
                                                        <span>RM {{ number_format($shippingFee, 2) }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-body">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="shipping_method" id="shipping_express" value="express">
                                        <label class="form-check-label" for="shipping_express">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong>Express Shipping</strong>
                                                    <p class="text-muted mb-0 small">1-2 business days</p>
                                                </div>
                                                <div>
                                                    <span>RM {{ number_format($expressShippingFee, 2) }}</span>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Payment Method -->
                        <div class="mb-4">
                            <h5 class="mb-3">Payment Method</h5>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="form-check">
                                        <input class="form-check-input payment-method-radio" type="radio" name="payment_method" id="payment_credit_card" value="credit_card" checked>
                                        <label class="form-check-label" for="payment_credit_card">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                    <i class="bi bi-credit-card fs-4"></i>
                                                </div>
                                                <div>
                                                    <strong>Credit/Debit Card</strong>
                                                    <p class="text-muted mb-0 small">Visa, Mastercard, American Express</p>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    
                                    <div id="credit-card-form" class="mt-3 ps-4">
                                        <div class="mb-3">
                                            <label for="card_number" class="form-label">Card Number <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('card_number') is-invalid @enderror" id="card_number" name="card_number" placeholder="1234 5678 9012 3456">
                                            @error('card_number')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="expiry_date" class="form-label">Expiry Date <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control @error('expiry_date') is-invalid @enderror" id="expiry_date" name="expiry_date" placeholder="MM/YY">
                                                @error('expiry_date')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="cvv" class="form-label">CVV <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control @error('cvv') is-invalid @enderror" id="cvv" name="cvv" placeholder="123">
                                                @error('cvv')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="card_name" class="form-label">Name on Card <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('card_name') is-invalid @enderror" id="card_name" name="card_name">
                                            @error('card_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="form-check">
                                        <input class="form-check-input payment-method-radio" type="radio" name="payment_method" id="payment_online_banking" value="online_banking">
                                        <label class="form-check-label" for="payment_online_banking">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                    <i class="bi bi-bank fs-4"></i>
                                                </div>
                                                <div>
                                                    <strong>Online Banking</strong>
                                                    <p
