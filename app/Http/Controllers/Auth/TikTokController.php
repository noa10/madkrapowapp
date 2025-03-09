<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\MadkrapowUser;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class TikTokController extends Controller
{
    public function redirectToTikTok()
    {
        try {
            // Add detailed logging to track the redirect process
            Log::info('Starting TikTok redirect', [
                'tiktok_config' => [
                    'client_id_set' => !empty(config('services.tiktok.client_id')),
                    'client_secret_set' => !empty(config('services.tiktok.client_secret')),
                    'redirect_uri_set' => !empty(config('services.tiktok.redirect')),
                    'redirect_uri' => config('services.tiktok.redirect')
                ]
            ]);
            
            // Store a session flag to indicate we're in the TikTok auth process
            session(['tiktok_auth_in_progress' => true]);
            
            // Redirect to TikTok OAuth
            return Socialite::driver('tiktok')
                ->scopes(['user.info.basic'])
                ->redirect();
                
        } catch (Exception $e) {
            // Clear the in-progress flag
            session()->forget('tiktok_auth_in_progress');
            
            // Log any errors that occur during redirect
            Log::error('TikTok redirect error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('login')
                ->with('error', 'Unable to connect to TikTok. Please try again later. Error: ' . $e->getMessage());
        }
    }

    public function handleTikTokCallback()
    {
        try {
            // Add detailed logging
            Log::info('TikTok callback initiated', [
                'request_params' => request()->all()
            ]);

            // Clear the in-progress flag
            session()->forget('tiktok_auth_in_progress');

            // Get the TikTok user
            $tiktokUser = Socialite::driver('tiktok')->user();

            // Log the TikTok user data (be careful with sensitive info)
            Log::info('TikTok user retrieved', [
                'id' => $tiktokUser->getId(),
                'nickname' => $tiktokUser->getNickname(),
                'name' => $tiktokUser->getName(),
                'avatar' => $tiktokUser->getAvatar()
            ]);

            // TikTok doesn't always provide email, so we'll use the ID as a unique identifier
            // and generate a placeholder email if needed
            $email = $tiktokUser->getEmail();
            if (empty($email)) {
                $email = 'tiktok_' . $tiktokUser->getId() . '@example.com';
                Log::info('Generated placeholder email for TikTok user', ['email' => $email]);
            }

            // Find existing user or create new one
            $user = MadkrapowUser::where('tiktok_id', $tiktokUser->getId())->first();
            
            if (!$user) {
                // Try to find by email if available
                $user = MadkrapowUser::where('email', $email)->first();
            }
            
            $isNewUser = false;

            if (!$user) {
                Log::info('Creating new user for TikTok auth');
                $isNewUser = true;
                
                // Create user data array
                $userData = [
                    'name' => $tiktokUser->getName() ?: $tiktokUser->getNickname() ?: 'TikTok User',
                    'email' => $email,
                    'password' => bcrypt(Str::random(16)),
                    'is_verified' => true,
                ];
                
                // Add tiktok_id if the column exists
                if (Schema::hasColumn('madkrapow_users', 'tiktok_id')) {
                    $userData['tiktok_id'] = $tiktokUser->getId();
                }
                
                $user = MadkrapowUser::create($userData);
            } else {
                Log::info('Updating existing user for TikTok auth', ['user_id' => $user->user_id]);
                
                // Update the tiktok_id if the column exists and it's not set
                if (Schema::hasColumn('madkrapow_users', 'tiktok_id')) {
                    if (empty($user->tiktok_id)) {
                        $user->tiktok_id = $tiktokUser->getId();
                        $user->save();
                    }
                }
            }

            // Log the user in
            Auth::login($user);
            Log::info('User logged in successfully', ['user_id' => $user->user_id]);

            // Prepare appropriate success message based on whether this is a new user or existing user
            if ($isNewUser) {
                $message = 'Your account has been created successfully with TikTok! Welcome to Mad Krapow.';
                $alertType = 'success';
            } else {
                $message = 'Welcome back! You have been logged in with TikTok.';
                $alertType = 'info';
            }

            // Redirect to homepage with appropriate message
            return redirect('/')->with($alertType, $message);
    
        } catch (Exception $e) {
            // Clear the in-progress flag
            session()->forget('tiktok_auth_in_progress');
            
            // Detailed error logging
            Log::error('TikTok login error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_params' => request()->all()
            ]);
    
            return redirect()->route('login')
                ->with('error', 'TikTok authentication failed. Please try again. Error: ' . $e->getMessage());
        }
    }
} 