<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class SocialEmailController extends Controller
{
    /**
     * Show the form to collect email for social login.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $provider
     * @return \Illuminate\View\View
     */
    public function showEmailForm(Request $request, $provider)
    {
        // Check if we have social data in the session
        if (!session()->has($provider . '_user')) {
            return redirect()->route('login')
                ->with('error', 'No social login data found. Please try again.');
        }
        
        return view('auth.social-email', [
            'provider' => $provider
        ]);
    }
    
    /**
     * Handle the email form submission.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $provider
     * @return \Illuminate\Http\RedirectResponse
     */
    public function processEmailForm(Request $request, $provider)
    {
        // Validate the email
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:madkrapow_users,email'
        ]);
        
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        
        // Get the social data from session
        $socialData = session($provider . '_user');
        $tokenData = session($provider . '_token');
        
        if (!$socialData) {
            return redirect()->route('login')
                ->with('error', 'No social login data found. Please try again.');
        }
        
        // Create the user
        $user = User::create([
            'name' => $socialData['display_name'] ?? $provider . ' User',
            'email' => $request->email,
            'password' => bcrypt(Str::random(24)),
        ]);
        
        // Create the social account link - fix the column name issue
        if ($user) {
            $socialAccount = new SocialAccount([
                'provider' => $provider,
                'provider_user_id' => $socialData['open_id'],
                'token' => $tokenData['access_token'] ?? null,
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'token_expires_at' => isset($tokenData['expires_in']) 
                    ? now()->addSeconds($tokenData['expires_in']) 
                    : null,
            ]);
            
            // Associate and save properly
            $socialAccount->user_id = $user->user_id;
            $socialAccount->save();
        }
        
        // Login the user
        Auth::login($user);
        
        // Clear the session data
        session()->forget($provider . '_user');
        session()->forget($provider . '_token');
        
        return redirect()->intended('/dashboard');
    }
}
