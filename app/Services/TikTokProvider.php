<?php

namespace App\Services;

use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;
use Illuminate\Support\Facades\Log;

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
        // Log the auth URL for debugging
        $url = 'https://www.tiktok.com/auth/authorize/';
        $fullUrl = $this->buildAuthUrlFromBase($url, $state);
        Log::info('TikTok Auth URL', ['url' => $fullUrl]);
        return $fullUrl;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://open-api.tiktok.com/oauth/access_token/';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        try {
            $response = $this->getHttpClient()->get('https://open-api.tiktok.com/user/info/', [
                'query' => [
                    'access_token' => $token,
                    'open_id' => $this->credentialsResponseBody['open_id'] ?? null,
                    'fields' => 'open_id,display_name,avatar_url',
                ],
            ]);

            $userData = json_decode($response->getBody(), true);
            Log::info('TikTok User Data Response', ['data' => $userData]);
            
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
        
        $userData = $user['data']['user'] ?? [];
        
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
        $fields = [
            'client_key' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUrl,
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
            Log::info('Getting TikTok access token', ['code' => $code]);
            
            $response = $this->getHttpClient()->post($this->getTokenUrl(), [
                'form_params' => $this->getTokenFields($code),
            ]);

            $data = json_decode($response->getBody(), true);
            Log::info('TikTok Token Response', ['data' => $data]);
            
            // Store the open_id for later use
            $this->credentialsResponseBody = $data['data'] ?? [];
            
            return $data['data'] ?? [];
        } catch (\Exception $e) {
            Log::error('TikTok getAccessTokenResponse Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
} 