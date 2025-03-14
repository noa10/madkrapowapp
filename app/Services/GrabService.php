<?php

namespace App\Services;

use App\Models\User;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class GrabService
{
    /**
     * Grab OAuth endpoints
     */
    protected $authorizationUrl;
    protected $tokenUrl;
    protected $rewardsTierUrl;
    protected $pointsEarningUrl;
    
    /**
     * Grab API credentials
     */
    protected $clientId;
    protected $clientSecret;
    protected $redirectUri;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        // Set up endpoints based on environment (staging or production)
        $isProduction = config('services.grab.environment') === 'production';
        
        // Set the endpoints
        if ($isProduction) {
            $this->authorizationUrl = 'https://api.grab.com/oauth2/authorize';
            $this->tokenUrl = 'https://api.grab.com/oauth2/token';
            $this->rewardsTierUrl = 'https://partner-api.grab.com/loyalty/rewards/v1/tier';
            $this->pointsEarningUrl = 'https://api.grab.com/rewards/v3/events';
        } else {
            $this->authorizationUrl = 'https://api.stg-myteksi.com/oauth2/authorize';
            $this->tokenUrl = 'https://api.stg-myteksi.com/oauth2/token';
            $this->rewardsTierUrl = 'https://partner-api.stg-myteksi.com/loyalty/rewards/v1/tier';
            $this->pointsEarningUrl = 'https://api.stg-myteksi.com/rewards/v3/events';
        }
        
        // Set credentials from config
        $this->clientId = config('services.grab.client_id');
        $this->clientSecret = config('services.grab.client_secret');
        $this->redirectUri = config('services.grab.redirect');
    }
    
    /**
     * Get the authorization URL for redirecting user to Grab OAuth screen
     *
     * @return string
     */
    public function getAuthorizationUrl()
    {
        $params = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => 'rewards.tier rewards.points',
            'state' => $this->generateState(),
        ];
        
        return $this->authorizationUrl . '?' . http_build_query($params);
    }
    
    /**
     * Generate a random state parameter for CSRF protection
     *
     * @return string
     */
    protected function generateState()
    {
        $state = bin2hex(random_bytes(16));
        session(['grab_oauth_state' => $state]);
        return $state;
    }
    
    /**
     * Verify the state parameter to prevent CSRF attacks
     *
     * @param string $state
     * @return bool
     */
    public function verifyState($state)
    {
        return $state === session('grab_oauth_state');
    }
    
    /**
     * Exchange authorization code for access token
     *
     * @param string $code
     * @return array
     * @throws Exception
     */
    public function getAccessToken($code)
    {
        try {
            $response = Http::asForm()->post($this->tokenUrl, [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri' => $this->redirectUri,
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'] ?? null,
                    'expires_in' => $data['expires_in'] ?? 3600,
                    'partner_user_id' => $data['partner_user_id'] ?? null,
                ];
            }
            
            Log::error('Grab OAuth token exchange failed', [
                'status' => $response->status(),
                'response' => $response->json() ?? $response->body(),
            ]);
            
            throw new Exception('Failed to exchange code for token: ' . ($response->json()['error_description'] ?? 'Unknown error'));
        } catch (Exception $e) {
            Log::error('Exception in Grab OAuth token exchange', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
    
    /**
     * Refresh an expired access token
     *
     * @param string $refreshToken
     * @return array
     * @throws Exception
     */
    public function refreshAccessToken($refreshToken)
    {
        try {
            $response = Http::asForm()->post($this->tokenUrl, [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'] ?? $refreshToken, // Some providers don't return a new refresh token
                    'expires_in' => $data['expires_in'] ?? 3600,
                ];
            }
            
            Log::error('Grab OAuth token refresh failed', [
                'status' => $response->status(),
                'response' => $response->json() ?? $response->body(),
            ]);
            
            throw new Exception('Failed to refresh token: ' . ($response->json()['error_description'] ?? 'Unknown error'));
        } catch (Exception $e) {
            Log::error('Exception in Grab OAuth token refresh', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
    
    /**
     * Store or update the user's Grab account details
     *
     * @param User $user
     * @param array $tokenData
     * @return SocialAccount
     */
    public function storeUserGrabAccount(User $user, array $tokenData)
    {
        $expiresAt = Carbon::now()->addSeconds($tokenData['expires_in']);
        
        // Find or create the social account
        $socialAccount = SocialAccount::updateOrCreate(
            [
                'user_id' => $user->user_id,
                'provider' => 'grab',
            ],
            [
                'provider_user_id' => $tokenData['partner_user_id'] ?? 'unknown',
                'token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'],
                'token_expires_at' => $expiresAt,
            ]
        );
        
        return $socialAccount;
    }
    
    /**
     * Get an authenticated user's loyalty tier
     *
     * @param string $accessToken
     * @return string|null
     */
    public function getLoyaltyTier($accessToken)
    {
        try {
            $response = Http::withToken($accessToken)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->get($this->rewardsTierUrl);
            
            if ($response->successful()) {
                $data = $response->json();
                return $data['result']['tier'] ?? null;
            }
            
            // Handle specific error status codes
            if ($response->status() === 401) {
                Log::warning('Unauthorized access to Grab loyalty tier API. Token might be expired.');
                throw new Exception('Unauthorized access to Grab loyalty tier API');
            } elseif ($response->status() === 429) {
                Log::warning('Rate limited by Grab loyalty tier API.');
                throw new Exception('Rate limited by Grab API');
            }
            
            Log::error('Failed to fetch Grab loyalty tier', [
                'status' => $response->status(),
                'response' => $response->json() ?? $response->body(),
            ]);
            
            return null;
        } catch (Exception $e) {
            Log::error('Exception fetching Grab loyalty tier', [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    /**
     * Get a valid access token for a user, refreshing if necessary
     *
     * @param User $user
     * @return string|null
     */
    public function getValidAccessToken(User $user)
    {
        $socialAccount = $user->socialAccounts()
            ->where('provider', 'grab')
            ->first();
            
        if (!$socialAccount) {
            return null;
        }
        
        // Check if token is expired or about to expire (within 5 minutes)
        if (!$socialAccount->token_expires_at || $socialAccount->token_expires_at->subMinutes(5)->isPast()) {
            try {
                // Token is expired or about to expire, refresh it
                if (!$socialAccount->refresh_token) {
                    // No refresh token available
                    return null;
                }
                
                $tokenData = $this->refreshAccessToken($socialAccount->refresh_token);
                
                // Update the social account with new tokens
                $socialAccount->token = $tokenData['access_token'];
                $socialAccount->refresh_token = $tokenData['refresh_token'];
                $socialAccount->token_expires_at = Carbon::now()->addSeconds($tokenData['expires_in']);
                $socialAccount->save();
                
                return $tokenData['access_token'];
            } catch (Exception $e) {
                // Failed to refresh token
                Log::error('Failed to refresh Grab access token', [
                    'user_id' => $user->user_id,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        }
        
        // Token is still valid
        return $socialAccount->token;
    }
    
    /**
     * Get a user's loyalty tier with caching
     *
     * @param User $user
     * @return array - Contains 'tier' (string|null) and 'error' (string|null)
     */
    public function getUserLoyaltyTier(User $user)
    {
        try {
            $accessToken = $this->getValidAccessToken($user);
            
            if (!$accessToken) {
                return [
                    'tier' => null,
                    'error' => 'Unable to authenticate with Grab. Please reconnect your account.'
                ];
            }
            
            $tier = $this->getLoyaltyTier($accessToken);
            
            if ($tier) {
                return [
                    'tier' => $tier,
                    'error' => null
                ];
            } else {
                return [
                    'tier' => null,
                    'error' => 'Unable to retrieve your loyalty tier information from Grab.'
                ];
            }
        } catch (Exception $e) {
            Log::error('Error getting user loyalty tier', [
                'user_id' => $user->user_id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'tier' => null,
                'error' => 'An error occurred while communicating with Grab services.'
            ];
        }
    }
    
    /**
     * Get display friendly name for a tier
     *
     * @param string $tier
     * @return string
     */
    public function getTierDisplayName($tier)
    {
        $tierNames = [
            'opt-out' => 'Not Enrolled',
            'member' => 'Member',
            'silver' => 'Silver',
            'gold' => 'Gold',
            'platinum' => 'Platinum'
        ];
        
        return $tierNames[$tier] ?? ucfirst($tier);
    }
    
    /**
     * Get CSS classes for a tier badge
     *
     * @param string $tier
     * @return array - Contains 'bg' and 'text' CSS classes
     */
    public function getTierBadgeClasses($tier)
    {
        switch ($tier) {
            case 'platinum':
                return [
                    'bg' => 'bg-purple-100',
                    'text' => 'text-purple-800',
                    'dot' => 'text-purple-400'
                ];
            case 'gold':
                return [
                    'bg' => 'bg-yellow-100',
                    'text' => 'text-yellow-800',
                    'dot' => 'text-yellow-400'
                ];
            case 'silver':
                return [
                    'bg' => 'bg-gray-100',
                    'text' => 'text-gray-800',
                    'dot' => 'text-gray-400'
                ];
            case 'member':
                return [
                    'bg' => 'bg-green-100',
                    'text' => 'text-green-800',
                    'dot' => 'text-green-400'
                ];
            default:
                return [
                    'bg' => 'bg-blue-100',
                    'text' => 'text-blue-800',
                    'dot' => 'text-blue-400'
                ];
        }
    }
    
    /**
     * Award points to a user for a specific event (e.g., purchase)
     *
     * @param User $user
     * @param string $source Event source (e.g., 'purchase')
     * @param string $sourceID Unique identifier for the event
     * @param string $description Human-readable description of the event
     * @param array $payload Event parameters (e.g., [['name' => 'transactionAmount', 'value' => '50.00']])
     * @param string|null $countryCode ISO 3166-1 alpha-2 country code (defaults to 'ID')
     * @return array - Contains 'success' (bool), 'points' (int|null), and 'error' (string|null)
     */
    public function awardPoints(User $user, string $source, string $sourceID, string $description, array $payload, string $countryCode = 'ID')
    {
        try {
            // Get user's Grab ID from social accounts
            $socialAccount = $user->socialAccounts()
                ->where('provider', 'grab')
                ->first();
                
            if (!$socialAccount) {
                return [
                    'success' => false,
                    'points' => null,
                    'error' => 'User does not have a linked Grab account'
                ];
            }
            
            // Generate idempotency key - ensures request can be safely retried
            $idempotencyKey = $this->generateIdempotencyKey();
            
            // Construct request body
            $body = [
                'idempotencyKey' => $idempotencyKey,
                'source' => $source,
                'sourceID' => $sourceID,
                'countryCode' => $countryCode,
                'partnerUserID' => $socialAccount->provider_user_id,
                'description' => $description,
                'payload' => $payload
            ];
            
            // Generate authorization signature and date header
            $dateHeader = $this->getDateHeaderForRequest();
            $authorization = $this->generatePointsEarningAuthorization($body, $dateHeader);
            
            // Send request to Grab API
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Date' => $dateHeader,
                'Authorization' => $authorization
            ])->post($this->pointsEarningUrl, $body);
            
            // Log the request for debugging
            Log::info('Points earning request sent', [
                'user_id' => $user->user_id,
                'body' => $body,
                'status' => $response->status()
            ]);
            
            if ($response->successful()) {
                $responseData = $response->json();
                return [
                    'success' => true,
                    'points' => $responseData['points'] ?? null,
                    'error' => null
                ];
            }
            
            // Handle specific error codes
            $errorMessage = $this->getPointsEarningErrorMessage($response);
            
            Log::error('Failed to award Grab points', [
                'user_id' => $user->user_id,
                'status' => $response->status(),
                'response' => $response->json() ?? $response->body(),
                'idempotencyKey' => $idempotencyKey
            ]);
            
            return [
                'success' => false,
                'points' => null,
                'error' => $errorMessage
            ];
            
        } catch (Exception $e) {
            Log::error('Exception in Grab points earning request', [
                'user_id' => $user->user_id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'points' => null,
                'error' => 'An unexpected error occurred: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate idempotency key for points earning requests
     * 
     * @return string 32-character UUID without dashes
     */
    protected function generateIdempotencyKey()
    {
        return str_replace('-', '', (string) \Illuminate\Support\Str::uuid());
    }
    
    /**
     * Get RFC7231 formatted date header for request
     * 
     * @return string
     */
    protected function getDateHeaderForRequest()
    {
        return Carbon::now()->toRfc7231String();
    }
    
    /**
     * Generate HMAC authorization signature for Points Earning API
     * 
     * @param array $body Request body
     * @param string $dateHeader Date header value
     * @return string Authorization header value
     */
    protected function generatePointsEarningAuthorization(array $body, string $dateHeader)
    {
        // Construct the string to sign
        $method = 'POST';
        $path = '/rewards/v3/events';
        $stringToSign = $method . "\n" . $path . "\n" . $dateHeader . "\n" . json_encode($body);
        
        // Create HMAC signature
        $signature = base64_encode(hash_hmac('sha256', $stringToSign, $this->clientSecret, true));
        
        // Return client_id:signature format
        return $this->clientId . ':' . $signature;
    }
    
    /**
     * Get user-friendly error message for Points Earning API errors
     * 
     * @param \Illuminate\Http\Client\Response $response
     * @return string
     */
    protected function getPointsEarningErrorMessage($response)
    {
        $status = $response->status();
        $errorData = $response->json();
        $errorMessage = $errorData['message'] ?? 'Unknown error';
        
        switch ($status) {
            case 400:
                return 'Invalid request: ' . $errorMessage;
            case 401:
                return 'Authentication failed. Please check the API credentials.';
            case 403:
                return 'Access denied. The source may not be properly configured.';
            case 429:
                return 'Rate limit exceeded. Please try again later.';
            case 500:
            case 502:
            case 503:
            case 504:
                return 'Grab service is currently unavailable. Please try again later.';
            default:
                return 'Error: ' . $errorMessage;
        }
    }
} 