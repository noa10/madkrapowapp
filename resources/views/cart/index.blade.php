@extends('layouts.app')

@section('title', 'Shopping Cart - Mad Krapow')

@section('content')
<div class="container py-5">
    <h1 class="mb-4">Shopping Cart</h1>
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    @if(isset($cartItems) && count($cartItems) > 0)
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 100px;">Product</th>
                                        <th>Name</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($cartItems as $item)
                                        <tr>
                                            <td>
                                                <a href="{{ route('products.show', $item->product->product_id) }}">
                                                    <img src="{{ $item->product->image_path ? asset('storage/' . $item->product->image_path) : '/pesmadkrapow.png' }}" 
                                                        class="img-thumbnail" alt="{{ $item->product->product_name }}" style="width: 80px; height: 80px; object-fit: cover;">
                                                </a>
                                            </td>
                                            <td>
                                                <a href="{{ route('products.show', $item->product->product_id) }}" class="text-decoration-none text-dark">
                                                    {{ $item->product->product_name }}
                                                </a>
                                            </td>
                                            <td>RM {{ number_format($item->product->price, 2) }}</td>
                                            <td>
                                                <form action="{{ route('cart.update') }}" method="POST" class="d-flex align-items-center quantity-form">
                                                    @csrf
                                                    <input type="hidden" name="cart_item_id" value="{{ $item->cart_item_id }}">
                                                    <div class="input-group input-group-sm" style="width: 120px;">
                                                        <button type="button" class="btn btn-outline-secondary decrease-quantity">
                                                            <i class="bi bi-dash"></i>
                                                        </button>
                                                        <input type="number" class="form-control text-center quantity-input" name="quantity" value="{{ $item->quantity }}" 
                                                            min="1" max="{{ $item->product->stock_quantity }}" data-item-id="{{ $item->cart_item_id }}">
                                                        <button type="button" class="btn btn-outline-secondary increase-quantity">
                                                            <i class="bi bi-plus"></i>
                                                        </button>
                                                    </div>
                                                </form>
                                            </td>
                                            <td class="fw-bold item-total">RM {{ number_format($item->product->price * $item->quantity, 2) }}</td>
                                            <td>
                                                <form action="{{ route('cart.remove') }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="cart_item_id" value="{{ $item->cart_item_id }}">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to remove this item?')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="{{ route('products.index') }}" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left me-2"></i> Continue Shopping
                    </a>
                    
                    <form action="{{ route('cart.clear') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to clear your cart?')">
                            <i class="bi bi-trash me-2"></i> Clear Cart
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <span>Subtotal</span>
                            <span class="fw-bold" id="subtotal">RM {{ number_format($subtotal, 2) }}</span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <span>Shipping</span>
                            <span id="shipping">
                                @if($subtotal >= 100)
                                    <span class="text-success">Free</span>
                                @else
                                    RM {{ number_format($shippingFee, 2) }}
                                @endif
                            </span>
                        </div>
                        
                        @if($subtotal < 100)
                            <div class="alert alert-info small">
                                <i class="bi bi-info-circle-fill me-2"></i> Add RM {{ number_format(100 - $subtotal, 2) }} more to get FREE shipping!
                            </div>
                        @endif
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-4">
                            <span class="fw-bold">Total</span>
                            <span class="fw-bold fs-5" id="total">
                                RM {{ number_format($subtotal >= 100 ? $subtotal : $subtotal + $shippingFee, 2) }}
                            </span>
                        </div>
                        
                        <div class="mb-3">
                            <label for="promo-code" class="form-label">Promo Code</label>
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" id="promo-code" placeholder="Enter promo code">
                                <button class="btn btn-outline-primary" type="button" id="apply-promo">Apply</button>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <a href="{{ route('checkout.index') }}" class="btn btn-primary btn-lg">
                                Proceed to Checkout <i class="bi bi-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title">We Accept</h5>
                        <div class="d-flex gap-2 mt-3">
                            <div class="payment-icon">
                                <i class="bi bi-credit-card fs-3"></i>
                            </div>
                            <div class="payment-icon">
                                <i class="bi bi-paypal fs-3"></i>
                            </div>
                            <div class="payment-icon">
                                <i class="bi bi-wallet2 fs-3"></i>
                            </div>
                            <div class="payment-icon">
                                <i class="bi bi-bank fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="card p-5 text-center">
            <div class="mb-4">
                <i class="bi bi-cart-x fs-1 text-muted"></i>
            </div>
            <h3>Your cart is empty</h3>
            <p class="text-muted mb-4">Looks like you haven't added any products to your cart yet.</p>
            <div>
                <a href="{{ route('products.index') }}" class="btn btn-primary">
                    <i class="bi bi-shop me-2"></i> Browse Products
                </a>
            </div>
        </div>
        
        <div class="mt-5">
            <h3>Recommended Products</h3>
            <div class="row mt-4">
                @if(isset($recommendedProducts) && count($recommendedProducts) > 0)
                    @foreach($recommendedProducts as $product)
                        <div class="col-md-3 col-6 mb-4">
                            <div class="card h-100 product-card">
                                <a href="{{ route('products.show', $product->product_id) }}">
                                    <img src="{{ $product->image_path ? asset('storage/' . $product->image_path) : '/pesmadkrapow.png' }}" class="card-img-top" alt="{{ $product->product_name }}">
                                </a>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title">
                                        <a href="{{ route('products.show', $product->product_id) }}" class="text-decoration-none text-dark">
                                            {{ $product->product_name }}
                                        </a>
                                    </h5>
                                    <div class="mt-auto">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-success fw-bold">RM {{ number_format($product->price, 2) }}</span>
                                            <form action="{{ route('cart.add') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="product_id" value="{{ $product->product_id }}">
                                                <input type="hidden" name="quantity" value="1">
                                                <button type="submit" class="btn btn-sm btn-outline-primary" {{ $product->stock_quantity <= 0 ? 'disabled' : '' }}>
                                                    <i class="bi bi-cart-plus"></i> Add
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle-fill me-2"></i> No recommended products available at the moment.
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Quantity buttons functionality
        const quantityForms = document.querySelectorAll('.quantity-form');
        
        quantityForms.forEach(form => {
            const decreaseBtn = form.querySelector('.decrease-quantity');
            const increaseBtn = form.querySelector('.increase-quantity');
            const quantityInput = form.querySelector('.quantity-input');
            const itemId = quantityInput.getAttribute('data-item-id');
            
            decreaseBtn.addEventListener('click', function() {
                const currentValue = parseInt(quantityInput.value);
                if (currentValue > 1) {
                    quantityInput.value = currentValue - 1;
                    updateCart(itemId, currentValue - 1);
                }
            });
            
            increaseBtn.addEventListener('click', function() {
                const currentValue = parseInt(quantityInput.value);
                const maxValue = parseInt(quantityInput.getAttribute('max'));
                if (currentValue < maxValue) {
                    quantityInput.value = currentValue + 1;
                    updateCart(itemId, currentValue + 1);
                }
            });
            
            quantityInput.addEventListener('change', function() {
                const currentValue = parseInt(quantityInput.value);
                const maxValue = parseInt(quantityInput.getAttribute('max'));
                
                if (currentValue < 1) {
                    quantityInput.value = 1;
                    updateCart(itemId, 1);
                } else if (currentValue > maxValue) {
                    quantityInput.value = maxValue;
                    updateCart(itemId, maxValue);
                } else {
                    updateCart(itemId, currentValue);
                }
            });
        });
        
        // Function to update cart via AJAX
        function updateCart(itemId, quantity) {
            // This would normally be an AJAX call to update the cart
            // For demo purposes, we're just submitting the form
            const form = document.querySelector(`.quantity-input[data-item-id="${itemId}"]`).closest('form');
            form.submit();
        }
        
        // Promo code button
        const applyPromoBtn = document.getElementById('apply-promo');
        if (applyPromoBtn) {
            applyPromoBtn.addEventListener('click', function() {
                const promoCode = document.getElementById('promo-code').value.trim();
                if (promoCode) {
                    alert('Promo code applied: ' + promoCode);
                    // This would normally be an AJAX call to apply the promo code
                } else {
                    alert('Please enter a promo code');
                }
            });
        }
    });
</script>
@endsection
