<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\GrabService;
use App\Models\MadkrapowOrder;
use App\Models\MadkrapowOrderItem;
use Illuminate\Support\Facades\Log;

class LoyaltyRewardController extends Controller
{
    /**
     * The Grab service instance.
     *
     * @var \App\Services\GrabService
     */
    protected $grabService;

    /**
     * Create a new controller instance.
     *
     * @param \App\Services\GrabService $grabService
     * @return void
     */
    public function __construct(GrabService $grabService)
    {
        $this->middleware('auth');
        $this->grabService = $grabService;
    }

    /**
     * Display user's loyalty rewards information.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get user's Grab loyalty tier
        $tierInfo = $this->grabService->getUserLoyaltyTier($user);
        $grabTier = $tierInfo['tier'] ?? null;
        $grabTierError = $tierInfo['error'] ?? null;

        // Get tier display information
        $tierDisplayName = null;
        $tierClasses = null;
        
        if ($grabTier) {
            $tierDisplayName = $this->grabService->getTierDisplayName($grabTier);
            $tierClasses = $this->grabService->getTierBadgeClasses($grabTier);
        }
        
        // Get recent orders that earned points
        $recentOrders = MadkrapowOrder::where('user_id', $user->user_id)
            ->where('status', 'completed')
            ->orderBy('order_date', 'desc')
            ->take(5)
            ->get();
            
        return view('loyalty.index', compact(
            'grabTier', 
            'grabTierError', 
            'tierDisplayName', 
            'tierClasses', 
            'recentOrders'
        ));
    }

    /**
     * Award points for a completed purchase.
     *
     * @param  string  $orderId
     * @return \Illuminate\Http\Response
     */
    public function awardPurchasePoints($orderId)
    {
        $user = Auth::user();
        $order = MadkrapowOrder::with(['orderItems.product'])
            ->where('order_id', $orderId)
            ->where('user_id', $user->user_id)
            ->firstOrFail();
            
        // Only award points for completed orders
        if ($order->status !== 'completed') {
            return redirect()->back()->with('error', 'Points can only be awarded for completed orders.');
        }
        
        // Check if points have already been awarded
        if ($order->points_awarded) {
            return redirect()->back()->with('info', 'Points have already been awarded for this order.');
        }
        
        // Calculate total amount
        $transactionAmount = number_format($order->order_total, 2, '.', '');
        
        // Create payload for Grab Points Earning API
        $source = 'purchase';
        $sourceID = 'order_' . $order->order_id;
        $description = 'Points earned from purchase on Madkrapow.com';
        $payload = [
            [
                'name' => 'transactionAmount',
                'value' => $transactionAmount
            ]
        ];
        
        // Call the Points Earning API
        $result = $this->grabService->awardPoints(
            $user,
            $source,
            $sourceID,
            $description,
            $payload
        );
        
        // Handle the result
        if ($result['success']) {
            // Update order to mark points as awarded
            $order->points_awarded = true;
            $order->points_awarded_at = now();
            $order->save();
            
            Log::info('Points awarded successfully', [
                'user_id' => $user->user_id,
                'order_id' => $orderId,
                'points' => $result['points']
            ]);
            
            return redirect()->back()->with('success', 'Points have been awarded for your purchase!');
        } else {
            Log::error('Failed to award points', [
                'user_id' => $user->user_id,
                'order_id' => $orderId,
                'error' => $result['error']
            ]);
            
            return redirect()->back()->with('error', 'Unable to award points: ' . $result['error']);
        }
    }
    
    /**
     * Manually trigger points earning for orders (admin function).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function adminAwardPoints(Request $request)
    {
        // Validate request
        $validated = $request->validate([
            'order_id' => 'required|exists:madkrapow_orders,order_id',
        ]);
        
        $orderId = $validated['order_id'];
        $order = MadkrapowOrder::with(['user', 'orderItems.product'])
            ->where('order_id', $orderId)
            ->firstOrFail();
            
        // Get the user associated with the order
        $user = $order->user;
        
        // Only award points for completed orders
        if ($order->status !== 'completed') {
            return redirect()->back()->with('error', 'Points can only be awarded for completed orders.');
        }
        
        // Calculate total amount
        $transactionAmount = number_format($order->order_total, 2, '.', '');
        
        // Create payload for Grab Points Earning API
        $source = 'purchase';
        $sourceID = 'order_' . $order->order_id;
        $description = 'Points earned from purchase on Madkrapow.com';
        $payload = [
            [
                'name' => 'transactionAmount',
                'value' => $transactionAmount
            ]
        ];
        
        // Call the Points Earning API
        $result = $this->grabService->awardPoints(
            $user,
            $source,
            $sourceID,
            $description,
            $payload
        );
        
        // Handle the result
        if ($result['success']) {
            // Update order to mark points as awarded
            $order->points_awarded = true;
            $order->points_awarded_at = now();
            $order->save();
            
            Log::info('Points awarded manually by admin', [
                'user_id' => $user->user_id,
                'order_id' => $orderId,
                'points' => $result['points'],
                'admin_id' => Auth::id()
            ]);
            
            return redirect()->back()->with('success', 'Points have been awarded for order #' . $order->order_id);
        } else {
            Log::error('Failed to award points (admin attempt)', [
                'user_id' => $user->user_id,
                'order_id' => $orderId,
                'error' => $result['error'],
                'admin_id' => Auth::id()
            ]);
            
            return redirect()->back()->with('error', 'Unable to award points: ' . $result['error']);
        }
    }
} 