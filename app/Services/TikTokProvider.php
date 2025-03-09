<?php

namespace App\Services;

use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

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
        return $this->buildAuthUrlFromBase('https://www.tiktok.com/auth/authorize/', $state);
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
        $response = $this->getHttpClient()->get('https://open-api.tiktok.com/user/info/', [
            'query' => [
                'access_token' => $token,
                'open_id' => $this->credentialsResponseBody['open_id'] ?? null,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
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
        return array_merge(parent::getTokenFields($code), [
            'client_key' => $this->clientId,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'form_params' => $this->getTokenFields($code),
        ]);

        $data = json_decode($response->getBody(), true);
        
        // Store the open_id for later use
        $this->credentialsResponseBody = $data['data'] ?? [];
        
        return $data['data'] ?? [];
    }
} 