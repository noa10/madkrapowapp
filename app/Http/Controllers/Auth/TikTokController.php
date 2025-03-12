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
            
            // Get user info with the access token
            $userResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $tokenData['access_token']
                ])
                ->get('https://open.tiktokapis.com/v2/user/info/', [
                    'fields' => 'open_id,display_name'
                ]);
            
            $userData = $userResponse->json();
            
            // Log the actual response for debugging
            Log::info('TikTok user data response', $userData);
            
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
                return redirect()->intended(route('dashboard'));
            }
            
            // Create new user
            $user = User::where('email', $tikTokUser['email'] ?? null)->first();
            
            if (!$user && empty($tikTokUser['email'])) {
                // TikTok didn't provide an email, redirect to a form to collect it
                session([
                    'tiktok_user' => $tikTokUser,
                    'tiktok_token' => $tokenData
                ]);
                
                return redirect()->route('social.email.form', ['provider' => 'tiktok']);
            }
            
            if (!$user) {
                $user = User::create([
                    'name' => $tikTokUser['display_name'] ?? 'TikTok User',
                    'email' => $tikTokUser['email'],
                    'password' => bcrypt(Str::random(24)),
                ]);
            }
            
            // Create social account link
            $user->socialAccounts()->create([
                'provider' => 'tiktok',
                'provider_user_id' => $tikTokUser['open_id'],
                'token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 0),
            ]);
            
            Auth::login($user);
            
            // Add this to your callback method after the user info request
            $rateLimit = [
                'limit' => $userResponse->header('X-RateLimit-Limit') ?? 'unknown',
                'remaining' => $userResponse->header('X-RateLimit-Remaining') ?? 'unknown',
                'reset' => $userResponse->header('X-RateLimit-Reset') ?? 'unknown',
            ];
            Log::info('TikTok rate limit info', $rateLimit);
            
            // Clear the session data
            session()->forget('tiktok_user');
            session()->forget('tiktok_token');
            
            return redirect()->intended(route('dashboard'));
            
        } catch (\Exception $e) {
            Log::error('TikTok authentication exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('register')
                ->with('error', 'An error occurred during TikTok authentication.');
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