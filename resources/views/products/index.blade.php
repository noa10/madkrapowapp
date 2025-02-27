@extends('layouts.app')

@section('title', 'Products - Mad Krapow')

@section('content')
<div class="container py-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="mb-0">Our Products</h1>
            <p class="text-muted">Discover our authentic Thai cuisine</p>
        </div>
        <div class="col-md-4">
            <div class="input-group">
                <input type="text" class="form-control" placeholder="Search products..." id="search-input">
                <button class="btn btn-outline-primary" type="button" id="search-button">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                        <div class="mb-2 mb-md-0">
                            <span class="text-muted">Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} of {{ $products->total() }} products</span>
                        </div>
                        
                        <div class="d-flex align-items-center">
                            <label for="sort-by" class="me-2 mb-0">Sort by:</label>
                            <select class="form-select form-select-sm" id="sort-by" style="width: auto;">
                                <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Newest</option>
                                <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>Price: Low to High</option>
                                <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>Price: High to Low</option>
                                <option value="popularity" {{ request('sort') == 'popularity' ? 'selected' : '' }}>Popularity</option>
                                <option value="rating" {{ request('sort') == 'rating' ? 'selected' : '' }}>Rating</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-3 mb-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Categories</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="{{ route('products.index') }}" class="list-group-item list-group-item-action {{ !request('category') ? 'active' : '' }}">
                            All Categories
                        </a>
                        @foreach($categories as $category)
                            <a href="{{ route('products.index', ['category' => $category->slug]) }}" class="list-group-item list-group-item-action {{ request('category') == $category->slug ? 'active' : '' }}">
                                {{ $category->name }}
                                <span class="badge bg-secondary float-end">{{ $category->products_count }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Price Range</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="price-min" class="form-label">Min Price: RM <span id="price-min-value">{{ request('min_price', 0) }}</span></label>
                        <input type="range" class="form-range" id="price-min" min="0" max="100" step="5" value="{{ request('min_price', 0) }}">
                    </div>
                    <div class="mb-3">
                        <label for="price-max" class="form-label">Max Price: RM <span id="price-max-value">{{ request('max_price', 100) }}</span></label>
                        <input type="range" class="form-range" id="price-max" min="0" max="100" step="5" value="{{ request('max_price', 100) }}">
                    </div>
                    <button id="apply-price-filter" class="btn btn-primary w-100">Apply Filter</button>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Availability</h5>
                </div>
                <div class="card-body">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="in-stock" {{ request('in_stock') ? 'checked' : '' }}>
                        <label class="form-check-label" for="in-stock">
                            In Stock Only
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-9">
            @if($products->count() > 0)
                <div class="row">
                    @foreach($products as $product)
                        <div class="col-md-4 col-6 mb-4">
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
                
                <div class="d-flex justify-content-center mt-4">
                    {{ $products->links() }}
                </div>
            @else
                <div class="card py-5">
                    <div class="card-body text-center">
                        <i class="bi bi-search display-1 text-muted mb-3"></i>
                        <h3>No Products Found</h3>
                        <p class="mb-4">We couldn't find any products matching your criteria.</p>
                        <a href="{{ route('products.index') }}" class="btn btn-primary">
                            <i class="bi bi-arrow-left me-1"></i> Clear Filters
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Price range sliders
        const priceMinSlider = document.getElementById('price-min');
        const priceMaxSlider = document.getElementById('price-max');
        const priceMinValue = document.getElementById('price-min-value');
        const priceMaxValue = document.getElementById('price-max-value');
        
        priceMinSlider.addEventListener('input', function() {
            priceMinValue.textContent = this.value;
            if (parseInt(priceMinSlider.value) > parseInt(priceMaxSlider.value)) {
                priceMaxSlider.value = priceMinSlider.value;
                priceMaxValue.textContent = priceMaxSlider.value;
            }
        });
        
        priceMaxSlider.addEventListener('input', function() {
            priceMaxValue.textContent = this.value;
            if (parseInt(priceMaxSlider.value) < parseInt(priceMinSlider.value)) {
                priceMinSlider.value = priceMaxSlider.value;
                priceMinValue.textContent = priceMinSlider.value;
            }
        });
        
        // Apply price filter
        document.getElementById('apply-price-filter').addEventListener('click', function() {
            applyFilters();
        });
        
        // Sort by change
        document.getElementById('sort-by').addEventListener('change', function() {
            applyFilters();
        });
        
        // In stock filter
        document.getElementById('in-stock').addEventListener('change', function() {
            applyFilters();
        });
        
        // Search functionality
        document.getElementById('search-button').addEventListener('click', function() {
            applyFilters();
        });
        
        document.getElementById('search-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });
        
        function applyFilters() {
            const minPrice = priceMinSlider.value;
            const maxPrice = priceMaxSlider.value;
            const sortBy = document.getElementById('sort-by').value;
            const inStock = document.getElementById('in-stock').checked;
            const searchQuery = document.getElementById('search-input').value;
            
            let url = new URL(window.location.href);
            let params = new URLSearchParams(url.search);
            
            // Update or add parameters
            params.set('min_price', minPrice);
            params.set('max_price', maxPrice);
            params.set('sort', sortBy);
            
            if (inStock) {
                params.set('in_stock', '1');
            } else {
                params.delete('in_stock');
            }
            
            if (searchQuery) {
                params.set('search', searchQuery);
            } else {
                params.delete('search');
            }
            
            // Keep category if it exists
            if (!params.has('category') && url.searchParams.has('category')) {
                params.set('category', url.searchParams.get('category'));
            }
            
            // Redirect to the filtered URL
            window.location.href = `${url.pathname}?${params.toString()}`;
        }
    });
</script>
@endsection
@endsection
