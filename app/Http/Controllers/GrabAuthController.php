<?php

namespace App\Http\Controllers;

use App\Services\GrabService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class GrabAuthController extends Controller
{
    protected $grabService;

    /**
     * Create a new controller instance.
     *
     * @param GrabService $grabService
     */
    public function __construct(GrabService $grabService)
    {
        $this->middleware('auth');
        $this->grabService = $grabService;
    }

    /**
     * Redirect the user to the Grab OAuth page.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirect()
    {
        return redirect($this->grabService->getAuthorizationUrl());
    }

    /**
     * Handle the callback from Grab OAuth.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function callback(Request $request)
    {
        try {
            // Check for error response
            if ($request->has('error')) {
                $error = $request->input('error');
                $errorDescription = $request->input('error_description');
                
                Log::error('Grab OAuth error', [
                    'error' => $error,
                    'description' => $errorDescription,
                ]);
                
                return redirect()->route('profile.edit')
                    ->with('error', 'Failed to connect your Grab account: ' . $errorDescription);
            }
            
            // Verify state to prevent CSRF attacks
            $state = $request->input('state');
            if (!$this->grabService->verifyState($state)) {
                return redirect()->route('profile.edit')
                    ->with('error', 'Invalid state parameter. Please try again.');
            }
            
            // Get authorization code
            $code = $request->input('code');
            if (!$code) {
                return redirect()->route('profile.edit')
                    ->with('error', 'No authorization code provided.');
            }
            
            // Exchange code for access token
            $tokenData = $this->grabService->getAccessToken($code);
            
            // Store the account information
            $user = Auth::user();
            $this->grabService->storeUserGrabAccount($user, $tokenData);
            
            // Fetch and store loyalty tier
            $tier = $this->grabService->getLoyaltyTier($tokenData['access_token']);
            
            return redirect()->route('profile.edit')
                ->with('success', 'Your Grab account has been connected successfully.' . 
                    ($tier ? ' Your loyalty tier: ' . ucfirst($tier) : ''));
                    
        } catch (Exception $e) {
            Log::error('Exception in Grab OAuth callback', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return redirect()->route('profile.edit')
                ->with('error', 'Failed to connect your Grab account. Please try again later.');
        }
    }
    
    /**
     * Disconnect the user's Grab account.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function disconnect()
    {
        try {
            $user = Auth::user();
            
            // Find and delete the user's Grab social account
            $socialAccount = $user->socialAccounts()
                ->where('provider', 'grab')
                ->first();
                
            if ($socialAccount) {
                $socialAccount->delete();
                return redirect()->route('profile.edit')
                    ->with('success', 'Your Grab account has been disconnected successfully.');
            }
            
            return redirect()->route('profile.edit')
                ->with('info', 'No Grab account was connected to your profile.');
                
        } catch (Exception $e) {
            Log::error('Exception disconnecting Grab account', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return redirect()->route('profile.edit')
                ->with('error', 'Failed to disconnect your Grab account. Please try again later.');
        }
    }
} 