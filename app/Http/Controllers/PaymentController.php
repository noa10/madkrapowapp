<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\MadkrapowOrder;
use App\Models\MadkrapowOrderItem;
use App\Models\MadkrapowProduct;
use App\Models\MadkrapowPayment;
use App\Services\BillplzService;
use Carbon\Carbon;

class PaymentController extends Controller
{
    protected $billplzService;

    public function __construct(BillplzService $billplzService)
    {
        $this->billplzService = $billplzService;
    }

    /**
     * Start the OCBC authorization process for QR payment
     *
     * @param int $orderId
     * @return \Illuminate\Http\Response
     */
    public function startOcbcAuthorization($orderId)
    {
        \Log::info('Starting OCBC authorization with orderId: ' . $orderId);
        
        try {
            // Debug: Try to find all orders for the current user
            $userId = auth()->id();
            $allOrders = MadkrapowOrder::where('user_id', $userId)->get();
            \Log::info('All orders for user ' . $userId, [
                'count' => $allOrders->count(),
                'orders' => $allOrders->map(function($o) {
                    return ['id' => $o->id, 'order_id' => $o->order_id];
                })
            ]);
            
            // Try to find the order using both id and order_id fields
            $order = null;
            
            // First, try direct lookup by ID
            $directOrder = MadkrapowOrder::find($orderId);
            if ($directOrder) {
                $order = $directOrder;
                \Log::info('Order found directly by ID', ['id' => $order->id, 'order_id' => $order->order_id]);
            } else {
                // Try by order_id field
                $orderByOrderId = MadkrapowOrder::where('order_id', $orderId)->first();
                if ($orderByOrderId) {
                    $order = $orderByOrderId;
                    \Log::info('Order found by order_id field', ['id' => $order->id, 'order_id' => $order->order_id]);
                } else {
                    // Try with the most recent order as last resort
                    $latestOrder = MadkrapowOrder::where('user_id', $userId)
                        ->orderBy('created_at', 'desc')
                        ->first();
                    
                    if ($latestOrder) {
                        $order = $latestOrder;
                        \Log::warning('Used latest order as fallback', [
                            'requested_id' => $orderId,
                            'fallback_id' => $order->id,
                            'fallback_order_id' => $order->order_id
                        ]);
                    } else {
                        throw new \Exception('No orders found for the current user');
                    }
                }
            }
            
            if (!$order) {
                throw new \Exception('Order not found with any ID: ' . $orderId);
            }
            
            // Store both IDs in session
            session(['ocbc_pending_order_id' => $order->id]);
            session(['ocbc_order_id' => $order->order_id]);
            session(['ocbc_order_real_id' => $order->id]); // Primary key, even if it's named differently
            
            \Log::info('Order found successfully', [
                'order_id' => $order->order_id,
                'id' => $order->id,
                'user_id' => $order->user_id,
                'total_amount' => $order->total_amount
            ]);
            
            // Generate the authorization URL
            $returnUrl = route('payments.ocbc.callback');
            
            $authResponse = $this->ocbcService->initiateAuthorization(
                $order->total_amount,
                $order->id,
                $returnUrl
            );
            
            if (!$authResponse) {
                \Log::error('OCBC authorization initiation failed', ['order_id' => $order->id]);
                return redirect()->route('checkout.index')->with('error', 'Failed to initiate OCBC payment authorization. Please try again.');
            }
            
            // Check for fallback mode (testing)
            if (isset($authResponse['fallback']) && $authResponse['fallback']) {
                // Handle fallback mode directly by redirecting to QR page
                \Log::info('Using fallback mode - redirecting to QR page', [
                    'order_id' => $order->id,
                    'order_real_id' => $order->order_id
                ]);
                
                // Generate a fake token for testing
                $fakeToken = 'test_token_' . time();
                session(['ocbc_access_token' => $fakeToken]);
                
                // Store all possible IDs to ensure we can find the order later
                session(['ocbc_pending_order_id' => $order->id]);
                session(['ocbc_order_id' => $order->order_id]);
                session(['ocbc_order_real_id' => $order->getKey()]);
                session(['checkout_order_id' => $order->order_id]);
                session(['checkout_order_real_id' => $order->id]);
                
                // Log all session data for debugging
                \Log::info('Set session data for order', [
                    'ocbc_pending_order_id' => $order->id,
                    'ocbc_order_id' => $order->order_id,
                    'ocbc_order_real_id' => $order->getKey(),
                    'checkout_order_id' => $order->order_id,
                    'checkout_order_real_id' => $order->id
                ]);
                
                // Redirect directly to the QR page with the primary key
                return redirect()->route('payments.ocbc.qr', ['orderId' => $order->getKey()]);
            }
            
            if (!isset($authResponse['authorizationUrl'])) {
                \Log::error('OCBC authorization failed - missing URL', ['order_id' => $order->id]);
                return redirect()->route('checkout.index')->with('error', 'Failed to generate authorization URL. Please try again.');
            }
            
            \Log::info('Redirecting to OCBC authorization page', ['url' => $authResponse['authorizationUrl']]);
            
            // Redirect to OCBC authorization page
            return redirect($authResponse['authorizationUrl']);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error("Order not found with id {$orderId}", ['error' => $e->getMessage()]);
            return redirect()->route('checkout.index')
                ->with('error', 'Payment processing error: Order not found. Please try again or contact support.');
        } catch (\Exception $e) {
            \Log::error('OCBC authorization error', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('checkout.index')
                ->with('error', 'Payment processing error: ' . $e->getMessage());
        }
    }
    
    /**
     * Start the Stripe FPX Malaysia payment process
     *
     * @param int $orderId
     * @return \Illuminate\Http\Response
     */
    public function startStripeFpxPayment($orderId)
    {
        Log::info('Starting Stripe FPX payment with orderId: ' . $orderId);
        
        try {
            // Debug: Try to find all orders for the current user
            $userId = auth()->id();
            $allOrders = MadkrapowOrder::where('user_id', $userId)->get();
            Log::info('All orders for user ' . $userId, [
                'count' => $allOrders->count(),
                'orders' => $allOrders->map(function($o) {
                    return ['id' => $o->id, 'order_id' => $o->order_id];
                })
            ]);
            
            // Try to find the order using both id and order_id fields
            $order = null;
            
            // First, try direct lookup by ID
            $directOrder = MadkrapowOrder::find($orderId);
            if ($directOrder) {
                $order = $directOrder;
                Log::info('Order found directly by ID', ['id' => $order->id, 'order_id' => $order->order_id]);
            } else {
                // Try by order_id field
                $orderByOrderId = MadkrapowOrder::where('order_id', $orderId)->first();
                if ($orderByOrderId) {
                    $order = $orderByOrderId;
                    Log::info('Order found by order_id field', ['id' => $order->id, 'order_id' => $order->order_id]);
                } else {
                    // Try with the most recent order as last resort
                    $latestOrder = MadkrapowOrder::where('user_id', $userId)
                        ->orderBy('created_at', 'desc')
                        ->first();
                    
                    if ($latestOrder) {
                        $order = $latestOrder;
                        Log::warning('Used latest order as fallback', [
                            'requested_id' => $orderId,
                            'fallback_id' => $order->id,
                            'fallback_order_id' => $order->order_id
                        ]);
                    } else {
                        throw new \Exception('No orders found for the current user');
                    }
                }
            }
            
            if (!$order) {
                throw new \Exception('Order not found with any ID: ' . $orderId);
            }
            
            // Store order info in session
            session(['stripe_pending_order_id' => $order->id]);
            session(['stripe_order_id' => $order->order_id]);
            session(['stripe_order_real_id' => $order->id]); // Primary key
            
            Log::info('Order found successfully', [
                'order_id' => $order->order_id,
                'id' => $order->id,
                'user_id' => $order->user_id,
                'total_amount' => $order->total_amount
            ]);
            
            // Generate the payment URL
            $returnUrl = route('payments.stripe.callback');
            
            $paymentResponse = $this->stripeService->initiateFpxPayment(
                $order->total_amount,
                $order->id,
                $returnUrl
            );
            
            if (!$paymentResponse) {
                Log::error('Stripe FPX payment initiation failed', ['order_id' => $order->id]);
                return redirect()->route('checkout.index')->with('error', 'Failed to initiate payment. Please try again.');
            }
            
            // Check for fallback mode (testing)
            if (isset($paymentResponse['fallback']) && $paymentResponse['fallback']) {
                // Handle fallback mode directly
                Log::info('Using fallback mode for Stripe payment', [
                    'order_id' => $order->id,
                    'order_real_id' => $order->order_id
                ]);
                
                // Store all possible IDs to ensure we can find the order later
                session(['checkout_order_id' => $order->order_id]);
                session(['checkout_order_real_id' => $order->id]);
                
                // Log all session data for debugging
                Log::info('Set session data for order', [
                    'stripe_pending_order_id' => $order->id,
                    'stripe_order_id' => $order->order_id,
                    'stripe_order_real_id' => $order->getKey(),
                    'checkout_order_id' => $order->order_id,
                    'checkout_order_real_id' => $order->id
                ]);
                
                // Create payment record
                $payment = $this->createPaymentRecord($order, [
                    'payment_method' => 'stripe_fpx',
                    'payment_status' => 'pending',
                    'amount' => $order->total_amount,
                    'payment_date' => now(),
                    'reference_id' => 'test_' . time()
                ]);
                
                // Redirect to success page for testing
                return redirect()->route('checkout.success', ['orderId' => $order->order_id])
                    ->with('success', 'Test payment successful. This is a test environment.');
            }
            
            // Create payment record
            $payment = $this->createPaymentRecord($order, [
                'payment_method' => 'stripe_fpx',
                'payment_status' => 'pending',
                'amount' => $order->total_amount,
                'payment_date' => now(),
                'reference_id' => $paymentResponse['paymentId'] ?? null
            ]);
            
            if (!isset($paymentResponse['checkoutUrl'])) {
                Log::error('Stripe payment failed - missing checkout URL', ['order_id' => $order->id]);
                return redirect()->route('checkout.index')->with('error', 'Failed to generate payment URL. Please try again.');
            }
            
            Log::info('Redirecting to Stripe checkout page', ['url' => $paymentResponse['checkoutUrl']]);
            
            // Redirect to Stripe checkout page
            return redirect($paymentResponse['checkoutUrl']);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error("Order not found with id {$orderId}", ['error' => $e->getMessage()]);
            return redirect()->route('checkout.index')
                ->with('error', 'Payment processing error: Order not found. Please try again or contact support.');
        } catch (\Exception $e) {
            Log::error('Stripe payment error', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('checkout.index')
                ->with('error', 'Payment processing error: ' . $e->getMessage());
        }
    }
    
    /**
     * Initiate a Stripe FPX Malaysia payment
     *
     * @param Request $request
     * @param int $orderId
     * @return \Illuminate\Http\Response
     */
    public function initiateStripeFpxPayment(Request $request, $orderId)
    {
        try {
            \Log::info('Initiating Stripe FPX payment', [
                'order_id' => $orderId
            ]);
            
            // Get order from database
            $order = MadkrapowOrder::where('order_id', $orderId)->first();
            
            if (!$order) {
                \Log::error('Order not found for Stripe FPX payment', [
                    'order_id' => $orderId
                ]);
                
                return redirect()->route('checkout.index')
                    ->with('error', 'Order not found. Please try again.');
            }
            
            // Check if order is already paid
            if ($order->status === 'paid') {
                \Log::info('Order already paid', [
                    'order_id' => $orderId
                ]);
                
                return redirect()->route('checkout.success', ['orderId' => $order->order_id])
                    ->with('success', 'Your order has already been paid.');
            }
            
            // Check if Stripe is enabled and configured
            if (!config('services.stripe.key') || !config('services.stripe.secret')) {
                \Log::error('Stripe not configured for FPX payment');
                
                return redirect()->route('checkout.index')
                    ->with('error', 'Payment method not available. Please try another payment method.');
            }
            
            // Store order ID in session
            session(['checkout_order_id' => $order->order_id]);
            
            // Initiate Stripe FPX payment
            $returnUrl = route('payments.stripe.fpx.callback');
            $result = $this->stripeService->initiateFpxPayment(
                $order->total_amount,
                $order->order_id,
                $returnUrl
            );
            
            if (!$result['success']) {
                \Log::error('Failed to initiate Stripe FPX payment', [
                    'order_id' => $orderId,
                    'error' => $result['message']
                ]);
                
                return redirect()->route('checkout.index')
                    ->with('error', 'Failed to initiate payment: ' . $result['message']);
            }
            
            // Update payment record with Stripe payment intent ID
            $payment = MadkrapowPayment::where('order_id', $order->order_id)
                ->where('payment_method', 'stripe_fpx')
                ->orderBy('created_at', 'desc')
                ->first();
            
            if ($payment) {
                $payment->reference_id = $result['payment_intent_id'];
                $payment->save();
                
                \Log::info('Payment record updated with Stripe payment intent ID', [
                    'payment_id' => $payment->id,
                    'payment_intent_id' => $result['payment_intent_id']
                ]);
            }
            
            // Redirect to Stripe FPX bank selection page
            return view('payments.stripe-fpx-callback', [
                'clientSecret' => $result['client_secret'],
                'paymentIntentId' => $result['payment_intent_id'],
                'stripeKey' => config('services.stripe.key')
            ]);
        } catch (\Exception $e) {
            \Log::error('Error initiating Stripe FPX payment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('checkout.index')
                ->with('error', 'An error occurred while processing your payment: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle the callback from Stripe FPX Malaysia payment
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function stripeFpxCallback(Request $request)
    {
        try {
            // Check for different parameter formats that Stripe might use
            $paymentIntentId = $request->input('payment_intent') ?? $request->input('payment_intent_id');
            $clientSecret = $request->input('payment_intent_client_secret') ?? $request->input('client_secret');
            
            \Log::info('Stripe FPX callback received', [
                'payment_intent_id' => $paymentIntentId,
                'parameters' => $request->all()
            ]);
            
            // If we have a payment intent but no client secret, redirect to the callback page with the proper parameters
            if ($paymentIntentId && !$request->has('payment_intent_id')) {
                \Log::info('Redirecting to FPX callback page with correct parameters', [
                    'payment_intent_id' => $paymentIntentId
                ]);
                
                return view('payments.stripe-fpx-callback', [
                    'payment_intent_id' => $paymentIntentId,
                    'client_secret' => $clientSecret
                ]);
            }
            
            if (!$paymentIntentId) {
                \Log::error('Missing payment intent ID in callback');
                
                return redirect()->route('checkout.index')
                    ->with('error', 'Payment information missing. Please try again.');
            }
            
            // Validate payment status
            $paymentStatus = $this->stripeService->validatePaymentStatus($paymentIntentId);
            
            // Get order ID from session
            $orderId = session('checkout_order_id');
            
            if (!$orderId) {
                \Log::error('Order ID not found in session for Stripe FPX callback');
                
                return redirect()->route('checkout.index')
                    ->with('error', 'Order information missing. Please try again.');
            }
            
            // Get order from database
            $order = MadkrapowOrder::where('order_id', $orderId)->first();
            
            if (!$order) {
                \Log::error('Order not found for Stripe FPX callback', [
                    'order_id' => $orderId
                ]);
                
                return redirect()->route('checkout.index')
                    ->with('error', 'Order not found. Please try again.');
            }
            
            // Update payment record
            $payment = MadkrapowPayment::where('order_id', $order->order_id)
                ->where('reference_id', $paymentIntentId)
                ->first();
            
            if ($payment) {
                $payment->status = $paymentStatus['success'] ? 'completed' : 
                    ($paymentStatus['pending'] ? 'processing' : 'failed');
                
                if ($paymentStatus['success']) {
                    $payment->payment_date = Carbon::now();
                }
                
                $payment->save();
                
                \Log::info('Payment record updated', [
                    'payment_id' => $payment->id,
                    'status' => $payment->status
                ]);
            }
            
            // Update order status if payment successful
            if ($paymentStatus['success']) {
                $order->status = 'paid';
                $order->save();
                
                \Log::info('Order status updated to paid in callback', [
                    'order_id' => $order->order_id
                ]);
                
                // Clear cart if payment successful
                if (Auth::check()) {
                    \App\Models\MadkrapowCartItem::where('user_id', Auth::id())->delete();
                }
                
                return redirect()->route('checkout.success', ['orderId' => $order->order_id])
                    ->with('success', 'Your payment was successful!');
            } else if ($paymentStatus['pending']) {
                return view('payments.stripe-fpx-callback', [
                    'clientSecret' => $clientSecret,
                    'paymentIntentId' => $paymentIntentId,
                    'stripeKey' => config('services.stripe.key')
                ]);
            } else {
                return redirect()->route('checkout.index')
                    ->with('error', 'Payment failed: ' . ($paymentStatus['message'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            \Log::error('Error handling Stripe FPX callback', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('checkout.index')
                ->with('error', 'An error occurred while processing your payment: ' . $e->getMessage());
        }
    }

    /**
     * Handle the callback from OCBC authorization
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function handleOcbcCallback(Request $request)
    {
        \Log::info('OCBC Callback received', ['request_data' => $request->all()]);
        
        // Check for error parameter
        if ($request->has('error')) {
            \Log::error('OCBC authorization error', ['error' => $request->input('error')]);
            return redirect()->route('checkout.index')
                ->with('error', 'Authorization failed: ' . $request->input('error'));
        }
        
        // For test environment - if we're missing credentials, handle this case
        if (empty(config('services.ocbc.client_id')) || empty(config('services.ocbc.bank_id'))) {
            \Log::warning('Using test mode for OCBC callback due to missing credentials');
            
            // Generate a fake token for testing
            $fakeToken = 'test_token_' . time();
            session(['ocbc_access_token' => $fakeToken]);
            
            // Try to get the order ID from the state parameter
            $orderId = null;
            if ($request->has('state')) {
                try {
                    $stateData = json_decode(base64_decode($request->input('state')), true);
                    $orderId = $stateData['order_id'] ?? null;
                } catch (\Exception $e) {
                    $orderId = session('ocbc_order_id');
                }
            }
            
            if (!$orderId) {
                $orderId = session('ocbc_order_id');
            }
            
            // Get order ID from various possible session keys if still not found
            if (!$orderId) {
                $orderId = session('ocbc_pending_order_id') ?? 
                           session('ocbc_order_real_id') ?? 
                           session('checkout_order_id') ?? 
                           session('checkout_order_real_id');
                
                \Log::info('Using order ID from secondary session sources', ['orderId' => $orderId]);
            }
            
            if ($orderId) {
                \Log::info('Redirecting to QR page with orderId: ' . $orderId);
                return redirect()->route('payments.ocbc.qr', ['orderId' => $orderId]);
            }
            
            return redirect()->route('checkout.index')->with('error', 'Test mode: Missing order ID');
        }
        
        // Check for state parameter which contains our order information
        $state = $request->input('state');
        
        if (!$state) {
            \Log::error('OCBC callback missing state parameter');
            return redirect()->route('checkout.index')->with('error', 'Authorization failed. Missing state parameter.');
        }
        
        try {
            // Decode state to get order ID and amount
            $stateData = json_decode(base64_decode($state), true);
            $orderId = $stateData['order_id'] ?? null;
            $requestId = $stateData['request_id'] ?? null;
            
            // Verify this matches our session data
            $pendingOrderId = session('ocbc_order_id');
            $pendingRequestId = session('ocbc_request_id');
            
            if (!$orderId || $orderId != $pendingOrderId) {
                \Log::error("OCBC callback state mismatch. Expected order: $pendingOrderId, Got: $orderId");
                return redirect()->route('checkout.index')->with('error', 'Payment verification failed. Invalid order data.');
            }
            
            if ($requestId != $pendingRequestId) {
                \Log::error("OCBC callback request ID mismatch. Expected: $pendingRequestId, Got: $requestId");
                return redirect()->route('checkout.index')->with('error', 'Payment verification failed. Invalid request data.');
            }
            
            // Get order details
            $order = MadkrapowOrder::findOrFail($orderId);
            
            // Note: According to OCBC documentation, the access token is returned in the URL hash fragment
            // Since PHP cannot access the hash fragment directly (it's client-side only),
            // we'll render a special page that will extract the token using JavaScript
            // and then redirect to the QR payment page
            
            return view('payments.ocbc-callback', [
                'orderId' => $orderId,
                'returnUrl' => route('payments.ocbc.qr', ['orderId' => $orderId])
            ]);
            
        } catch (\Exception $e) {
            \Log::error('OCBC Callback Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('checkout.index')->with('error', 'Payment processing error: ' . $e->getMessage());
        }
    }

    /**
     * Handle the callback from Stripe FPX payment
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function handleStripeFpxCallback(Request $request)
    {
        Log::info('Stripe FPX Callback received', ['request_data' => $request->all()]);
        
        // Get the payment intent ID and client secret from the request
        $paymentIntentId = $request->input('payment_intent_id');
        $clientSecret = $request->input('client_secret');
        
        if (!$paymentIntentId) {
            Log::error('Stripe FPX callback missing payment intent ID');
            return redirect()->route('checkout.index')
                ->with('error', 'Payment processing error: Missing payment information.');
        }
        
        try {
            // Validate the payment status
            $paymentStatus = $this->stripeService->validatePaymentStatus($paymentIntentId);
            
            $orderId = session('stripe_pending_order_id');
            if (!$orderId) {
                // Try to get the order ID from the payment metadata
                $orderId = $paymentStatus['metadata']['order_id'] ?? null;
            }
            
            if (!$orderId) {
                throw new \Exception('Order ID not found in session or payment metadata');
            }
            
            $order = MadkrapowOrder::find($orderId);
            if (!$order) {
                throw new \Exception('Order not found: ' . $orderId);
            }
            
            // Update payment record
            $payment = MadkrapowPayment::where('order_id', $order->id)
                ->where('reference_id', $paymentIntentId)
                ->first();
                
            if ($payment) {
                $payment->status = $paymentStatus['success'] ? 'completed' : 
                    ($paymentStatus['pending'] ? 'processing' : 'failed');
                $payment->payment_date = now();
                $payment->save();
                
                Log::info('Payment record updated', [
                    'payment_id' => $payment->id,
                    'status' => $payment->status
                ]);
            } else {
                // Create new payment record if one doesn't exist
                $payment = $this->createPaymentRecord($order, [
                    'payment_method' => 'stripe_fpx',
                    'payment_status' => $paymentStatus['success'] ? 'completed' : 
                        ($paymentStatus['pending'] ? 'processing' : 'failed'),
                    'amount' => $paymentStatus['amount'] ?? $order->total_amount,
                    'payment_date' => now(),
                    'reference_id' => $paymentIntentId
                ]);
            }
            
            // If payment is successful or pending, redirect to success page
            if ($paymentStatus['success'] || $paymentStatus['pending']) {
                // Update order status
                $order->order_status = $paymentStatus['success'] ? 'paid' : 'payment_pending';
                $order->save();
                
                return redirect()->route('checkout.success', ['orderId' => $order->order_id])
                    ->with('success', 'Payment ' . ($paymentStatus['success'] ? 'successful' : 'is being processed') . '!');
            } else {
                // Payment failed
                Log::error('Stripe FPX payment failed', [
                    'order_id' => $order->id,
                    'payment_intent_id' => $paymentIntentId,
                    'status' => $paymentStatus['status']
                ]);
                
                return redirect()->route('checkout.index')
                    ->with('error', 'Payment failed: ' . ($paymentStatus['error'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            Log::error('Stripe FPX callback error', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('checkout.index')
                ->with('error', 'Payment processing error: ' . $e->getMessage());
        }
    }

    /**
     * Process OCBC QR payment for an order
     *
     * @param int|null $orderId
     * @param string|null $accessToken
     * @return \Illuminate\Http\Response
     */
    public function processOcbcQrPayment($orderId = null, $accessToken = null)
    {
        \Log::info('Processing OCBC QR payment with orderId: ' . ($orderId ?? 'NULL'));
        
        try {
            // If orderId is null, try to get it from session
            if ($orderId === null) {
                $orderId = session('ocbc_order_real_id') ?? 
                          session('ocbc_order_id') ?? 
                          session('checkout_order_real_id') ?? 
                          session('checkout_order_id') ?? 
                          session('ocbc_pending_order_id');
                
                if ($orderId) {
                    \Log::info('Using order ID from session for QR payment', ['orderId' => $orderId]);
                } else {
                    \Log::error('No order ID found in request or session');
                    return redirect()->route('checkout.index')
                        ->with('error', 'Payment processing error: Missing order ID. Please try again.');
                }
            }
            
            // Try to find the order using both id and order_id fields
            $order = null;
            
            // Check the session for the real order ID first
            $sessionOrderId = session('ocbc_order_real_id');
            if ($sessionOrderId) {
                $sessionOrder = MadkrapowOrder::find($sessionOrderId);
                if ($sessionOrder) {
                    $order = $sessionOrder;
                    \Log::info('Order found from session ID', ['id' => $order->id, 'order_id' => $order->order_id]);
                }
            }
            
            // If not found in session, try direct lookup
            if (!$order) {
                // First, try direct lookup by ID
                $directOrder = MadkrapowOrder::find($orderId);
                if ($directOrder) {
                    $order = $directOrder;
                    \Log::info('Order found directly by ID', ['id' => $order->id, 'order_id' => $order->order_id]);
                } else {
                    // Try by order_id field
                    $orderByOrderId = MadkrapowOrder::where('order_id', $orderId)->first();
                    if ($orderByOrderId) {
                        $order = $orderByOrderId;
                        \Log::info('Order found by order_id field', ['id' => $order->id, 'order_id' => $order->order_id]);
                    } else {
                        // Try with the most recent order as last resort
                        $userId = auth()->id();
                        $latestOrder = MadkrapowOrder::where('user_id', $userId)
                            ->orderBy('created_at', 'desc')
                            ->first();
                        
                        if ($latestOrder) {
                            $order = $latestOrder;
                            \Log::warning('Used latest order as fallback', [
                                'requested_id' => $orderId,
                                'fallback_id' => $order->id,
                                'fallback_order_id' => $order->order_id
                            ]);
                        } else {
                            throw new \Exception('No orders found for the current user');
                        }
                    }
                }
            }
            
            if (!$order) {
                throw new \Exception('Order not found with any ID: ' . $orderId);
            }
            
            // Ensure we have the actual order ID and not just the orderId parameter
            $realOrderId = $order->id; // This is the database primary key
            $orderIdForFK = $order->order_id; // This might be different if order_id is the custom PK
            
            \Log::info('Order found successfully for QR payment', [
                'order_id' => $orderIdForFK,
                'id' => $realOrderId,
                'user_id' => $order->user_id,
                'total_amount' => $order->total_amount
            ]);
            
            // If no access token provided, use the one from session
            if (!$accessToken) {
                $accessToken = session('ocbc_access_token');
                \Log::info('Using access token from session', ['has_token' => !empty($accessToken)]);
            }
            
            // Test mode - if no credentials or in test environment
            $testMode = false;
            if (empty(config('services.ocbc.client_id')) || 
                empty(config('services.ocbc.bank_id')) || 
                strpos($accessToken, 'test_token_') === 0) {
                
                $testMode = true;
                \Log::info('Using test mode for QR payment');
                
                // Generate test payment data
                $paymentResponse = $this->generateTestPaymentData($order);
                
            } else {
                // If still no access token, start authorization flow
                if (!$accessToken) {
                    \Log::info('No access token found, starting authorization flow');
                    return $this->startOcbcAuthorization($orderId);
                }
                
                // Call OCBC API to generate real QR payment
                $paymentResponse = $this->ocbcService->generateQrPayment(
                    $accessToken,
                    $order->total_amount,
                    'ORDER-' . $order->id
                );
            }
            
            if (!$paymentResponse) {
                \Log::error('Failed to generate QR payment data');
                return redirect()->route('checkout.index')->with('error', 'Unable to generate QR payment. Please try again.');
            }
            
            // Save the payment reference
            $payment = MadkrapowPayment::where('order_id', $orderIdForFK)
                ->where('payment_method', 'duitnow_qr')
                ->first();
                
            if (!$payment) {
                $payment = new MadkrapowPayment();
                $payment->order_id = $orderIdForFK;
                $payment->payment_method = 'duitnow_qr';
                $payment->amount = $order->total_amount;
            }
            
            $payment->status = 'pending';
            $payment->reference_id = $paymentResponse['paymentId'] ?? null; // Using reference_id instead of stripe_payment_id
            
            // Store additional data if available
            if (!empty($paymentResponse)) {
                $payment->transaction_details = $paymentResponse;
            }
            
            // Add debug logging for the payment record
            \Log::info('About to save payment record', [
                'payment' => [
                    'order_id' => $payment->order_id,
                    'amount' => $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'reference_id' => $payment->reference_id
                ]
            ]);
            
            $payment->save();
            
            \Log::info('Payment record updated', ['payment_id' => $payment->payment_id, 'status' => $payment->status]);
            
            // Store QR data in session
            session(['ocbc_payment_id' => $paymentResponse['paymentId'] ?? null]);
            session(['ocbc_qr_data' => $paymentResponse['qrData'] ?? null]);
            
            \Log::info('Rendering QR payment page');
            
            return view('payments.qr', [
                'order' => $order,
                'qrData' => $paymentResponse['qrData'] ?? null, 
                'paymentId' => $paymentResponse['paymentId'] ?? null,
                'expiresAt' => Carbon::now()->addMinutes(30),
                'testMode' => $testMode,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error processing QR payment', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('checkout.index')
                ->with('error', 'Payment processing error: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate test payment data for development/testing
     *
     * @param MadkrapowOrder $order
     * @return array
     */
    private function generateTestPaymentData($order)
    {
        // Ensure we have the order ID (either id or order_id)
        $orderIdForReference = $order->order_id ?? $order->id ?? 'unknown';
        
        \Log::info('Generating test payment data', [
            'order_id' => $orderIdForReference,
            'real_id' => $order->id ?? 'unknown',
            'order_order_id' => $order->order_id ?? 'unknown'
        ]);
        
        return [
            'paymentId' => 'test_payment_' . time(),
            'qrData' => 'test_qr_data',
            'status' => 'PENDING',
            'expiryDate' => Carbon::now()->addMinutes(30)->format('Y-m-d H:i:s'),
            'amount' => number_format($order->total_amount, 2),
            'currency' => 'MYR',
            'merchantName' => config('services.ocbc.merchant_name', 'Mad Krapow'),
            'orderReference' => 'ORDER-' . $orderIdForReference
        ];
    }
    
    /**
     * OCBC payment callback handler
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function ocbcCallback(Request $request)
    {
        \Log::info('OCBC Payment Callback received', ['request_data' => $request->all()]);
        
        $paymentId = $request->input('paymentId');
        if (!$paymentId) {
            return response()->json(['status' => 'error', 'message' => 'Payment ID not provided'], 400);
        }
        
        // Verify payment status
        $paymentStatus = $this->ocbcService->verifyPayment($paymentId);
        
        if (!$paymentStatus) {
            return response()->json(['status' => 'error', 'message' => 'Payment verification failed'], 500);
        }
        
        // Find the payment record
        $payment = MadkrapowPayment::where('reference_id', $paymentId)->first();
        
        if (!$payment) {
            return response()->json(['status' => 'error', 'message' => 'Payment not found'], 404);
        }
        
        // Update payment status
        $statusFromBank = $paymentStatus['status'] ?? null;
        
        if ($statusFromBank === 'COMPLETED') {
            $payment->status = 'completed';
            $payment->payment_date = Carbon::now();
            $payment->save();
            
            // Update order status
            $order = MadkrapowOrder::find($payment->order_id);
            if ($order) {
                $order->status = 'paid';
                $order->save();
                
                // Store the order ID for redirect in session
                session(['completed_order_id' => $order->order_id]);
                
                \Log::info('Payment completed successfully via callback', [
                    'order_id' => $order->order_id,
                    'payment_id' => $payment->payment_id
                ]);
            }
            
            return response()->json([
                'status' => 'success', 
                'message' => 'Payment completed successfully',
                'redirect_url' => route('checkout.confirmation', ['id' => $payment->order_id])
            ]);
        } else if ($statusFromBank === 'FAILED') {
            $payment->status = 'failed';
            $payment->save();
            
            return response()->json([
                'status' => 'success', 
                'message' => 'Payment failed',
                'redirect_url' => route('checkout.index')
            ]);
        }
        
        return response()->json(['status' => 'success', 'message' => 'Payment status updated']);
    }
    
    /**
     * Check payment status manually
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkPaymentStatus(Request $request)
    {
        try {
            $orderId = session('checkout_order_id');
            $paymentIntentId = $request->input('payment_intent_id');
            
            \Log::info('Checking payment status', [
                'order_id' => $orderId,
                'payment_intent_id' => $paymentIntentId
            ]);
            
            if (!$orderId && !$paymentIntentId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No order or payment information found in session'
                ]);
            }
            
            if ($paymentIntentId) {
                // Check Stripe payment status
                $paymentStatus = $this->stripeService->validatePaymentStatus($paymentIntentId);
                
                // If payment data contains metadata with order_id, use that
                $metadataOrderId = $paymentStatus['metadata']['order_id'] ?? null;
                if ($metadataOrderId) {
                    $orderId = $metadataOrderId;
                }
                
                // If we still don't have an order ID, try to find it via the payment record
                if (!$orderId) {
                    $payment = MadkrapowPayment::where('reference_id', $paymentIntentId)->first();
                    if ($payment) {
                        $orderId = $payment->order_id;
                        \Log::info('Found order ID from payment record', [
                            'payment_id' => $payment->id,
                            'order_id' => $orderId
                        ]);
                    }
                }
                
                // Get order from database
                $order = null;
                if ($orderId) {
                    $order = MadkrapowOrder::where('id', $orderId)
                        ->orWhere('order_id', $orderId)
                        ->first();
                }
                
                if (!$order && $metadataOrderId) {
                    $order = MadkrapowOrder::where('id', $metadataOrderId)
                        ->orWhere('order_id', $metadataOrderId)
                        ->first();
                }
                
                if (!$order) {
                    \Log::error('Order not found for payment status check', [
                        'order_id' => $orderId,
                        'payment_intent_id' => $paymentIntentId
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Order not found'
                    ]);
                }
                
                // Update payment status in DB
                $payment = MadkrapowPayment::where('order_id', $order->id)
                    ->where('reference_id', $paymentIntentId)
                    ->first();
                
                if ($payment) {
                    $payment->status = $paymentStatus['success'] ? 'completed' : 
                        ($paymentStatus['pending'] ? 'processing' : 'failed');
                    $payment->save();
                    
                    \Log::info('Payment record updated', [
                        'payment_id' => $payment->id,
                        'status' => $payment->status
                    ]);
                }
                
                // Update order status
                if ($paymentStatus['success']) {
                    $order->status = 'paid';
                    $order->save();
                    
                    \Log::info('Order status updated to paid', [
                        'order_id' => $order->id
                    ]);
                }
                
                return response()->json([
                    'success' => true,
                    'status' => $paymentStatus['status'],
                    'order_id' => $order->order_id
                ]);
            } else {
                // Get order from database
                $order = MadkrapowOrder::where('order_id', $orderId)
                    ->orWhere('id', $orderId)
                    ->first();
                
                if (!$order) {
                    \Log::error('Order not found for payment status check', [
                        'order_id' => $orderId
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Order not found'
                    ]);
                }
                
                // Get latest payment for order
                $payment = MadkrapowPayment::where('order_id', $order->id)
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                if (!$payment) {
                    \Log::error('No payment found for order', [
                        'order_id' => $order->id
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'No payment found for order'
                    ]);
                }
                
                return response()->json([
                    'success' => true,
                    'status' => $payment->status === 'completed' ? 'succeeded' : 
                        ($payment->status === 'processing' ? 'processing' : 'failed'),
                    'order_id' => $order->order_id
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Error checking payment status', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error checking payment status: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Complete a test payment (for development/testing only)
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function completeTestPayment(Request $request)
    {
        \Log::info('Test payment completion initiated', $request->all());
        
        $orderId = $request->input('order_id');
        $paymentId = $request->input('payment_id');
        
        if (!$orderId || !$paymentId) {
            return redirect()->back()->with('error', 'Missing order or payment information');
        }
        
        // Find the payment record
        $payment = MadkrapowPayment::where('order_id', $orderId)
            ->where('reference_id', $paymentId)
            ->first();
        
        if (!$payment) {
            \Log::error('Test payment not found', ['order_id' => $orderId, 'payment_id' => $paymentId]);
            
            // If not found by reference_id, try to find the most recent payment for this order
            $payment = MadkrapowPayment::where('order_id', $orderId)
                ->orderBy('created_at', 'desc')
                ->first();
                
            if (!$payment) {
                return redirect()->back()->with('error', 'Payment record not found');
            }
            
            // Update the reference_id to match what was provided
            $payment->reference_id = $paymentId;
        }
        
        // Update payment status
        $payment->status = 'completed';
        $payment->payment_date = Carbon::now();
        $payment->save();
        
        // Update order status
        $order = MadkrapowOrder::find($orderId);
        if (!$order) {
            // Try with order_id if finding by id fails
            $order = MadkrapowOrder::where('order_id', $orderId)->first();
        }
        
        if ($order) {
            $order->status = 'paid';
            $order->save();
            
            \Log::info('Test payment completed successfully', [
                'order_id' => $orderId,
                'payment_id' => $payment->payment_id,
                'order_primary_key' => $order->getKey()
            ]);
            
            // Make sure we're using the order's primary key for redirection
            $confirmationId = $order->order_id; // Use the order_id field as it's the primary key
            
            // Debug logs to help trace the issue
            \Log::info('Redirecting to confirmation page with ID', [
                'confirmation_id' => $confirmationId,
                'order' => [
                    'id' => $order->id,
                    'order_id' => $order->order_id,
                    'primary_key' => $order->getKey(),
                ]
            ]);
            
            // Redirect to confirmation page with the appropriate ID
            return redirect()->route('checkout.confirmation', ['id' => $confirmationId])
                ->with('success', 'Test payment completed successfully');
        } else {
            \Log::error('Order not found for completed payment', ['order_id' => $orderId]);
            return redirect()->route('checkout.index')
                ->with('error', 'Payment completed but order not found.');
        }
    }

    /**
     * Store the access token received from OCBC's implicit flow
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeToken(Request $request)
    {
        $accessToken = $request->input('access_token');
        $orderId = $request->input('order_id');
        $expiresIn = $request->input('expires_in', 3600);
        
        if (!$accessToken || !$orderId) {
            \Log::error('Missing required parameters for storing token', [
                'has_token' => !empty($accessToken),
                'has_order_id' => !empty($orderId)
            ]);
            return response()->json(['success' => false, 'message' => 'Missing required parameters'], 400);
        }
        
        try {
            // Store token in session
            session(['ocbc_access_token' => $accessToken]);
            session(['ocbc_token_expiry' => now()->addSeconds($expiresIn)->timestamp]);
            session(['ocbc_order_id' => $orderId]);
            
            // Verify the order exists and store both potential keys
            try {
                $order = MadkrapowOrder::findOrFail($orderId);
                session(['ocbc_order_real_id' => $order->order_id]); // Primary key
            } catch (\Exception $e) {
                \Log::warning("Order not found with id {$orderId} for token storage", ['error' => $e->getMessage()]);
                try {
                    $order = MadkrapowOrder::where('order_id', $orderId)->firstOrFail();
                    session(['ocbc_order_real_id' => $order->order_id]);
                } catch (\Exception $e2) {
                    \Log::error("Order not found with either id or order_id {$orderId}", [
                        'error' => $e2->getMessage()
                    ]);
                }
            }
            
            \Log::info('OCBC token stored successfully', [
                'order_id' => $orderId,
                'token_expires_in' => $expiresIn
            ]);
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Log::error('Error storing OCBC token', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => 'Error storing token'], 500);
        }
    }
    
    /**
     * Create a payment record for an order
     *
     * @param MadkrapowOrder $order
     * @param array $data
     * @return MadkrapowPayment
     */
    private function createPaymentRecord($order, $data)
    {
        try {
            $payment = new MadkrapowPayment();
            $payment->order_id = $order->getKey();  // Use getKey() to get the primary key value
            $payment->payment_method = $data['payment_method'];
            $payment->amount = $data['amount'] ?? $order->total_amount;
            $payment->status = $data['payment_status'] ?? 'pending';
            $payment->payment_date = $data['payment_date'] ?? now();
            $payment->reference_id = $data['reference_id'] ?? null;
            
            // Handle transaction details if provided
            if (isset($data['transaction_details'])) {
                // Since we've set the 'transaction_details' attribute to cast as an array in the model,
                // Laravel will automatically JSON encode it when saving to the database
                $payment->transaction_details = $data['transaction_details'];
            }
            
            $payment->save();
            
            Log::info('Payment record created successfully', [
                'order_id' => $order->getKey(),
                'payment_id' => $payment->payment_id,
                'method' => $payment->payment_method,
                'status' => $payment->status
            ]);
            
            return $payment;
        } catch (\Exception $e) {
            Log::error('Error creating payment record', [
                'order_id' => $order->getKey(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Handle Stripe webhook events
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function handleStripeWebhook(Request $request)
    {
        // Set Stripe API key
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
        
        $webhookSecret = config('services.stripe.webhook_secret');
        $payload = $request->getContent();
        $sigHeader = $request->server('HTTP_STRIPE_SIGNATURE');
        $event = null;
        
        try {
            if ($webhookSecret) {
                // Verify webhook signature and extract the event
                $event = \Stripe\Webhook::constructEvent(
                    $payload, $sigHeader, $webhookSecret
                );
            } else {
                // If no webhook secret is configured, just create the event from payload
                $event = json_decode($payload, true);
            }
            
            \Log::info('Stripe webhook received', [
                'type' => $event->type,
                'event_id' => $event->id
            ]);
            
            // Handle specific event types
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $paymentIntent = $event->data->object;
                    return $this->handleSuccessfulPayment($paymentIntent);
                
                case 'payment_intent.payment_failed':
                    $paymentIntent = $event->data->object;
                    return $this->handleFailedPayment($paymentIntent);
                
                case 'payment_intent.processing':
                    $paymentIntent = $event->data->object;
                    return $this->handleProcessingPayment($paymentIntent);
                
                default:
                    \Log::info('Unhandled Stripe webhook event', ['type' => $event->type]);
                    return response()->json(['status' => 'success', 'message' => 'Webhook received but not processed']);
            }
        } catch (\UnexpectedValueException $e) {
            \Log::error('Stripe webhook error - Invalid payload', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['status' => 'error', 'message' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            \Log::error('Stripe webhook error - Invalid signature', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 400);
        } catch (\Exception $e) {
            \Log::error('Stripe webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Handle successful payment from Stripe webhook
     *
     * @param \Stripe\PaymentIntent $paymentIntent
     * @return \Illuminate\Http\Response
     */
    private function handleSuccessfulPayment($paymentIntent)
    {
        try {
            \Log::info('Processing successful payment', [
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $paymentIntent->amount,
                'metadata' => $paymentIntent->metadata->toArray()
            ]);
            
            $orderId = $paymentIntent->metadata->order_id ?? null;
            
            if (!$orderId) {
                \Log::error('Order ID not found in payment intent metadata', [
                    'payment_intent_id' => $paymentIntent->id
                ]);
                return response()->json(['status' => 'error', 'message' => 'Order ID not found'], 400);
            }
            
            // Get order from database
            $order = MadkrapowOrder::where('id', $orderId)
                ->orWhere('order_id', $orderId)
                ->first();
            
            if (!$order) {
                \Log::error('Order not found for payment', [
                    'order_id' => $orderId,
                    'payment_intent_id' => $paymentIntent->id
                ]);
                return response()->json(['status' => 'error', 'message' => 'Order not found'], 404);
            }
            
            // Update order status
            $order->order_status = 'paid';
            $order->save();
            
            // Update payment record
            $payment = MadkrapowPayment::where('order_id', $order->id)
                ->where(function($query) use ($paymentIntent) {
                    $query->where('reference_id', $paymentIntent->id);
                })
                ->first();
            
            if ($payment) {
                $payment->status = 'completed';
                $payment->payment_date = Carbon::now();
                $payment->save();
                
                \Log::info('Payment record updated', [
                    'payment_id' => $payment->id,
                    'order_id' => $order->id
                ]);
            } else {
                // Create new payment record if one doesn't exist
                $payment = new MadkrapowPayment();
                $payment->order_id = $order->id;
                $payment->amount = $paymentIntent->amount / 100; // Convert from cents
                $payment->payment_method = 'stripe_fpx';
                $payment->status = 'completed';
                $payment->payment_date = Carbon::now();
                $payment->reference_id = $paymentIntent->id;
                $payment->save();
                
                \Log::info('New payment record created', [
                    'payment_id' => $payment->id,
                    'order_id' => $order->id
                ]);
            }
            
            return response()->json(['status' => 'success', 'message' => 'Payment processed successfully']);
        } catch (\Exception $e) {
            \Log::error('Error processing successful payment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Handle failed payment from Stripe webhook
     *
     * @param \Stripe\PaymentIntent $paymentIntent
     * @return \Illuminate\Http\Response
     */
    private function handleFailedPayment($paymentIntent)
    {
        try {
            \Log::info('Handling failed payment', [
                'payment_intent_id' => $paymentIntent->id
            ]);
            
            // Find the order from the metadata
            $orderId = $paymentIntent->metadata->order_id ?? null;
            
            if (!$orderId) {
                \Log::error('No order ID in payment intent metadata');
                return;
            }
            
            $order = MadkrapowOrder::where('order_id', $orderId)->first();
            
            if (!$order) {
                \Log::error('Order not found', [
                    'order_id' => $orderId
                ]);
                return;
            }
            
            // Update order status
            $order->status = 'payment_failed';
            $order->save();
            
            // Update payment record if it exists
            $payment = MadkrapowPayment::where('order_id', $order->order_id)
                ->where(function($query) use ($paymentIntent) {
                    $query->where('reference_id', $paymentIntent->id);
                })
                ->first();
            
            if ($payment) {
                $payment->status = 'failed';
                $payment->save();
                
                \Log::info('Payment record updated as failed', [
                    'payment_id' => $payment->id,
                    'order_id' => $order->id
                ]);
            }
            
            return response()->json([
                'status' => 'success', 
                'message' => 'Failed payment recorded'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error processing failed payment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Handle processing payment from Stripe webhook
     *
     * @param \Stripe\PaymentIntent $paymentIntent
     * @return \Illuminate\Http\Response
     */
    private function handleProcessingPayment($paymentIntent)
    {
        try {
            \Log::info('Handling processing payment', [
                'payment_intent_id' => $paymentIntent->id
            ]);
            
            // Find the order from the metadata
            $orderId = $paymentIntent->metadata->order_id ?? null;
            
            if (!$orderId) {
                \Log::error('No order ID in payment intent metadata');
                return;
            }
            
            $order = MadkrapowOrder::where('order_id', $orderId)->first();
            
            if (!$order) {
                \Log::error('Order not found', [
                    'order_id' => $orderId
                ]);
                return;
            }
            
            // Update payment record if it exists
            $payment = MadkrapowPayment::where('order_id', $order->order_id)
                ->where(function($query) use ($paymentIntent) {
                    $query->where('reference_id', $paymentIntent->id);
                })
                ->first();
            
            if ($payment) {
                $payment->status = 'processing';
                $payment->save();
                
                \Log::info('Payment record updated as processing', [
                    'payment_id' => $payment->id,
                    'order_id' => $order->id
                ]);
            }
            
            return response()->json(['status' => 'success', 'message' => 'Processing payment recorded']);
        } catch (\Exception $e) {
            \Log::error('Error handling processing payment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Create a test order for payment testing
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createTestOrder(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'amount' => 'required|numeric|min:1',
                'description' => 'required|string|max:255'
            ]);
            
            \Log::info('Creating test order', [
                'amount' => $validated['amount'],
                'description' => $validated['description']
            ]);
            
            // Generate a unique order ID
            $orderNumber = 'TEST-' . strtoupper(Str::random(6)) . '-' . date('Ymd');
            
            // Create an order record
            $order = new MadkrapowOrder();
            $order->user_id = Auth::id();
            $order->total_amount = $validated['amount'];
            $order->shipping_cost = 0;
            $order->status = 'pending';
            $order->order_date = Carbon::now();
            
            if (!$order->save()) {
                throw new \Exception('Failed to create test order');
            }
            
            \Log::info('Test order created', [
                'order_id' => $order->order_id,
                'status' => $order->status
            ]);
            
            // Find an existing product to use for the test order
            $product = MadkrapowProduct::first();
            
            if (!$product) {
                // If no products exist, create a test product
                $product = new MadkrapowProduct();
                $product->product_name = 'Test Product';
                $product->price = $validated['amount'];
                $product->stock_quantity = 999;
                $product->description = 'Test product for test orders';
                $product->save();
                
                \Log::info('Created test product', [
                    'product_id' => $product->product_id
                ]);
            }
            
            // Create a test order item
            $orderItem = new MadkrapowOrderItem();
            $orderItem->order_id = $order->order_id;
            $orderItem->product_id = $product->product_id; // Use a real product
            $orderItem->quantity = 1;
            $orderItem->price_at_purchase = $validated['amount'];
            $orderItem->save();
            
            // Create payment record
            $payment = new MadkrapowPayment();
            $payment->order_id = $order->order_id;
            $payment->amount = $validated['amount'];
            $payment->payment_date = null;
            $payment->payment_method = 'stripe_fpx';
            $payment->status = 'pending';
            $payment->reference_id = 'test_' . Str::random(24); // Generate a test payment ID
            $payment->save();
            
            // Store order ID in session for redirect to payment page
            session(['checkout_order_id' => $order->order_id]);
            
            return response()->json([
                'success' => true,
                'message' => 'Test order created successfully',
                'order_id' => $order->order_id
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error creating test order', [
                'errors' => $e->errors()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error creating test order', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error creating test order: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Initiate payment via Stripe Payment Link
     *
     * @param int $orderId
     * @return \Illuminate\Http\Response
     */
    public function initiatePaymentLink($orderId)
    {
        Log::info('Initiating Stripe Payment Link for order ID: ' . $orderId);
        
        try {
            // Find the order
            $order = MadkrapowOrder::find($orderId);
            if (!$order) {
                // Try by order_id field
                $order = MadkrapowOrder::where('order_id', $orderId)->first();
                if (!$order) {
                    throw new \Exception('Order not found: ' . $orderId);
                }
            }
            
            // Verify order belongs to current user
            if ($order->user_id != Auth::id()) {
                throw new \Exception('Unauthorized access to order.');
            }
            
            // Create payment link
            $paymentLinkResponse = $this->stripeService->createPaymentLink(
                $order->total_amount,
                $order->order_id,
                'Order #' . $order->order_id . ' payment'
            );
            
            if (!$paymentLinkResponse['success']) {
                throw new \Exception('Failed to create payment link: ' . ($paymentLinkResponse['message'] ?? 'Unknown error'));
            }
            
            // Create payment record
            $payment = new MadkrapowPayment();
            $payment->order_id = $order->id;
            $payment->amount = $order->total_amount;
            $payment->payment_date = Carbon::now();
            $payment->payment_method = 'stripe_payment_link';
            $payment->reference_id = $paymentLinkResponse['payment_link_id'];
            $payment->payment_status = 'pending';
            
            if (!$payment->save()) {
                throw new \Exception('Payment record not created.');
            }
            
            // Update order status
            $order->status = 'pending';
            $order->save();
            
            // Store order ID in session for checkout flow
            session(['checkout_order_id' => $order->order_id]);
            session(['checkout_order_real_id' => $order->id]);
            
            // Redirect to the payment link
            return redirect($paymentLinkResponse['payment_link_url']);
            
        } catch (\Exception $e) {
            Log::error('Stripe Payment Link Error', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('checkout.index')
                ->with('error', 'Payment link creation failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle successful payment from Stripe Payment Link
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function paymentLinkSuccess(Request $request, $orderId)
    {
        Log::info('Payment Link Success callback received', [
            'order_id' => $orderId,
            'request_data' => $request->all()
        ]);
        
        try {
            // Find the order
            $order = MadkrapowOrder::where('order_id', $orderId)->first();
            if (!$order) {
                $order = MadkrapowOrder::find($orderId);
                if (!$order) {
                    throw new \Exception('Order not found: ' . $orderId);
                }
            }
            
            // Update payment status to completed
            $payment = MadkrapowPayment::where('order_id', $order->id)
                ->where('payment_method', 'stripe_payment_link')
                ->orderBy('created_at', 'desc')
                ->first();
            
            if ($payment) {
                $payment->payment_status = 'completed';
                $payment->save();
            }
            
            // Update order status to paid
            $order->status = 'paid';
            $order->save();
            
            // Return success view
            return view('payments.success', [
                'order' => $order,
                'payment_method' => 'stripe_payment_link'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error processing payment link success callback', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('checkout.index')
                ->with('error', 'Error processing payment confirmation: ' . $e->getMessage());
        }
    }

    /**
     * Initiate Billplz payment
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function initiateBillplzPayment(Request $request)
    {
        // Get orderId from query parameter or session
        $orderId = $request->query('orderId') ?? session('billplz_pending_order_id');
        
        Log::info('Starting Billplz payment', [
            'orderId' => $orderId,
            'query_params' => $request->all(),
            'session_data' => [
                'billplz_pending_order_id' => session('billplz_pending_order_id'),
                'billplz_order_id' => session('billplz_order_id')
            ]
        ]);
        
        try {
            // Extra validation to make sure we have a valid ID
            if (empty($orderId)) {
                Log::error('Empty orderId in Billplz initiation');
                return redirect()->route('checkout.index')
                    ->with('error', 'Missing order information. Please try again.');
            }
            
            // Find order by primary key first
            $order = MadkrapowOrder::find($orderId);
            
            // If not found, try by order number
            if (!$order) {
                $orderNumber = session('billplz_order_id');
                if ($orderNumber) {
                    $order = MadkrapowOrder::where('order_number', $orderNumber)->first();
                    
                    if ($order) {
                        Log::info('Order found by order_number', [
                            'order_number' => $orderNumber,
                            'primary_key' => $order->getKey()
                        ]);
                    }
                }
            }
            
            // If still not found, redirect to checkout
            if (!$order) {
                Log::error('Order not found with ID: ' . $orderId);
                return redirect()->route('checkout.index')
                    ->with('error', 'Order not found. Please try again.');
            }
            
            // Store order info in session
            session(['billplz_pending_order_id' => $order->getKey()]);
            session(['billplz_order_id' => $order->order_number ?? $order->getKey()]);
            
            Log::info('Order found successfully', [
                'primary_key' => $order->getKey(),
                'order_number' => $order->order_number ?? 'N/A',
                'user_id' => $order->user_id,
                'total_amount' => $order->total_amount
            ]);
            
            // Create a Billplz bill
            $billResponse = $this->billplzService->createBill($order);
            
            if (!$billResponse['success']) {
                Log::error('Billplz bill creation failed', [
                    'order_id' => $order->getKey(),
                    'error' => $billResponse['error']
                ]);
                return redirect()->route('checkout.index')->with('error', 'Failed to initiate payment. Please try again.');
            }
            
            // Store bill information in session
            session(['billplz_bill_id' => $billResponse['bill_id']]);
            
            // Create a payment record
            $this->createPaymentRecord($order, [
                'payment_method' => 'billplz',
                'amount' => $order->total_amount,
                'payment_status' => 'pending',
                'payment_date' => now(),
                'reference_id' => $billResponse['bill_id'],
                'transaction_details' => [
                    'bill_url' => $billResponse['url'],
                    'initiated_at' => now()->toIso8601String()
                ]
            ]);
            
            // Redirect to Billplz payment page
            return redirect($billResponse['url']);
            
        } catch (\Exception $e) {
            Log::error('Billplz payment initiation error', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('checkout.index')
                ->with('error', 'Payment processing error: ' . $e->getMessage());
        }
    }

    /**
     * Handle Billplz return callback
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function handleBillplzReturn(Request $request)
    {
        Log::info('Billplz return callback received', ['payload' => $request->all()]);
        
        try {
            // Get order ID from session or request
            $orderId = session('billplz_order_id');
            if (!$orderId && $request->has('ref_1')) {
                $orderId = $request->ref_1;
            }
            
            if (!$orderId) {
                throw new \Exception('Order ID not found in session or request');
            }
            
            // Try to find the order by order_number first
            $order = MadkrapowOrder::where('order_number', $orderId)->first();
            
            // If not found, try by id and order_id
            if (!$order) {
                $order = MadkrapowOrder::where('id', $orderId)
                    ->orWhere('order_id', $orderId)
                    ->first();
                    
                if (!$order) {
                    throw new \Exception("Order not found with any identifier: {$orderId}");
                }
            }
            
            // Get bill ID from session or request
            $billId = session('billplz_bill_id');
            if (!$billId && $request->has('billplz[id]')) {
                $billId = $request->input('billplz.id');
            }
            
            if (!$billId) {
                throw new \Exception('Bill ID not found in session or request');
            }
            
            // Get bill details to check payment status
            $billDetails = $this->billplzService->getBill($billId);
            if (!$billDetails['success']) {
                throw new \Exception('Could not retrieve bill details: ' . ($billDetails['error'] ?? 'Unknown error'));
            }
            
            $billData = $billDetails['data'];
            $isPaid = $billData['paid'] ?? false;
            
            // Get the payment record
            $payment = $order->payment;
            if (!$payment) {
                // Create payment record if it doesn't exist
                $payment = new MadkrapowPayment([
                    'order_id' => $order->getKey(),
                    'payment_method' => 'billplz',
                    'amount' => $order->total_amount,
                    'payment_date' => $isPaid ? now() : null,
                    'status' => $isPaid ? 'completed' : 'pending',
                    'reference_id' => $billId,
                    'transaction_details' => $billData
                ]);
                $payment->save();
            } else {
                // Update existing payment record
                $payment->status = $isPaid ? 'completed' : 'pending';
                $payment->payment_date = $isPaid ? now() : $payment->payment_date;
                
                // Make sure transaction_details is an array before merging
                $existingDetails = is_array($payment->transaction_details) ? $payment->transaction_details : [];
                $payment->transaction_details = array_merge(
                    $existingDetails,
                    ['bill_data' => $billData, 'updated_at' => now()->toIso8601String()]
                );
                $payment->save();
            }
            
            // Update order status if payment is completed
            if ($isPaid) {
                $order->status = 'processing';
                $order->save();
                
                // Clear session data
                session()->forget(['billplz_bill_id', 'billplz_order_id', 'billplz_pending_order_id']);
                
                return redirect()->route('payments.success', ['orderId' => $order->getKey()])
                    ->with('success', 'Payment completed successfully!');
            }
            
            // Payment failed or is still pending
            return redirect()->route('payments.failed', ['orderId' => $order->getKey()])
                ->with('error', 'Payment was not completed. Please try again or contact support.');
            
        } catch (\Exception $e) {
            // Log the error
            Log::error('Error handling Billplz return callback', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('checkout.index')
                ->with('error', 'There was an error processing your payment: ' . $e->getMessage());
        }
    }

    /**
     * Handle Billplz webhook
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function handleBillplzWebhook(Request $request)
    {
        Log::info('Billplz webhook received', ['payload' => $request->all()]);
        
        try {
            // Validate webhook data
            $data = $request->all();
            if (!$this->billplzService->verifyWebhook($data)) {
                Log::error('Invalid Billplz webhook signature', ['data' => $data]);
                return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 401);
            }
            
            // Get bill and payment data
            $billId = $data['id'] ?? null;
            $paid = $data['paid'] ?? 'false';
            $isPaid = ($paid === 'true');
            $orderId = $data['reference_1'] ?? null;
            
            if (!$billId || !$orderId) {
                Log::error('Missing bill ID or order ID in webhook data', ['data' => $data]);
                return response()->json(['status' => 'error', 'message' => 'Missing required data'], 400);
            }
            
            // Try multiple ways to find the order
            $order = null;
            
            // Try finding by the primary key first (which should be in reference_1)
            $order = MadkrapowOrder::find($orderId);
            
            // If not found, try by order_number from reference_2
            if (!$order && isset($data['reference_2'])) {
                $order = MadkrapowOrder::where('order_number', $data['reference_2'])->first();
                
                if ($order) {
                    Log::info('Order found by order_number', [
                        'order_number' => $data['reference_2'],
                        'primary_key' => $order->getKey()
                    ]);
                }
            }
            
            if (!$order) {
                Log::error("Order not found with reference: {$orderId}", ['data' => $data]);
                return response()->json(['status' => 'error', 'message' => 'Order not found'], 404);
            }
            
            // Get or create payment record
            $payment = $order->payment;
            if (!$payment) {
                $payment = new MadkrapowPayment([
                    'order_id' => $order->getKey(),
                    'payment_method' => 'billplz',
                    'amount' => $order->total_amount,
                    'payment_date' => $isPaid ? now() : null,
                    'status' => $isPaid ? 'completed' : 'pending',
                    'reference_id' => $billId,
                    'transaction_details' => $data
                ]);
                $payment->save();
            } else {
                // Update existing payment
                $payment->status = $isPaid ? 'completed' : $payment->status;
                $payment->payment_date = $isPaid ? now() : $payment->payment_date;
                
                // Make sure transaction_details is an array before merging
                $existingDetails = is_array($payment->transaction_details) ? $payment->transaction_details : [];
                $payment->transaction_details = array_merge(
                    $existingDetails,
                    ['webhook_data' => $data, 'updated_at' => now()->toIso8601String()]
                );
                $payment->save();
            }
            
            // Update order status if payment is completed
            if ($isPaid) {
                $order->status = 'processing';
                $order->save();
                
                Log::info('Payment completed via webhook', [
                    'order_id' => $order->getKey(),
                    'bill_id' => $billId
                ]);
            }
            
            return response()->json(['status' => 'success']);
            
        } catch (\Exception $e) {
            Log::error('Error handling Billplz webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Create a test Billplz bill for testing purposes
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function createTestBillplzBill(Request $request)
    {
        try {
            $user = auth()->user();
            $amount = $request->input('amount', 10.00);
            $description = $request->input('description', 'Test Payment');
            
            // Input validation
            if (!is_numeric($amount) || $amount <= 0) {
                return redirect()->route('test.billplz')
                    ->with('error', 'Invalid amount. Please enter a positive number.');
            }
            
            // SSL Fix: For development environment only
            if (app()->environment('local')) {
                // Create a temporary stream context that disables SSL verification
                $context = stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ]
                ]);
                
                // Apply the custom stream context
                libxml_set_streams_context($context);
                
                // If using Guzzle, you might need this
                // \GuzzleHttp\RequestOptions::VERIFY => false
            }
            
            // Create a test bill directly using the Billplz Service
            // The required parameters for v4 API are:
            // 1. collection_id
            // 2. email
            // 3. mobile (phone)
            // 4. name
            // 5. amount (in cents)
            // 6. callback_url
            // 7. description
            $response = $this->billplzService->getBillplz()->bill()->create(
                config('services.billplz.collection_id'),        // collection_id
                $user->email,                                     // email
                $user->phone ?? '60123456789',                    // mobile
                $user->name,                                      // name
                intval($amount * 100),                            // amount in cents
                route('payments.billplz.callback'),               // callback_url
                $description,                                     // description
                [                                                 // optional parameters
                    'redirect_url' => route('test.billplz')
                ]
            );
            
            if (!$response->isSuccessful()) {
                Log::error('Test Billplz bill creation failed', [
                    'error' => $response->toArray()
                ]);
                
                return redirect()->route('test.billplz')
                    ->with('error', 'Failed to create test bill: ' . ($response->toArray()['error']['message'] ?? 'Unknown error'));
            }
            
            $billData = $response->toArray();
            
            Log::info('Test Billplz bill created successfully', [
                'bill_id' => $billData['id'],
                'url' => $billData['url']
            ]);
            
            // Redirect to the bill payment page
            return redirect($billData['url']);
            
        } catch (\Exception $e) {
            Log::error('Error creating test Billplz bill', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('test.billplz')
                ->with('error', 'Error creating test bill: ' . $e->getMessage());
        }
    }

    /**
     * Test Billplz API connection directly with cURL
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function testBillplzConnection(Request $request)
    {
        // Only allow in development
        if (!app()->environment('local')) {
            abort(403, 'This endpoint is only available in development environment');
        }
        
        $results = [];
        
        // Test 1: Direct cURL request with SSL verification disabled
        try {
            $ch = curl_init();
            
            curl_setopt($ch, CURLOPT_URL, 'https://www.billplz-sandbox.com/api/v3/collections');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, config('services.billplz.key') . ':');
            
            // Disable SSL verification for testing
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            $response = curl_exec($ch);
            $error = curl_error($ch);
            $info = curl_getinfo($ch);
            
            curl_close($ch);
            
            $results['curl_direct'] = [
                'success' => empty($error),
                'response' => $response,
                'error' => $error,
                'info' => $info
            ];
        } catch (\Exception $e) {
            $results['curl_direct'] = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
        
        // Test 2: Using the Billplz Service
        try {
            $response = $this->billplzService->getBillplz()
                ->collection()
                ->all()
                ->toArray();
            
            $results['billplz_service'] = [
                'success' => true,
                'response' => $response
            ];
        } catch (\Exception $e) {
            $results['billplz_service'] = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
        
        // Return the results for debugging
        return response()->json([
            'message' => 'Billplz API connection test results',
            'results' => $results,
            'config' => [
                'key_set' => !empty(config('services.billplz.key')),
                'x_signature_set' => !empty(config('services.billplz.x_signature')),
                'collection_id_set' => !empty(config('services.billplz.collection_id')),
                'sandbox' => config('services.billplz.sandbox', true)
            ]
        ]);
    }

    /**
     * Display payment success page
     *
     * @param string $orderId
     * @return \Illuminate\Http\Response
     */
    public function paymentSuccess($orderId)
    {
        // Try to find the order by different fields
        $order = MadkrapowOrder::with(['orderItems.product', 'shipping', 'payment'])
            ->where(function($query) use ($orderId) {
                $query->where('order_id', $orderId)  // Primary key is order_id
                    ->orWhere('order_number', $orderId);
            })
            ->first();
            
        if (!$order) {
            return redirect()->route('home')->with('error', 'Order not found');
        }
        
        return view('payments.success', compact('order'));
    }
    
    /**
     * Display payment failure page
     *
     * @param string $orderId
     * @return \Illuminate\Http\Response
     */
    public function paymentFailed($orderId)
    {
        // Try to find the order by different fields
        $order = MadkrapowOrder::with(['orderItems.product', 'shipping', 'payment'])
            ->where(function($query) use ($orderId) {
                $query->where('order_id', $orderId)  // Primary key is order_id
                    ->orWhere('order_number', $orderId);
            })
            ->first();
            
        if (!$order) {
            return redirect()->route('home')->with('error', 'Order not found');
        }
        
        return view('payments.failed', compact('order'));
    }
}