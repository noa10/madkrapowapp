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

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {
        try {
            Log::info('Getting TikTok access token', [
                'code' => $code,
                'client_key' => $this->clientId
            ]);
            
            $response = $this->getHttpClient()->post($this->getTokenUrl(), [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'form_params' => $this->getTokenFields($code),
            ]);

            $data = json_decode($response->getBody(), true);
            Log::info('TikTok Token Response', ['data' => $data]);
            
            return $data;
        } catch (\Exception $e) {
            Log::error('TikTok getAccessTokenResponse Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
} 