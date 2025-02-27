@extends('layouts.app')

@section('title', 'Home - Mad Krapow')

@section('content')
<!-- Hero Section -->
<div class="hero-section position-relative">
    <div class="hero-image" style="background-image: url('{{ asset('images/dalle2.jpg') }}'); height: 500px; background-size: cover; background-position: top;">
        <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark" style="opacity: 0.5;"></div>
        <div class="container h-100">
            <div class="row h-100 align-items-center">
                <div class="col-md-8 text-white position-relative">
                    <h1 class="display-4 fw-bold mb-4">Authentic Thai Street Food</h1>
                    <p class="lead mb-4">Experience the bold flavors of Thailand with our signature Mad Krapow dishes</p>
                    <div class="d-flex gap-3">
                        <a href="{{ route('products.index') }}" class="btn btn-primary btn-lg">
                            <i class="bi bi-shop me-2"></i> Shop Now
                        </a>
                        <a href="#featured-products" class="btn btn-outline-light btn-lg">
                            <i class="bi bi-arrow-down me-2"></i> Explore
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="bg-light py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-primary text-white rounded-circle mb-3 mx-auto d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class="bi bi-truck fs-4"></i>
                        </div>
                        <h5 class="card-title">Fast Delivery</h5>
                        <p class="card-text text-muted">Free shipping on orders over RM 100. Delivery within 3-5 business days.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-primary text-white rounded-circle mb-3 mx-auto d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class="bi bi-shield-check fs-4"></i>
                        </div>
                        <h5 class="card-title">Quality Ingredients</h5>
                        <p class="card-text text-muted">We use only the freshest ingredients sourced from local suppliers.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-primary text-white rounded-circle mb-3 mx-auto d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class="bi bi-star fs-4"></i>
                        </div>
                        <h5 class="card-title">Authentic Taste</h5>
                        <p class="card-text text-muted">Experience the true flavors of Thailand with our authentic recipes.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Featured Products Section -->
<div id="featured-products" class="py-5">
    <div class="container">
        <div class="row mb-4">
            <div class="col-12 text-center">
                <h2 class="section-title">Featured Products</h2>
                <p class="text-muted">Discover our most popular Thai dishes</p>
            </div>
        </div>
        
        <div class="row">
            @foreach($featuredProducts as $product)
                <div class="col-md-3 col-6 mb-4">
                    <div class="card h-100 product-card">
                        <div class="position-relative">
                            <a href="{{ route('products.show', $product->product_id) }}">
                                @if($product->image_path)
                                    <img src="{{ asset('/' . $product->image_path) }}" class="card-img-top" alt="{{ $product->product_name }}">
                                @else
                                    <img src="/pesmadkrapow.png" class="card-img-top" alt="{{ $product->product_name }}">
                                @endif
                            </a>
                            
                            @if($product->stock_quantity <= 0)
                                <div class="position-absolute top-0 end-0 m-2">
                                    <span class="badge bg-danger">Out of Stock</span>
                                </div>
                            @elseif($product->stock_quantity < 5)
                                <div class="position-absolute top-0 end-0 m-2">
                                    <span class="badge bg-warning text-dark">Low Stock</span>
                                </div>
                            @endif
                        </div>
                        
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">
                                <a href="{{ route('products.show', $product->product_id) }}" class="text-decoration-none text-dark">
                                    {{ $product->product_name }}
                                </a>
                            </h5>
                            
                            <div class="mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="me-2">
                                        @php
                                            $rating = $product->average_rating ?? 0;
                                        @endphp
                                        
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= $rating)
                                                <i class="bi bi-star-fill text-warning small"></i>
                                            @elseif($i - 0.5 <= $rating)
                                                <i class="bi bi-star-half text-warning small"></i>
                                            @else
                                                <i class="bi bi-star text-warning small"></i>
                                            @endif
                                        @endfor
                                    </div>
                                    <small class="text-muted">({{ $product->reviews_count ?? 0 }})</small>
                                </div>
                            </div>
                            
                            <p class="card-text text-muted small mb-3">
                                {{ Str::limit($product->description, 60) }}
                            </p>
                            
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
        </div>
        
        <div class="text-center mt-4">
            <a href="{{ route('products.index') }}" class="btn btn-outline-primary">
                View All Products <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</div>

<!-- Categories Section -->


<!-- About Section -->
<div class="py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 mb-4 mb-md-0">
                <img src="{{ asset('images/pesmadkrapow.png') }}" class="img-fluid rounded" alt="About Mad Krapow">
            </div>
            <div class="col-md-6">
                <h2 class="section-title mb-4">About Mad Krapow</h2>
                <p class="lead mb-4">Bringing authentic Thai street food flavors to your doorstep</p>
                <p class="mb-4">Mad Krapow was founded with a passion for sharing the vibrant and bold flavors of Thai street food. Our signature dish, Pad Krapow, is a spicy stir-fry with holy basil that captures the essence of Thai cuisine.</p>
                <p class="mb-4">We source the freshest ingredients and use traditional cooking methods to ensure an authentic taste experience. Whether you're a Thai food enthusiast or trying it for the first time, our products will take your taste buds on a journey to the streets of Bangkok.</p>
                <a href="#" class="btn btn-outline-primary">
                    Learn More About Us <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Testimonials Section -->


<!-- Newsletter Section -->
<div class="py-5 bg-primary text-white">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <h2 class="mb-3">Subscribe to Our Newsletter</h2>
                <p class="mb-4">Stay updated with our latest products, promotions, and recipes</p>
                <form class="row g-3 justify-content-center">
                    <div class="col-md-8">
                        <div class="input-group">
                            <input type="email" class="form-control" placeholder="Your email address" aria-label="Your email address">
                            <button class="btn btn-light" type="button">Subscribe</button>
                        </div>
                    </div>
                </form>
                <p class="mt-3 small">By subscribing, you agree to receive marketing emails from Mad Krapow</p>
            </div>
        </div>
    </div>
</div>

<style>
.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.category-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.section-title {
    position: relative;
    padding-bottom: 15px;
    margin-bottom: 15px;
}

.section-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 50px;
    height: 3px;
    background-color: var(--bs-primary);
}
</style>
@endsection
