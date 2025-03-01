@extends('layouts.app')

@section('title', 'Register - Mad Krapow')

@section('styles')
<style>
    .btn-google-loading {
        position: relative;
        pointer-events: none;
    }
    .btn-google-loading:after {
        content: '';
        display: inline-block;
        width: 1rem;
        height: 1rem;
        border: 2px solid rgba(255,255,255,0.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 1s ease-in-out infinite;
        position: absolute;
        right: 1rem;
        top: calc(50% - 0.5rem);
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>
@endsection

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Register</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('register') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
                            @error('name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">
                            @error('email')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">
                            @error('password')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password-confirm" class="form-label">Confirm Password</label>
                            <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address (Optional)</label>
                            <textarea id="address" class="form-control @error('address') is-invalid @enderror" name="address" rows="3">{{ old('address') }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                Register
                            </button>
                            
                            <a href="{{ route('auth.google') }}" id="google-signup-btn" class="btn btn-outline-danger mt-2" onclick="startGoogleSignup(event)">
                                <i class="fab fa-google"></i> Sign up with Google
                            </a>
                        </div>

                        <div class="mt-3 text-center">
                            <p>Already have an account? <a href="{{ route('login') }}">Login here</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function startGoogleSignup(event) {
        const button = event.currentTarget;
        button.classList.add('btn-google-loading');
        button.innerHTML = '<i class="fab fa-google"></i> Connecting to Google...';
        // Continue with the redirect (don't prevent default)
    }

    // Check if we're returning from a Google auth attempt
    document.addEventListener('DOMContentLoaded', function() {
        if ({{ session('google_auth_in_progress') ? 'true' : 'false' }}) {
            document.getElementById('google-signup-btn').classList.add('btn-google-loading');
            document.getElementById('google-signup-btn').innerHTML = '<i class="fab fa-google"></i> Authenticating with Google...';
        }
    });
</script>
@endsection
