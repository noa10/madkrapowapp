@extends('layouts.app')

@section('title', $product->product_name . ' - Mad Krapow')

@section('content')
<div class="container py-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $product->product_name }}</li>
        </ol>
    </nav>

    <!-- Product Details -->
    <div class="row">
        <!-- Product Image -->
        <div class="col-md-6 mb-4 mb-md-0">
            <div class="position-relative">
                <img src="{{ $product->image_path ? asset('storage/' . $product->image_path) : '/pesmadkrapow.png' }}" class="img-fluid rounded" alt="{{ $product->product_name }}">
                
                @if($product->stock_quantity <= 0)
                    <div class="position-absolute top-0 end-0 m-3">
                        <span class="badge bg-danger">Out of Stock</span>
                    </div>
                @elseif($product->stock_quantity < 5)
                    <div class="position-absolute top-0 end-0 m-3">
                        <span class="badge bg-warning text-dark">Low Stock</span>
                    </div>
                @endif
            </div>
        </div>
        
        <!-- Product Info -->
        <div class="col-md-6">
            <h1 class="mb-3">{{ $product->product_name }}</h1>
            
            <div class="d-flex align-items-center mb-3">
                <div class="me-3">
                    @for($i = 1; $i <= 5; $i++)
                        <i class="bi bi-star{{ $i <= ($product->average_rating ?? 0) ? '-fill' : '' }} text-warning"></i>
                    @endfor
                </div>
                <a href="#reviews" class="text-decoration-none">
                    {{ $product->reviews_count ?? 0 }} {{ Str::plural('review', $product->reviews_count ?? 0) }}
                </a>
            </div>
            
            <div class="mb-4">
                <h2 class="text-success fw-bold fs-3">RM {{ number_format($product->price, 2) }}</h2>
            </div>
            
            <div class="mb-4">
                <p>{{ $product->description }}</p>
            </div>
            
            @if($product->stock_quantity > 0)
                <div class="mb-4">
                    <p class="mb-1">Availability: 
                        <span class="{{ $product->stock_quantity > 10 ? 'text-success' : 'text-warning' }}">
                            {{ $product->stock_quantity > 10 ? 'In Stock' : 'Only ' . $product->stock_quantity . ' left' }}
                        </span>
                    </p>
                </div>
                
                <form action="{{ route('cart.add') }}" method="POST" class="mb-4">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->product_id }}">
                    
                    <div class="row g-3 align-items-center">
                        <div class="col-md-3">
                            <label for="quantity" class="form-label">Quantity</label>
                            <div class="input-group">
                                <button type="button" class="btn btn-outline-secondary decrease-quantity">
                                    <i class="bi bi-dash"></i>
                                </button>
                                <input type="number" class="form-control text-center" id="quantity" name="quantity" value="1" min="1" max="{{ $product->stock_quantity }}">
                                <button type="button" class="btn btn-outline-secondary increase-quantity">
                                    <i class="bi bi-plus"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-md-9">
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-cart-plus me-2"></i> Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            @else
                <div class="alert alert-danger mb-4">
                    <i class="bi bi-exclamation-circle-fill me-2"></i> This product is currently out of stock.
                </div>
                
                <form action="{{ route('notifications.stock') }}" method="POST" class="mb-4">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->product_id }}">
                    <div class="d-grid">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="bi bi-bell me-2"></i> Notify Me When Available
                        </button>
                    </div>
                </form>
            @endif
            
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex mb-3">
                        <div class="me-3">
                            <i class="bi bi-truck text-primary fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Free Shipping</h6>
                            <p class="mb-0 small text-muted">On orders over RM 100</p>
                        </div>
                    </div>
                    
                    <div class="d-flex">
                        <div class="me-3">
                            <i class="bi bi-shield-check text-primary fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Secure Payments</h6>
                            <p class="mb-0 small text-muted">Multiple payment methods accepted</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Product Tabs -->
    <div class="row mt-5">
        <div class="col-12">
            <ul class="nav nav-tabs" id="productTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab" aria-controls="details" aria-selected="true">Details</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button" role="tab" aria-controls="reviews" aria-selected="false">Reviews</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="shipping-tab" data-bs-toggle="tab" data-bs-target="#shipping" type="button" role="tab" aria-controls="shipping" aria-selected="false">Shipping</button>
                </li>
            </ul>
            
            <div class="tab-content p-4 border border-top-0 rounded-bottom" id="productTabsContent">
                <!-- Details Tab -->
                <div class="tab-pane fade show active" id="details" role="tabpanel" aria-labelledby="details-tab">
                    <div class="row">
                        <div class="col-md-8">
                            <h4 class="mb-4">Product Details</h4>
                            <p>{{ $product->description }}</p>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Cooking Instructions</h5>
                                    <ol class="ps-3">
                                        <li class="mb-2">Tumis bawang putih, cili padi dan protein.</li>
                                        <li class="mb-2">Masuk 1 sdt pes MAD KRAPOW + 35ml air.</li>
                                        <li class="mb-2">Tabur herba, siap!</li>
                                    </ol>
                                    <div class="text-center mt-3">
                                        <img src="/pesmadkrapow.png" class="img-fluid rounded" style="max-height: 150px;" alt="Cooking Instructions">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Reviews Tab -->
                <div class="tab-pane fade" id="reviews" role="tabpanel" aria-labelledby="reviews-tab">
                    <div class="row">
                        <div class="col-md-4 mb-4 mb-md-0">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <h4 class="mb-0">{{ number_format($product->average_rating ?? 0, 1) }}</h4>
                                    <div class="my-2">
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="bi bi-star{{ $i <= ($product->average_rating ?? 0) ? '-fill' : '' }} text-warning fs-5"></i>
                                        @endfor
                                    </div>
                                    <p class="text-muted mb-4">Based on {{ $product->reviews_count ?? 0 }} {{ Str::plural('review', $product->reviews_count ?? 0) }}</p>
                                    
                                    @if(auth()->check())
                                        <a href="{{ route('reviews.create', ['product_id' => $product->product_id]) }}" class="btn btn-primary">
                                            <i class="bi bi-star me-1"></i> Write a Review
                                        </a>
                                    @else
                                        <a href="{{ route('login') }}" class="btn btn-outline-primary">
                                            <i class="bi bi-box-arrow-in-right me-1"></i> Login to Review
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-8">
                            <h4 class="mb-4">Customer Reviews</h4>
                            
                            @if(isset($reviews) && $reviews->count() > 0)
                                @foreach($reviews as $review)
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                        <span>{{ substr($review->user->name ?? 'U', 0, 1) }}</span>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0">{{ $review->user->name ?? 'User' }}</h6>
                                                        <small class="text-muted">{{ isset($review->review_date) ? $review->review_date->format('M d, Y') : date('M d, Y') }}</small>
                                                    </div>
                                                </div>
                                                <div>
                                                    @for($i = 1; $i <= 5; $i++)
                                                        <i class="bi bi-star{{ $i <= ($review->rating ?? 0) ? '-fill' : '' }} text-warning"></i>
                                                    @endfor
                                                </div>
                                            </div>
                                            <p class="mb-0">{{ $review->comment ?? 'No comment provided.' }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle-fill me-2"></i> No reviews yet. Be the first to review this product!
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Shipping Tab -->
                <div class="tab-pane fade" id="shipping" role="tabpanel" aria-labelledby="shipping-tab">
                    <h4 class="mb-4">Shipping Information</h4>
                    <div class="mb-4">
                        <h5>Delivery Options</h5>
                        <ul>
                            <li><strong>Standard Delivery:</strong> 3-5 business days (Free for orders over RM 100)</li>
                            <li><strong>Express Delivery:</strong> 1-2 business days (Additional charges apply)</li>
                        </ul>
                    </div>
                    
                    <div class="mb-4">
                        <h5>Return Policy</h5>
                        <p>We accept returns within 30 days of delivery. Items must be unused and in their original packaging.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Quantity buttons
        const decreaseBtn = document.querySelector('.decrease-quantity');
        const increaseBtn = document.querySelector('.increase-quantity');
        const quantityInput = document.querySelector('#quantity');
        
        if (decreaseBtn && increaseBtn && quantityInput) {
            decreaseBtn.addEventListener('click', function() {
                const currentValue = parseInt(quantityInput.value);
                if (currentValue > 1) {
                    quantityInput.value = currentValue - 1;
                }
            });
            
            increaseBtn.addEventListener('click', function() {
                const currentValue = parseInt(quantityInput.value);
                const maxValue = parseInt(quantityInput.getAttribute('max'));
                if (currentValue < maxValue) {
                    quantityInput.value = currentValue + 1;
                }
            });
        }
    });
</script>
@endsection
