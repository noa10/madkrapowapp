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
    .btn-facebook-loading {
        position: relative;
        pointer-events: none;
    }
    .btn-facebook-loading:after {
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
    .btn-tiktok-loading {
        position: relative;
        pointer-events: none;
    }
    .btn-tiktok-loading:after {
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
                            
                            <a href="{{ route('auth.facebook') }}" id="facebook-login-btn" class="btn btn-outline-primary mt-2" onclick="startFacebookLogin(event)">
                                <i class="fab fa-facebook"></i> Login with Facebook
                            </a>
                            
                            <a href="{{ route('auth.tiktok') }}" id="tiktok-login-btn" class="btn btn-outline-dark mt-2" onclick="startTikTokLogin(event)">
                                <i class="fab fa-tiktok"></i> Login with TikTok
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
        
        // Set a timeout to reset the button if the redirect doesn't happen
        setTimeout(function() {
            if (document.body.contains(button)) {
                button.classList.remove('btn-google-loading');
                button.innerHTML = '<i class="fab fa-google"></i> Login with Google';
            }
        }, 10000); // 10 seconds timeout
    }

    function startFacebookLogin(event) {
        const button = event.currentTarget;
        button.classList.add('btn-facebook-loading');
        button.innerHTML = '<i class="fab fa-facebook"></i> Connecting to Facebook...';
        
        // Set a timeout to reset the button if the redirect doesn't happen
        setTimeout(function() {
            if (document.body.contains(button)) {
                button.classList.remove('btn-facebook-loading');
                button.innerHTML = '<i class="fab fa-facebook"></i> Login with Facebook';
            }
        }, 10000); // 10 seconds timeout
    }
    
    function startTikTokLogin(event) {
        const button = event.currentTarget;
        button.classList.add('btn-tiktok-loading');
        button.innerHTML = '<i class="fab fa-tiktok"></i> Connecting to TikTok...';
        
        // Set a timeout to reset the button if the redirect doesn't happen
        setTimeout(function() {
            if (document.body.contains(button)) {
                button.classList.remove('btn-tiktok-loading');
                button.innerHTML = '<i class="fab fa-tiktok"></i> Login with TikTok';
            }
        }, 10000); // 10 seconds timeout
    }

    // Check if we're returning from a social auth attempt
    document.addEventListener('DOMContentLoaded', function() {
        if ({{ session('google_auth_in_progress') ? 'true' : 'false' }}) {
            const googleBtn = document.getElementById('google-login-btn');
            googleBtn.classList.add('btn-google-loading');
            googleBtn.innerHTML = '<i class="fab fa-google"></i> Authenticating with Google...';
            
            // Set a timeout to reset the button if the authentication doesn't complete
            setTimeout(function() {
                if (document.body.contains(googleBtn)) {
                    googleBtn.classList.remove('btn-google-loading');
                    googleBtn.innerHTML = '<i class="fab fa-google"></i> Login with Google';
                }
            }, 10000); // 10 seconds timeout
        }
        
        if ({{ session('facebook_auth_in_progress') ? 'true' : 'false' }}) {
            const facebookBtn = document.getElementById('facebook-login-btn');
            facebookBtn.classList.add('btn-facebook-loading');
            facebookBtn.innerHTML = '<i class="fab fa-facebook"></i> Authenticating with Facebook...';
            
            // Set a timeout to reset the button if the authentication doesn't complete
            setTimeout(function() {
                if (document.body.contains(facebookBtn)) {
                    facebookBtn.classList.remove('btn-facebook-loading');
                    facebookBtn.innerHTML = '<i class="fab fa-facebook"></i> Login with Facebook';
                }
            }, 10000); // 10 seconds timeout
        }
        
        if ({{ session('tiktok_auth_in_progress') ? 'true' : 'false' }}) {
            const tiktokBtn = document.getElementById('tiktok-login-btn');
            tiktokBtn.classList.add('btn-tiktok-loading');
            tiktokBtn.innerHTML = '<i class="fab fa-tiktok"></i> Authenticating with TikTok...';
            
            // Set a timeout to reset the button if the authentication doesn't complete
            setTimeout(function() {
                if (document.body.contains(tiktokBtn)) {
                    tiktokBtn.classList.remove('btn-tiktok-loading');
                    tiktokBtn.innerHTML = '<i class="fab fa-tiktok"></i> Login with TikTok';
                }
            }, 10000); // 10 seconds timeout
        }
    });
</script>
@endsection