<?php

namespace App\Http\Controllers;

use App\Services\GrabService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class ProfileController extends Controller
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
     * Show the user profile and loyalty information.
     *
     * @return \Illuminate\View\View
     */
    public function edit()
    {
        $user = Auth::user();
        $grabTier = null;
        $grabConnected = false;
        $grabError = null;
        $tierClasses = null;
        $tierDisplayName = null;

        // Check if the user has a connected Grab account
        $grabAccount = $user->socialAccounts()
            ->where('provider', 'grab')
            ->first();

        if ($grabAccount) {
            $grabConnected = true;
            
            // Try to get the cached tier first (cache for 1 hour)
            $cacheKey = 'grab_tier_' . $user->user_id;
            
            if (Cache::has($cacheKey)) {
                $grabTier = Cache::get($cacheKey);
                $tierDisplayName = $this->grabService->getTierDisplayName($grabTier);
                $tierClasses = $this->grabService->getTierBadgeClasses($grabTier);
            } else {
                try {
                    // Get the loyalty tier info
                    $tierInfo = $this->grabService->getUserLoyaltyTier($user);
                    
                    if ($tierInfo['tier']) {
                        $grabTier = $tierInfo['tier'];
                        $tierDisplayName = $this->grabService->getTierDisplayName($grabTier);
                        $tierClasses = $this->grabService->getTierBadgeClasses($grabTier);
                        
                        // Cache the tier for 1 hour
                        Cache::put($cacheKey, $grabTier, now()->addHour());
                    } else {
                        $grabError = $tierInfo['error'];
                    }
                } catch (Exception $e) {
                    Log::error('Error in profile edit', [
                        'user_id' => $user->user_id,
                        'error' => $e->getMessage()
                    ]);
                    $grabError = 'An error occurred while fetching your Grab loyalty tier.';
                }
            }
        }

        return view('profile.edit', compact(
            'user', 
            'grabTier', 
            'grabConnected', 
            'grabError',
            'tierDisplayName',
            'tierClasses'
        ));
    }

    /**
     * Manually refresh the user's Grab loyalty tier.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function refreshGrabTier()
    {
        $user = Auth::user();
        
        // Check if the user has a connected Grab account
        $grabAccount = $user->socialAccounts()
            ->where('provider', 'grab')
            ->first();
            
        if (!$grabAccount) {
            return redirect()->route('profile.edit')
                ->with('error', 'You do not have a connected Grab account.');
        }
        
        try {
            // Get the loyalty tier info (force refresh)
            $tierInfo = $this->grabService->getUserLoyaltyTier($user);
            
            if ($tierInfo['tier']) {
                $grabTier = $tierInfo['tier'];
                $tierDisplayName = $this->grabService->getTierDisplayName($grabTier);
                
                // Update the cache
                $cacheKey = 'grab_tier_' . $user->user_id;
                Cache::put($cacheKey, $grabTier, now()->addHour());
                
                return redirect()->route('profile.edit')
                    ->with('success', "Your Grab loyalty tier has been refreshed: {$tierDisplayName}");
            } else {
                return redirect()->route('profile.edit')
                    ->with('error', $tierInfo['error'] ?? 'Unable to fetch your Grab loyalty tier at this time.');
            }
        } catch (Exception $e) {
            Log::error('Error refreshing Grab loyalty tier', [
                'user_id' => $user->user_id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('profile.edit')
                ->with('error', 'An error occurred while refreshing your Grab loyalty tier.');
        }
    }
} 