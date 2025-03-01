<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirectToGoogle()
    {
        try {
            // Add logging to track the redirect process
            \Log::info('Starting Google redirect');
            
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

            // Use standard ->user() (NOT stateless) for web applications
            $googleUser = Socialite::driver('google')->user();

            // Log the Google user data (be careful with sensitive info)
            \Log::info('Google user retrieved', [
                'id' => $googleUser->getId(),
                'email' => $googleUser->getEmail(),
            ]);

            // Find existing user or create new one
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                \Log::info('Creating new user for Google auth');
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'password' => bcrypt(Str::random(16)),
                ]);
            } else {
                \Log::info('Updating existing user for Google auth', ['user_id' => $user->id]);
                // Update the google_id if it's not set
                if (empty($user->google_id)) {
                    $user->google_id = $googleUser->getId();
                    $user->save();
                }
            }

            // Log the user in
            Auth::login($user);
            \Log::info('User logged in successfully', ['user_id' => $user->id]);

            // Redirect to homepage instead of dashboard
            return redirect('/');
    
        } catch (Exception $e) {
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
