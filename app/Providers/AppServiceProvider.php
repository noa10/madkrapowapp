<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        if(config('app.env') === 'production') {
            \URL::forceScheme('https');
        }

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
}
