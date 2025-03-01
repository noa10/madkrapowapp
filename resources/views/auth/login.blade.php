@extends('layouts.app')

@section('title', 'Login - Mad Krapow')

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
                    <h4 class="mb-0">Login</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                            @error('email')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">
                            @error('password')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label" for="remember">
                                Remember Me
                            </label>
                        </div>
                        
                        <div class="d-grid gap-2 mb-3">
                            <button type="submit" class="btn btn-primary">
                                {{ __('Login') }}
                            </button>
                            
                            <a href="{{ route('auth.google') }}" id="google-login-btn" class="btn btn-outline-danger mt-2" onclick="startGoogleLogin(event)">
                                <i class="fab fa-google"></i> Login with Google
                            </a>
                            
                            @if (Route::has('password.request'))
                                <div class="text-center mt-2">
                                    <a href="{{ route('password.request') }}">
                                        {{ __('Forgot Your Password?') }}
                                    </a>
                                </div>
                            @endif
                        </div>
                        
                        <div class="mt-3 text-center">
                            <p>Don't have an account? <a href="{{ route('register') }}">Register here</a></p>
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
    function startGoogleLogin(event) {
        const button = event.currentTarget;
        button.classList.add('btn-google-loading');
        button.innerHTML = '<i class="fab fa-google"></i> Connecting to Google...';
        // Continue with the redirect (don't prevent default)
    }

    // Check if we're returning from a Google auth attempt
    document.addEventListener('DOMContentLoaded', function() {
        if ({{ session('google_auth_in_progress') ? 'true' : 'false' }}) {
            document.getElementById('google-login-btn').classList.add('btn-google-loading');
            document.getElementById('google-login-btn').innerHTML = '<i class="fab fa-google"></i> Authenticating with Google...';
        }
    });
</script>
@endsection