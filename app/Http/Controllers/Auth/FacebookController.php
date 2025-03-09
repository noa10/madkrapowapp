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

class FacebookController extends Controller
{
    public function redirectToFacebook()
    {
        try {
            // Add detailed logging to track the redirect process
            Log::info('Starting Facebook redirect', [
                'facebook_config' => [
                    'client_id_set' => !empty(config('services.facebook.client_id')),
                    'client_secret_set' => !empty(config('services.facebook.client_secret')),
                    'redirect_uri_set' => !empty(config('services.facebook.redirect')),
                    'redirect_uri' => config('services.facebook.redirect')
                ]
            ]);
            
            // Store a session flag to indicate we're in the Facebook auth process
            session(['facebook_auth_in_progress' => true]);
            
            // Redirect to Facebook OAuth
            return Socialite::driver('facebook')
                ->scopes(['email'])
                ->redirect();
                
        } catch (Exception $e) {
            // Clear the in-progress flag
            session()->forget('facebook_auth_in_progress');
            
            // Log any errors that occur during redirect
            Log::error('Facebook redirect error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('login')
                ->with('error', 'Unable to connect to Facebook. Please try again later. Error: ' . $e->getMessage());
        }
    }

    public function handleFacebookCallback()
    {
        try {
            // Add detailed logging
            Log::info('Facebook callback initiated', [
                'request_params' => request()->all()
            ]);

            // Clear the in-progress flag
            session()->forget('facebook_auth_in_progress');

            // Get the Facebook user
            $facebookUser = Socialite::driver('facebook')->user();

            // Log the Facebook user data (be careful with sensitive info)
            Log::info('Facebook user retrieved', [
                'id' => $facebookUser->getId(),
                'email' => $facebookUser->getEmail(),
                'name' => $facebookUser->getName()
            ]);

            // Find existing user or create new one
            $user = MadkrapowUser::where('email', $facebookUser->getEmail())->first();
            $isNewUser = false;

            if (!$user) {
                Log::info('Creating new user for Facebook auth');
                $isNewUser = true;
                
                // Create user data array
                $userData = [
                    'name' => $facebookUser->getName(),
                    'email' => $facebookUser->getEmail(),
                    'password' => bcrypt(Str::random(16)),
                    'is_verified' => true,
                ];
                
                // Add facebook_id if the column exists
                if (Schema::hasColumn('madkrapow_users', 'facebook_id')) {
                    $userData['facebook_id'] = $facebookUser->getId();
                }
                
                $user = MadkrapowUser::create($userData);
            } else {
                Log::info('Updating existing user for Facebook auth', ['user_id' => $user->user_id]);
                
                // Update the facebook_id if the column exists and it's not set
                if (Schema::hasColumn('madkrapow_users', 'facebook_id')) {
                    if (empty($user->facebook_id)) {
                        $user->facebook_id = $facebookUser->getId();
                        $user->save();
                    }
                }
            }

            // Log the user in
            Auth::login($user);
            Log::info('User logged in successfully', ['user_id' => $user->user_id]);

            // Prepare appropriate success message based on whether this is a new user or existing user
            if ($isNewUser) {
                $message = 'Your account has been created successfully with Facebook! Welcome to Mad Krapow.';
                $alertType = 'success';
            } else {
                $message = 'Welcome back! You have been logged in with Facebook.';
                $alertType = 'info';
            }

            // Redirect to homepage with appropriate message
            return redirect('/')->with($alertType, $message);
    
        } catch (Exception $e) {
            // Clear the in-progress flag
            session()->forget('facebook_auth_in_progress');
            
            // Detailed error logging
            Log::error('Facebook login error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_params' => request()->all()
            ]);
    
            return redirect()->route('login')
                ->with('error', 'Facebook authentication failed. Please try again. Error: ' . $e->getMessage());
        }
    }
} 