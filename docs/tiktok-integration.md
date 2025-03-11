# TikTok Login Integration

This document provides comprehensive documentation for integrating TikTok Login with the MadKrapow application.

## Overview

TikTok Login integration allows users to authenticate using their TikTok accounts, enabling single sign-on functionality and access to basic TikTok profile information.

## Prerequisites

1. TikTok Developer Account
2. Registered TikTok application with Login Kit enabled
3. Laravel application with Socialite package installed

## Configuration

### TikTok Developer Portal Setup

1. Create an app in the [TikTok Developer Portal](https://developers.tiktok.com)
2. Enable Login Kit for your app
3. Configure your app with the following settings:
   - Add your domain to the allowed domains list
   - Set the correct redirect URI: `https://yourdomain.com/auth/tiktok/callback`
   - Request the `user.info.basic` scope

### Important Configuration Values

- **App ID**: Your numerical App ID (e.g., `7480055128306714630`)
- **Client Key**: Your client key (also known as app key) (e.g., `sbawslovnjuabyqhci`)
- **Client Secret**: Your app secret key
- **Redirect URI**: The callback URL for OAuth flow completion

### Environment Configuration

Add the following to your `.env` file:

```
TIKTOK_CLIENT_KEY=your_client_key
TIKTOK_CLIENT_SECRET=your_client_secret
TIKTOK_REDIRECT_URI=https://yourdomain.com/auth/tiktok/callback
```

### Laravel Configuration

Update your `config/services.php` file to include TikTok configuration:

```php
'tiktok' => [
    'client_id' => env('TIKTOK_CLIENT_KEY'),
    'client_secret' => env('TIKTOK_CLIENT_SECRET'),
    'redirect' => env('TIKTOK_REDIRECT_URI'),
],
```

## Implementation

### Routes

Add these routes to your `routes/web.php` file:

```php
// TikTok Authentication Routes
Route::get('auth/tiktok', [App\Http\Controllers\Auth\TikTokController::class, 'redirect'])->name('auth.tiktok');
Route::get('auth/tiktok/callback', [App\Http\Controllers\Auth\TikTokController::class, 'callback']);
```

### TikTok Service Provider

Create a custom TikTok provider for Socialite in `app/Services/TikTokProvider.php`:

```php
<?php

namespace App\Services;

use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TikTokProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['user.info.basic'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        // Generate a code verifier
        $codeVerifier = Str::random(128);
        
        // Store the code verifier in session for later use
        session(['tiktok_code_verifier' => $codeVerifier]);
        
        // Generate code challenge (SHA256 hash of code verifier, base64url encoded)
        $codeChallenge = strtr(rtrim(
            base64_encode(hash('sha256', $codeVerifier, true)),
            '='
        ), '+/', '-_');
        
        // We'll handle the URL building ourselves to ensure client_key is used
        $url = 'https://www.tiktok.com/v2/auth/authorize/';
        
        // Create query params manually
        $query = http_build_query([
            'client_key' => $this->clientId,
            'scope' => $this->formatScopes($this->scopes, $this->scopeSeparator),
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUrl,
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);
        
        $fullUrl = $url . '?' . $query;
        
        // Log the full URL and parameters
        Log::info('TikTok Auth URL', [
            'url' => $fullUrl,
            'client_key' => $this->clientId,
            'redirect_uri' => $this->redirectUrl
        ]);
        
        return $fullUrl;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://open.tiktokapis.com/v2/oauth/token/';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        try {
            Log::info('Getting TikTok user info with token', ['token_prefix' => substr($token, 0, 10) . '...']);
            
            $response = $this->getHttpClient()->get('https://open.tiktokapis.com/v2/user/info/', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'query' => [
                    'fields' => 'open_id,union_id,avatar_url,display_name,username,is_verified',
                ],
            ]);

            $userData = json_decode($response->getBody(), true);
            Log::info('TikTok User Data Response', ['data' => $userData]);
            
            // Check if the response contains the expected data structure
            if (!isset($userData['data']) || !isset($userData['data']['user'])) {
                Log::error('TikTok user data missing expected structure', ['response' => $userData]);
            }
            
            return $userData;
        } catch (\Exception $e) {
            Log::error('TikTok getUserByToken Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        Log::info('Mapping TikTok user to object', ['raw_user' => $user]);
        
        // Extract user data from the nested structure
        $userData = isset($user['data']) && isset($user['data']['user']) 
            ? $user['data']['user'] 
            : [];
        
        // If we don't have the expected data, log an error
        if (empty($userData)) {
            Log::error('TikTok user data is empty or missing expected structure', [
                'user' => $user
            ]);
        }
        
        return (new User)->setRaw($user)->map([
            'id' => $userData['open_id'] ?? null,
            'nickname' => $userData['display_name'] ?? null,
            'name' => $userData['display_name'] ?? null,
            'email' => null, // TikTok doesn't provide email
            'avatar' => $userData['avatar_url'] ?? null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        // Get code verifier from session
        $codeVerifier = session('tiktok_code_verifier');
        
        // Clear code verifier from session
        session()->forget('tiktok_code_verifier');
        
        $fields = [
            'client_key' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUrl,
            'code_verifier' => $codeVerifier,
        ];
        
        Log::info('TikTok Token Fields', ['fields' => $fields]);
        
        return $fields;
    }
}
```

### TikTok Controller

Create a controller to handle TikTok authentication in `app/Http/Controllers/Auth/TikTokController.php`:

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SocialAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TikTokController extends Controller
{
    /**
     * Redirect the user to the TikTok authentication page.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirect()
    {
        $state = Str::random(40);
        $codeVerifier = Str::random(128);
        
        // Store state and code verifier in session for validation when user returns
        session(['tiktok_state' => $state]);
        session(['tiktok_code_verifier' => $codeVerifier]);
        
        // Generate code challenge (SHA256 hash of code verifier, base64url encoded)
        $codeChallenge = strtr(rtrim(
            base64_encode(hash('sha256', $codeVerifier, true)),
            '='
        ), '+/', '-_');
        
        // Get client key and redirect URI from config (services.php) which pulls from .env
        $clientKey = config('services.tiktok.client_id');
        $redirectUri = config('services.tiktok.redirect');
        
        // Log the configuration for debugging
        Log::info('TikTok Auth Redirect Configuration', [
            'client_key' => $clientKey,
            'redirect_uri' => $redirectUri
        ]);
        
        // Build the URL manually to ensure client_key is included
        $url = 'https://www.tiktok.com/v2/auth/authorize';
        $query = http_build_query([
            'client_key' => $clientKey,
            'scope' => 'user.info.basic',
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);
        
        // Set the auth in progress flag
        session(['tiktok_auth_in_progress' => true]);
        
        // Log the final URL
        $finalUrl = $url . '?' . $query;
        Log::info('TikTok Auth Redirect URL', ['url' => $finalUrl]);
        
        return redirect($finalUrl);
    }
    
    /**
     * Handle the callback from TikTok.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function callback(Request $request)
    {
        // Clear the in-progress flag
        session()->forget('tiktok_auth_in_progress');
        
        // Verify state to prevent CSRF attacks
        if ($request->state !== session('tiktok_state')) {
            return redirect()->route('register')
                ->with('error', 'Invalid state parameter. Authentication failed.');
        }
        
        // Get the code verifier from session
        $codeVerifier = session('tiktok_code_verifier');
        
        // Clear the state and code verifier from session
        session()->forget('tiktok_state');
        session()->forget('tiktok_code_verifier');
        
        // Check for error response
        if ($request->has('error')) {
            Log::error('TikTok authentication error', [
                'error' => $request->error,
                'error_description' => $request->error_description
            ]);
            
            return redirect()->route('register')
                ->with('error', 'TikTok authentication failed: ' . $request->error_description);
        }
        
        // Exchange authorization code for access token
        try {
            // Get client key, secret and redirect URI from config (services.php) which pulls from .env
            $clientKey = config('services.tiktok.client_id');
            $clientSecret = config('services.tiktok.client_secret');
            $redirectUri = config('services.tiktok.redirect');
            
            // Log the token exchange request
            Log::info('TikTok token exchange request', [
                'code' => substr($request->code, 0, 10) . '...',
                'redirect_uri' => $redirectUri
            ]);
            
            $response = Http::asForm()->post('https://open.tiktokapis.com/v2/oauth/token/', [
                'client_key' => $clientKey,
                'client_secret' => $clientSecret,
                'code' => $request->code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $redirectUri,
                'code_verifier' => $codeVerifier,
            ]);
            
            $tokenData = $response->json();
            
            // Log the token response (without exposing the full token)
            Log::info('TikTok token exchange response', [
                'success' => isset($tokenData['access_token']),
                'has_open_id' => isset($tokenData['open_id']),
                'error' => $tokenData['error'] ?? null
            ]);
            
            if (!isset($tokenData['access_token'])) {
                Log::error('TikTok token exchange failed', ['response' => $tokenData]);
                return redirect()->route('register')
                    ->with('error', 'Failed to obtain access token from TikTok.');
            }
            
            // Add this right after getting the token
            Log::debug('TikTok token data', [
                'token_prefix' => isset($tokenData['access_token']) ? substr($tokenData['access_token'], 0, 10) . '...' : 'missing',
                'token_type' => $tokenData['token_type'] ?? 'missing',
                'open_id' => $tokenData['open_id'] ?? 'missing'
            ]);
            
            // Get user info with the access token
            $userResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $tokenData['access_token']
                ])
                ->get('https://open.tiktokapis.com/v2/user/info/', [
                    'fields' => 'open_id,union_id,avatar_url,display_name,username,is_verified'
                ]);
            
            $userData = $userResponse->json();
            
            // Log the actual response for debugging
            Log::info('TikTok user data response', $userData);
            
            // Add this after the user info request
            Log::debug('TikTok user info raw response', [
                'status' => $userResponse->status(),
                'body' => $userResponse->body(),
                'json' => $userResponse->json()
            ]);
            
            if (!isset($userData['data']) || !isset($userData['data']['user'])) {
                Log::error('TikTok user info retrieval failed', ['response' => $userData]);
                return redirect()->route('register')
                    ->with('error', 'Failed to retrieve user information from TikTok.');
            }
            
            $tikTokUser = $userData['data']['user'];
            
            // Ensure we have the minimum required fields
            if (!isset($tikTokUser['open_id'])) {
                Log::error('TikTok user data missing open_id', ['tiktok_user' => $tikTokUser]);
                return redirect()->route('register')
                    ->with('error', 'TikTok user data is incomplete. Please try again.');
            }
            
            // Find or create user
            $socialAccount = SocialAccount::where('provider', 'tiktok')
                ->where('provider_user_id', $tikTokUser['open_id'])
                ->first();
            
            if ($socialAccount) {
                // Login the existing user
                Auth::login($socialAccount->user);
                return redirect()->intended('/dashboard');
            } else {
                // Handle new user registration
                // Store TikTok data in session for registration completion
                session([
                    'tiktok_user' => $tikTokUser,
                    'tiktok_token' => [
                        'access_token' => $tokenData['access_token'],
                        'refresh_token' => $tokenData['refresh_token'] ?? null,
                        'expires_in' => $tokenData['expires_in'] ?? null,
                    ]
                ]);
                
                // Redirect to complete registration
                return redirect()->route('register.social', ['provider' => 'tiktok']);
            }
            
        } catch (\Exception $e) {
            Log::error('TikTok authentication exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('register')
                ->with('error', 'An error occurred during TikTok authentication: ' . $e->getMessage());
        }
    }
    
    /**
     * Refresh the TikTok access token.
     *
     * @param  string  $refreshToken
     * @return array|null
     */
    public function refreshToken($refreshToken)
    {
        try {
            $response = Http::asForm()->post('https://open.tiktokapis.com/v2/oauth/token/', [
                'client_key' => config('services.tiktok.client_id'),
                'client_secret' => config('services.tiktok.client_secret'),
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ]);
            
            $tokenData = $response->json();
            
            if (isset($tokenData['access_token'])) {
                return $tokenData;
            }
            
            Log::error('TikTok token refresh failed', ['response' => $tokenData]);
            return null;
            
        } catch (\Exception $e) {
            Log::error('TikTok token refresh exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }
}
```

### Register TikTok Socialite Provider

Update your `app/Providers/AppServiceProvider.php` to register the TikTok provider:

```php
public function boot()
{
    // Existing boot code
    
    // Fix TikTok Socialite provider to use client_key instead of client_id
    if (class_exists('Laravel\Socialite\Facades\Socialite')) {
        $socialite = app('Laravel\Socialite\Contracts\Factory');
        
        try {
            $socialite->extend('tiktok', function ($app) use ($socialite) {
                $config = $app['config']['services.tiktok'];
                return $socialite->buildProvider(
                    \App\Services\TikTokProvider::class, 
                    $config
                );
            });
        } catch (\Exception $e) {
            // Provider might already be registered, just ignore
            \Illuminate\Support\Facades\Log::info('TikTok provider registration: ' . $e->getMessage());
        }
    }
}
```

## Implementation Details

### PKCE Authentication Flow

TikTok requires PKCE (Proof Key for Code Exchange) for OAuth 2.0 authorization, which enhances security by preventing authorization code interception attacks. The implementation includes:

1. Generate a random code verifier
2. Create a code challenge (base64url encoded SHA-256 hash of the code verifier)
3. Send the code challenge with the authorization request
4. Include the original code verifier in the token exchange request

### Credential Management

This implementation follows Laravel best practices for credential management:

1. Store all sensitive credentials in the `.env` file
2. Access credentials through Laravel's configuration system
3. Never hardcode credentials in the codebase
4. Use config() helper to access credentials consistently

### Error Handling

The implementation includes comprehensive error handling and logging:

1. CSRF protection using state parameter
2. Error handling for all API responses
3. Detailed logging at each step of the authentication flow
4. Validation of required user data fields

## Troubleshooting

### Common Issues

1. **client_key error (error code 10003)**
   - Ensure your client key in .env matches exactly what's in the TikTok Developer Portal
   - Check that the client_key parameter is included in the authorization URL
   - Verify that services.php is properly configured to read TIKTOK_CLIENT_KEY

2. **Redirect URI error**
   - Verify that the redirect URI in your TikTok Developer Portal exactly matches your configured URI in .env
   - Note that TikTok doesn't allow localhost in production apps
   - Make sure your TIKTOK_REDIRECT_URI in .env matches what's registered in the TikTok Developer Portal

3. **User data retrieval issues**
   - Check that you're requesting valid fields in the user info request
   - Verify that your app has the necessary permissions
   - Review logs for the exact API response

4. **PKCE-related errors**
   - Ensure the code_challenge is properly generated using the S256 method
   - Verify that the code_verifier is saved and retrieved correctly from the session

### Testing Locally

For local testing:

1. Use a domain proxy like [ngrok](https://ngrok.com/) to create a public URL
2. Update your TikTok app's redirect URI to use the ngrok URL
3. Update your application configuration to match

## API Reference

### TikTok OAuth Endpoints

- **Authorization URL**: `https://www.tiktok.com/v2/auth/authorize`
- **Token Exchange URL**: `https://open.tiktokapis.com/v2/oauth/token/`
- **User Info URL**: `https://open.tiktokapis.com/v2/user/info/`
- **Token Refresh URL**: `https://open.tiktokapis.com/v2/oauth/token/`
- **Token Revoke URL**: `https://open.tiktokapis.com/v2/oauth/revoke/`

### Available User Data Fields

- `open_id`: Unique identifier for the user
- `union_id`: Unique identifier across all of a developer's apps
- `avatar_url`: Profile picture URL
- `display_name`: User's display name
- `username`: Username (if available)
- `is_verified`: Whether the user is verified

Note that TikTok doesn't provide email addresses through their API.

## References

- [TikTok Login Kit Documentation](https://developers.tiktok.com/doc/login-kit-overview/)
- [OAuth User Access Token Management](https://developers.tiktok.com/doc/oauth-user-access-token-management)
- [TikTok API Error Codes](https://developers.tiktok.com/doc/error-codes) 