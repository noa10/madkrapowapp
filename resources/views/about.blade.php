@extends('layouts.app')

@section('content')
<!-- Hero Section -->
<section class="bg-light py-5">
    <div class="container text-center py-5">
        <h1 class="display-4 fw-bold mb-4">Crafting Authentic Thai Flavors</h1>
        <p class="fs-4 text-muted mx-auto" style="max-width: 600px">Where tradition meets innovation in every bite.</p>
    </div>
</section>

<!-- Our Story Section -->
<section class="py-5">
    <div class="container">
        <h2 class="fs-1 fw-bold mb-5 text-center">Our Story</h2>
        <div class="mx-auto" style="max-width: 800px">
            <p class="text-muted mb-4">
                Founded in 2023, Mad Krapow brings the vibrant and authentic flavors of Thai cuisine directly to your doorstep in Shah Alam, Selangor, and across Malaysia. What began as a passionate endeavor to share cherished family recipes has quickly blossomed into a beloved culinary destination.
            </p>
            <p class="text-muted mb-4">
                Our journey started with a simple desire: to make the rich and diverse tastes of Thailand accessible to everyone. We believe that great food should be made with love, using only the freshest ingredients and time-honored cooking techniques. This commitment to quality and authenticity is at the heart of everything we do at Mad Krapow.
            </p>
            <p class="text-muted">
                We're more than just a food delivery service; we're a culinary bridge, connecting you to the heart of Thai gastronomy. From our aromatic ready-to-cook pastes to our delectable Thai dishes, each product is crafted with meticulous attention to detail and a deep respect for tradition.
            </p>
        </div>
    </div>
</section>

<!-- Core Values Section -->
<section class="py-5 bg-light">
    <div class="container py-3">
        <h2 class="fs-1 fw-bold mb-5 text-center">Our Core Values</h2>
        
        <div class="row g-4">
            <!-- Fresh Ingredients -->
            <div class="col-md-4">
                <div class="card h-100 shadow border-0">
                    <div class="card-body text-center p-4">
                        <div class="d-flex justify-content-center mb-4">
                            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                                <i class="bi bi-check2 text-white fs-2"></i>
                            </div>
                        </div>
                        <h3 class="fs-4 fw-bold mb-3">Fresh Ingredients</h3>
                        <p class="text-muted">
                            Daily-sourced local produce and premium meats curated by our master chefs.
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Authentic Recipes -->
            <div class="col-md-4">
                <div class="card h-100 shadow border-0">
                    <div class="card-body text-center p-4">
                        <div class="d-flex justify-content-center mb-4">
                            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                                <i class="bi bi-star-fill text-white fs-2"></i>
                            </div>
                        </div>
                        <h3 class="fs-4 fw-bold mb-3">Authentic Recipes</h3>
                        <p class="text-muted">
                            Centuries-old family recipes preserved through generations of Thai culinary tradition.
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Sustainable Sourcing -->
            <div class="col-md-4">
                <div class="card h-100 shadow border-0">
                    <div class="card-body text-center p-4">
                        <div class="d-flex justify-content-center mb-4">
                            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                                <i class="bi bi-shield-fill-check text-white fs-2"></i>
                            </div>
                        </div>
                        <h3 class="fs-4 fw-bold mb-3">Sustainable Sourcing</h3>
                        <p class="text-muted">
                            Ethically sourced ingredients supporting local farmers and communities.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Meet Our Chefs Section -->
<section class="py-5">
    <div class="container py-3">
        <h2 class="fs-1 fw-bold mb-3 text-center">Meet Our Chef</h2>
        <p class="fs-5 text-muted text-center mb-5">The culinary expert behind our authentic flavors</p>
        
        <div class="row justify-content-center">
            <!-- Master Chef -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow border-0">
                    <div class="card-body text-center p-4">
                        <div class="mx-auto mb-4 rounded-circle overflow-hidden bg-light" style="width: 128px; height: 128px;">
                            <img 
                                src="https://images.unsplash.com/photo-1577219491135-ce391730fb2c?ixlib=rb-1.2.1&auto=format&fit=crop&w=300&q=80" 
                                alt="Master Chef" 
                                class="w-100 h-100 object-fit-cover"
                            >
                        </div>
                        <h3 class="fs-4 fw-bold">M.Han</h3>
                        <p class="text-muted">Master Chef</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Questions Section -->
<section class="py-5 bg-danger bg-opacity-10">
    <div class="container text-center py-3">
        <h2 class="fs-2 fw-bold mb-4">Have Questions?</h2>
        <a href="{{ route('contact') }}" class="btn btn-primary btn-lg px-5">
            Contact Us
        </a>
    </div>
</section>
@endsection

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
    .object-fit-cover {
        object-fit: cover;
    }
</style>
@endsection