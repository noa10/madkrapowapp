@extends('layouts.app')

@section('content')
<div class="about-page-bg">
    <div class="container py-5">
        {{-- Hero Section --}}
        <div class="text-center mb-5">
            <h1 class="display-4 text-dark mb-4">
                Crafting Authentic Thai Flavors
            </h1>
            <p class="lead text-secondary mx-auto" style="max-width: 600px">
                Where tradition meets innovation in every bite
            </p>
        </div>

        {{-- Introduction Section --}}
        <section class="mb-5">
            <h2 class="h3 text-dark mb-3">Our Story</h2>
            <p class="text-muted mb-4">
                Founded in 2023, Madkrapow brings authentic Thai street food flavors to your doorstep.
                What started as a small family recipe has grown into a beloved local institution, thanks
                to our commitment to quality ingredients and traditional cooking methods.
            </p>
        </section>

        {{-- Values Section --}}
        <section class="mb-5">
            <div class="row justify-content-center">
                <h2 class="text-center h2 mb-5">Our Core Values</h2>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body text-center">
                                <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                    <svg class="svg-icon text-danger w-25" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <h3 class="h4 mb-2">Fresh Ingredients</h3>
                                <p class="text-muted">Daily-sourced local produce and premium meats curated by our master chefs.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body text-center">
                                <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                    <svg class="svg-icon text-danger w-25" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                    </svg>
                                </div>
                                <h3 class="h4 mb-2">Authentic Recipes</h3>
                                <p class="text-muted">Centuries-old family recipes preserved through generations of Thai culinary tradition.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body text-center">
                                <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                    <svg class="svg-icon text-danger w-25" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/>
                                    </svg>
                                </div>
                                <h3 class="h4 mb-2">Sustainable Sourcing</h3>
                                <p class="text-muted">Ethically sourced ingredients supporting local farmers and communities.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Chefs Section --}}
        <section class="bg-light py-5">
            <div class="container">
                <div class="row text-center mb-4">
                    <div class="col-12">
                        <h2 class="display-5">Meet Our Chefs</h2>
                        <p class="lead">The culinary experts behind our authentic flavors</p>
                    </div>
                </div>
                
                <div class="row g-4">
                    {{-- Chef 1 --}}
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm">
                            <div class="ratio ratio-1x1">
                                <img src="{{ asset('images/chef1.jpg') }}" alt="Head Chef" class="card-img-top object-fit-cover">
                            </div>
                            <div class="card-body text-center">
                                <h3 class="h5 mb-2">Somchai P.</h3>
                                <p class="text-madkrapow-red mb-3">Master Chef</p>
                                <div class="d-flex justify-content-center gap-2">
                                    {{-- Social links would go here --}}
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Chef 2 --}}
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm">
                            <div class="ratio ratio-1x1">
                                <img src="{{ asset('images/chef2.jpg') }}" alt="Sous Chef" class="card-img-top object-fit-cover">
                            </div>
                            <div class="card-body text-center">
                                <h3 class="h5 mb-2">Nonglak S.</h3>
                                <p class="text-madkrapow-red mb-3">Sous Chef</p>
                                <div class="d-flex justify-content-center gap-2">
                                    {{-- Social links would go here --}}
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Chef 3 --}}
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm">
                            <div class="ratio ratio-1x1">
                                <img src="{{ asset('images/chef3.jpg') }}" alt="Pastry Chef" class="card-img-top object-fit-cover">
                            </div>
                            <div class="card-body text-center">
                                <h3 class="h5 mb-2">Pimchanok W.</h3>
                                <p class="text-madkrapow-red mb-3">Pastry Chef</p>
                                <div class="d-flex justify-content-center gap-2">
                                   {{-- Social links would go here --}}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- CTA Section --}}
        <section class="bg-danger bg-opacity-10 p-5 text-center rounded-3 my-5">
            <h2 class="h4 mb-4">Have Questions?</h2>
            <a href="{{ route('contact') }}" class="btn btn-danger btn-lg px-5">
                Contact Us
            </a>
        </section>
    </div>
</div>
@endsection