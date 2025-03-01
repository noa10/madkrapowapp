<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\MadkrapowUser;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirectToGoogle()
    {
        try {
            // Add logging to track the redirect process
            \Log::info('Starting Google redirect');
            
            // Store a session flag to indicate we're in the Google auth process
            session(['google_auth_in_progress' => true]);
            
            // Simplify the redirect process
            return Socialite::driver('google')
                ->scopes(['email', 'profile'])
                ->redirect();
                
        } catch (Exception $e) {
            // Log any errors that occur during redirect
            \Log::error('Google redirect error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return redirect()->route('login')
                ->with('error', 'Unable to connect to Google. Please try again later.');
        }
    }

    public function handleGoogleCallback()
    {
        try {
            // Add detailed logging
            \Log::info('Google callback initiated');

            // Clear the in-progress flag
            session()->forget('google_auth_in_progress');

            // Use standard ->user() (NOT stateless) for web applications
            $googleUser = Socialite::driver('google')->user();

            // Log the Google user data (be careful with sensitive info)
            \Log::info('Google user retrieved', [
                'id' => $googleUser->getId(),
                'email' => $googleUser->getEmail(),
            ]);

            // Find existing user or create new one
            $user = MadkrapowUser::where('email', $googleUser->getEmail())->first();
            $isNewUser = false;

            if (!$user) {
                \Log::info('Creating new user for Google auth');
                $isNewUser = true;
                
                // Create user data array
                $userData = [
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'password' => bcrypt(Str::random(16)),
                    'is_verified' => true,
                ];
                
                // Add google_id if the column exists
                if (Schema::hasColumn('madkrapow_users', 'google_id')) {
                    $userData['google_id'] = $googleUser->getId();
                }
                
                $user = MadkrapowUser::create($userData);
            } else {
                \Log::info('Updating existing user for Google auth', ['user_id' => $user->user_id]);
                
                // Update the google_id if the column exists and it's not set
                if (Schema::hasColumn('madkrapow_users', 'google_id')) {
                    if (empty($user->google_id)) {
                        $user->google_id = $googleUser->getId();
                        $user->save();
                    }
                }
            }

            // Log the user in
            Auth::login($user);
            \Log::info('User logged in successfully', ['user_id' => $user->user_id]);

            // Prepare appropriate success message based on whether this is a new user or existing user
            if ($isNewUser) {
                $message = 'Your account has been created successfully with Google! Welcome to Mad Krapow.';
                $alertType = 'success';
            } else {
                $message = 'Welcome back! You have been logged in with Google.';
                $alertType = 'info';
            }

            // Redirect to homepage with appropriate message
            return redirect('/')->with($alertType, $message);
    
        } catch (Exception $e) {
            // Clear the in-progress flag
            session()->forget('google_auth_in_progress');
            
            // Detailed error logging
            \Log::error('Google login error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
    
            return redirect()->route('login')
                ->with('error', 'Google authentication failed. Please try again. Error: ' . $e->getMessage());
        }
    }
}
