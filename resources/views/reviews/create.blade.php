@extends('layouts.app')

@section('title', 'Write a Review - Mad Krapow')

@section('content')
<div class="container py-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('products.show', $product->product_id) }}">{{ $product->product_name }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Write a Review</li>
        </ol>
    </nav>
    
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Write a Review</h4>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-4">
                        <div class="me-3">
                            @if($product->image_path)
                                <img src="{{ asset('storage/' . $product->image_path) }}" class="img-fluid rounded" alt="{{ $product->product_name }}" style="max-width: 100px;">
                            @else
                                <img src="/pesmadkrapow.png" class="img-fluid rounded" alt="{{ $product->product_name }}" style="max-width: 100px;">
                            @endif
                        </div>
                        <div>
                            <h5 class="mb-1">{{ $product->product_name }}</h5>
                            <p class="text-muted mb-0">You're reviewing: {{ $product->product_name }}</p>
                        </div>
                    </div>
                    
                    <form action="{{ route('reviews.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->product_id }}">
                        
                        <div class="mb-4">
                            <label class="form-label">Rating</label>
                            <div class="rating-input">
                                <div class="d-flex align-items-center">
                                    <div class="rating-stars me-3">
                                        <input type="radio" id="rating-5" name="rating" value="5" class="visually-hidden" {{ old('rating') == 5 ? 'checked' : '' }}>
                                        <label for="rating-5" class="rating-label" title="5 stars">
                                            <i class="bi bi-star-fill"></i>
                                        </label>
                                        
                                        <input type="radio" id="rating-4" name="rating" value="4" class="visually-hidden" {{ old('rating') == 4 ? 'checked' : '' }}>
                                        <label for="rating-4" class="rating-label" title="4 stars">
                                            <i class="bi bi-star-fill"></i>
                                        </label>
                                        
                                        <input type="radio" id="rating-3" name="rating" value="3" class="visually-hidden" {{ old('rating') == 3 ? 'checked' : '' }}>
                                        <label for="rating-3" class="rating-label" title="3 stars">
                                            <i class="bi bi-star-fill"></i>
                                        </label>
                                        
                                        <input type="radio" id="rating-2" name="rating" value="2" class="visually-hidden" {{ old('rating') == 2 ? 'checked' : '' }}>
                                        <label for="rating-2" class="rating-label" title="2 stars">
                                            <i class="bi bi-star-fill"></i>
                                        </label>
                                        
                                        <input type="radio" id="rating-1" name="rating" value="1" class="visually-hidden" {{ old('rating') == 1 ? 'checked' : '' }}>
                                        <label for="rating-1" class="rating-label" title="1 star">
                                            <i class="bi bi-star-fill"></i>
                                        </label>
                                    </div>
                                    <span id="rating-text" class="text-muted">Select a rating</span>
                                </div>
                                @error('rating')
                                    <div class="text-danger mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="comment" class="form-label">Review</label>
                            <textarea id="comment" name="comment" class="form-control @error('comment') is-invalid @enderror" rows="5" placeholder="Share your experience with this product...">{{ old('comment') }}</textarea>
                            @error('comment')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                            <div class="form-text">
                                <small class="text-muted">Your review will help other customers make better purchase decisions. Be honest and specific in your feedback.</small>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                Submit Review
                            </button>
                            <a href="{{ route('products.show', $product->product_id) }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.rating-stars {
    display: flex;
    flex-direction: row-reverse;
    font-size: 1.5rem;
}

.rating-label {
    color: #e9ecef;
    cursor: pointer;
    margin-right: 5px;
}

.rating-input input:checked ~ label,
.rating-input input:hover ~ label {
    color: #ffc107;
}

.rating-input input:hover ~ label {
    color: #ffdb70;
}

.visually-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    margin: -1px;
    padding: 0;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    border: 0;
}
</style>

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ratingInputs = document.querySelectorAll('input[name="rating"]');
        const ratingText = document.getElementById('rating-text');
        const ratingTexts = [
            'Select a rating',
            'Poor',
            'Fair',
            'Good',
            'Very Good',
            'Excellent'
        ];
        
        ratingInputs.forEach(input => {
            input.addEventListener('change', function() {
                const value = parseInt(this.value);
                ratingText.textContent = ratingTexts[value];
            });
        });
        
        // Set initial text if a rating is already selected
        const checkedRating = document.querySelector('input[name="rating"]:checked');
        if (checkedRating) {
            const value = parseInt(checkedRating.value);
            ratingText.textContent = ratingTexts[value];
        }
    });
</script>
@endsection
@endsection
