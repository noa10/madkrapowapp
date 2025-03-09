@extends('layouts.app')

@section('content')
<div class="container py-5">
    <h1 class="text-4xl font-bold mb-6 text-center">Contact Us</h1>
    <p class="text-xl text-gray-600 max-w-2xl mx-auto mb-12 text-center">
        We'd love to hear from you! Reach out with any questions or feedback.
    </p>
    
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="row max-w-6xl mx-auto">
        <!-- Contact Information -->
        <div class="col-lg-6 mb-5 mb-lg-0">
            <h2 class="text-2xl font-bold mb-4">Get In Touch</h2>
            
            <div class="mb-5">
                <div class="d-flex mb-4">
                    <div class="me-3 mt-1">
                        <i class="bi bi-envelope text-primary fs-4"></i>
                    </div>
                    <div>
                        <h3 class="fs-5 fw-semibold">Email</h3>
                        <p class="text-muted">info@madkrapow.com</p>
                    </div>
                </div>
                
                <div class="d-flex mb-4">
                    <div class="me-3 mt-1">
                        <i class="bi bi-telephone text-primary fs-4"></i>
                    </div>
                    <div>
                        <h3 class="fs-5 fw-semibold">Phone</h3>
                        <p class="text-muted">+60 12-345-6789</p>
                    </div>
                </div>
                
                <div class="d-flex mb-4">
                    <div class="me-3 mt-1">
                        <i class="bi bi-geo-alt text-primary fs-4"></i>
                    </div>
                    <div>
                        <h3 class="fs-5 fw-semibold">Address</h3>
                        <p class="text-muted">123 Thai Street, Kuala Lumpur, Malaysia</p>
                    </div>
                </div>
            </div>
            
            <div class="mt-5">
                <h3 class="fs-5 fw-semibold mb-3">Business Hours</h3>
                <div class="mb-2">
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Monday - Friday:</span>
                        <span>10:00 AM - 10:00 PM</span>
                    </div>
                </div>
                <div class="mb-2">
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Saturday - Sunday:</span>
                        <span>11:00 AM - 11:00 PM</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Contact Form -->
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h2 class="text-2xl font-bold mb-4">Send Us a Message</h2>
                    
                    <form method="POST" action="{{ route('contact.process') }}">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                id="name" name="name" value="{{ old('name') }}" placeholder="Your name" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                id="email" name="email" value="{{ old('email') }}" placeholder="Your email" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control @error('subject') is-invalid @enderror" 
                                id="subject" name="subject" value="{{ old('subject') }}" placeholder="Message subject" required>
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-4">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control @error('message') is-invalid @enderror" 
                                id="message" name="message" rows="5" placeholder="Your message" required>{{ old('message') }}</textarea>
                            @error('message')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 d-flex justify-content-center align-items-center">
                            <i class="bi bi-send me-2"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
@endsection