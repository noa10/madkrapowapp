<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Your Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 40px;
        }
        .form-container {
            max-width: 500px;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .provider-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
        .tiktok-icon {
            color: #000;
        }
        .form-title {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <div class="text-center">
                @if($provider == 'tiktok')
                    <div class="provider-icon tiktok-icon">
                        <i class="fab fa-tiktok"></i>
                    </div>
                    <h2 class="form-title">Complete Your TikTok Registration</h2>
                @else
                    <h2 class="form-title">Complete Your {{ ucfirst($provider) }} Registration</h2>
                @endif
                
                <p class="mb-4">We just need your email address to complete your account setup.</p>
            </div>
            
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            <form method="POST" action="{{ route('social.email.process', ['provider' => $provider]) }}">
                @csrf
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required>
                    <div class="form-text">We'll never share your email with anyone else.</div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Complete Registration</button>
                </div>
                
                <div class="mt-3 text-center">
                    <a href="{{ route('login') }}">Cancel and return to login</a>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" integrity="sha512-fD9DI5bZwQxOi7MhYWnnNPlvXdp/2Pj3XSTRrFs5FQa4mizyGLnJcN6tuvUS6LbmgN1ut+XGSABKvjN0H6Aoow==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</body>
</html> 