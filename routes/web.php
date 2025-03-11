<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Home and general pages
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [HomeController::class, 'about'])->name('about');
Route::get('/contact', [HomeController::class, 'contact'])->name('contact');
Route::post('/contact', [HomeController::class, 'processContact'])->name('contact.process');
Route::get('/faq', [HomeController::class, 'faq'])->name('faq');
Route::get('/terms', [HomeController::class, 'terms'])->name('terms');
Route::get('/privacy', [HomeController::class, 'privacy'])->name('privacy');

// Landing page (temporary, will be replaced by the home page)
Route::get('/landing', function () {
    return redirect('/landing.html');
})->name('landing');

// Authentication routes
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Product routes
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{id}', [ProductController::class, 'show'])->name('products.show');
Route::post('/products/{id}/reviews', [ProductController::class, 'storeReview'])->name('products.reviews.store')->middleware('auth');

// Review routes
Route::get('/products/{productId}/reviews', [ReviewController::class, 'index'])->name('reviews.index');
Route::get('/products/{productId}/reviews/create', [ReviewController::class, 'create'])->name('reviews.create')->middleware('auth');
Route::post('/products/{productId}/reviews', [ReviewController::class, 'store'])->name('reviews.store')->middleware('auth');
Route::get('/reviews/{id}/edit', [ReviewController::class, 'edit'])->name('reviews.edit')->middleware('auth');
Route::put('/reviews/{id}', [ReviewController::class, 'update'])->name('reviews.update')->middleware('auth');
Route::delete('/reviews/{id}', [ReviewController::class, 'destroy'])->name('reviews.destroy')->middleware('auth');

// Routes that require authentication
Route::middleware(['auth'])->group(function () {
    // User profile
    Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    
    // Cart routes
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart', [CartController::class, 'addToCart'])->name('cart.store');
    Route::post('/cart/add', [CartController::class, 'addToCart'])->name('cart.add');
    Route::match(['put', 'post'], '/cart/{cartItemId}', [CartController::class, 'updateQuantity'])->name('cart.update');
    Route::delete('/cart/{cartItemId}', [CartController::class, 'removeItem'])->name('cart.remove');
    Route::delete('/cart', [CartController::class, 'clearCart'])->name('cart.clear');
    Route::get('/cart/checkout', [CartController::class, 'checkout'])->name('cart.checkout');
    
    // Checkout routes
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'process'])->name('checkout.process');
    Route::get('/checkout/confirmation/{id}', [CheckoutController::class, 'confirmation'])->name('checkout.confirmation');
    
    // Order routes
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::get('/orders/{id}/track', [OrderController::class, 'track'])->name('orders.track');

    // Billplz payment routes
    Route::get('/payments/billplz/initiate', 
        [App\Http\Controllers\PaymentController::class, 'initiateBillplzPayment'])
        ->name('payments.billplz.initiate');
    
    Route::get('/payments/billplz/return', 
        [App\Http\Controllers\PaymentController::class, 'handleBillplzReturn'])
        ->name('payments.billplz.return');
    
    Route::post('/payments/billplz/webhook', 
        [App\Http\Controllers\PaymentController::class, 'handleBillplzWebhook'])
        ->name('billplz.webhook');
    
    Route::get('/payments/success/{orderId}', 
        [App\Http\Controllers\PaymentController::class, 'paymentSuccess'])
        ->name('payments.success')
        ->where('orderId', '[0-9]+');
    
    Route::get('/payments/failed/{orderId}', 
        [App\Http\Controllers\PaymentController::class, 'paymentFailed'])
        ->name('payments.failed')
        ->where('orderId', '[0-9]+');

    // Test routes for Billplz
    Route::get('/test/billplz', function() {
        return view('test-billplz');
    })->name('test.billplz');
    Route::get('/test/billplz/connection', [App\Http\Controllers\PaymentController::class, 'testBillplzConnection'])->name('test.billplz.connection');
    Route::post('/test/billplz/create', [App\Http\Controllers\PaymentController::class, 'createTestBillplzBill'])->name('test.billplz.create');
});

// Admin routes (should be protected by admin middleware in a real application)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');
    
    // Admin product routes
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{id}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{id}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{id}', [ProductController::class, 'destroy'])->name('products.destroy');
    
    // Admin order routes
    Route::get('/orders', [OrderController::class, 'adminIndex'])->name('orders.index');
    Route::get('/orders/{id}', [OrderController::class, 'adminShow'])->name('orders.show');
    Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus'])->name('orders.status.update');
    Route::put('/orders/{id}/shipping', [OrderController::class, 'updateShippingStatus'])->name('orders.shipping.update');
    Route::put('/orders/{id}/payment', [OrderController::class, 'updatePaymentStatus'])->name('orders.payment.update');
    
    // Admin review routes
    Route::get('/reviews', [ReviewController::class, 'adminIndex'])->name('reviews.index');
    Route::delete('/reviews/{id}', [ReviewController::class, 'adminDestroy'])->name('reviews.destroy');
});

// Xdebug test route
Route::get('/xdebug-test', function() {
    xdebug_break();
    return phpinfo();
});

// Password Reset Routes
Route::get('forgot-password', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])
    ->name('password.request');
Route::post('forgot-password', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])
    ->name('password.email');
Route::get('reset-password/{token}', [App\Http\Controllers\Auth\ResetPasswordController::class, 'showResetForm'])
    ->name('password.reset');
Route::post('reset-password', [App\Http\Controllers\Auth\ResetPasswordController::class, 'reset'])
    ->name('password.update');
// Google login
Route::get('auth/google', [App\Http\Controllers\Auth\GoogleController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('auth/google/callback', [App\Http\Controllers\Auth\GoogleController::class, 'handleGoogleCallback']);

// Facebook login
Route::get('auth/facebook', [App\Http\Controllers\Auth\FacebookController::class, 'redirectToFacebook'])->name('auth.facebook');
Route::get('auth/facebook/callback', [App\Http\Controllers\Auth\FacebookController::class, 'handleFacebookCallback']);

// TikTok login
Route::get('auth/tiktok', [App\Http\Controllers\Auth\TikTokController::class, 'redirectToTikTok'])->name('auth.tiktok');
Route::get('auth/tiktok/callback', [App\Http\Controllers\Auth\TikTokController::class, 'handleTikTokCallback']);
Route::get('auth/tiktok/error', function(Request $request) {
    Log::error('TikTok auth error redirect', [
        'params' => $request->all(),
        'url' => $request->fullUrl()
    ]);
    return redirect()->route('login')->with('error', 'TikTok authentication failed: ' . ($request->get('error_string') ?? $request->get('error')));
})->name('auth.tiktok.error');

// Add this debugging route
Route::get('/debug-google', function() {
    try {
        $config = config('services.google');
        return response()->json([
            'client_id_configured' => !empty($config['client_id']),
            'client_secret_configured' => !empty($config['client_secret']),
            'redirect_configured' => !empty($config['redirect']),
            'redirect_value' => $config['redirect'] ?? null,
            'socialite_installed' => class_exists('Laravel\Socialite\Facades\Socialite'),
        ]);
    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});

// Add this debugging route for Facebook
Route::get('/debug-facebook', function() {
    try {
        $config = config('services.facebook');
        return response()->json([
            'client_id_configured' => !empty($config['client_id']),
            'client_secret_configured' => !empty($config['client_secret']),
            'redirect_configured' => !empty($config['redirect']),
            'redirect_value' => $config['redirect'] ?? null,
            'socialite_installed' => class_exists('Laravel\Socialite\Facades\Socialite'),
        ]);
    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});

// Add this debugging route for TikTok
Route::get('/debug-tiktok', function() {
    try {
        $config = config('services.tiktok');
        return response()->json([
            'client_id_configured' => !empty($config['client_id']),
            'client_secret_configured' => !empty($config['client_secret']),
            'redirect_configured' => !empty($config['redirect']),
            'redirect_value' => $config['redirect'] ?? null,
            'socialite_installed' => class_exists('Laravel\Socialite\Facades\Socialite'),
        ]);
    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});

// Test route for Facebook OAuth
Route::get('/test-facebook-oauth', function() {
    try {
        // Check if Facebook is configured
        $config = config('services.facebook');
        $configStatus = [
            'client_id_configured' => !empty($config['client_id']),
            'client_secret_configured' => !empty($config['client_secret']),
            'redirect_configured' => !empty($config['redirect']),
            'redirect_value' => $config['redirect'] ?? null,
            'socialite_installed' => class_exists('Laravel\Socialite\Facades\Socialite'),
        ];
        
        // Check if the Facebook App is valid
        $appId = $config['client_id'];
        $appSecret = $config['client_secret'];
        
        // Return the configuration status
        return response()->json([
            'config_status' => $configStatus,
            'message' => 'If all configuration values are true, your Facebook OAuth setup should be working. Check the Facebook Developer Console to make sure your App is properly configured.',
            'next_steps' => [
                'Check if your Facebook App is in Development Mode',
                'Make sure you have added the correct OAuth redirect URI in the Facebook Developer Console',
                'Verify that your App has the "Facebook Login" product added',
                'Check if you have added your app domain in the Facebook Developer Console',
                'Make sure you have configured the correct App ID and App Secret in your .env file'
            ]
        ]);
    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});

// Test route for TikTok OAuth
Route::get('/test-tiktok-oauth', function() {
    try {
        // Check if TikTok is configured
        $config = config('services.tiktok');
        $configStatus = [
            'client_id_configured' => !empty($config['client_id']),
            'client_secret_configured' => !empty($config['client_secret']),
            'redirect_configured' => !empty($config['redirect']),
            'redirect_value' => $config['redirect'] ?? null,
            'socialite_installed' => class_exists('Laravel\Socialite\Facades\Socialite'),
        ];
        
        // Return the configuration status
        return response()->json([
            'config_status' => $configStatus,
            'message' => 'If all configuration values are true, your TikTok OAuth setup should be working. Check the TikTok Developer Console to make sure your App is properly configured.',
            'next_steps' => [
                'Make sure your TikTok App is in Development Mode',
                'Verify that the redirect URI in your TikTok Developer Console matches exactly: ' . $config['redirect'],
                'Ensure your TikTok App has the correct permissions',
                'Check that your App Domain in TikTok Developer Console includes madkrapow.com',
                'Verify that your Client Key and Client Secret in .env match what\'s in the TikTok Developer Console'
            ]
        ]);
    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});

// Clear session route
Route::get('/clear-auth-session', function() {
    session()->forget('facebook_auth_in_progress');
    session()->forget('google_auth_in_progress');
    session()->forget('tiktok_auth_in_progress');
    return redirect()->route('login')->with('info', 'Authentication session cleared. Please try again.');
});

// Add these routes to your existing web.php file

// Facebook Data Deletion Routes
Route::post('/facebook/data-deletion', [App\Http\Controllers\FacebookDataDeletionController::class, 'handleDataDeletion'])
    ->name('facebook.data-deletion');
Route::get('/facebook/data-deletion/status/{id}', [App\Http\Controllers\FacebookDataDeletionController::class, 'showStatus'])
    ->name('facebook.data-deletion.status');

// TikTok Authentication Routes
// Make sure these routes are defined
// Should be:
// TikTok OAuth routes
Route::get('auth/tiktok', [App\Http\Controllers\Auth\TikTokController::class, 'redirect'])->name('auth.tiktok');
Route::get('auth/tiktok/callback', [App\Http\Controllers\Auth\TikTokController::class, 'callback']);

// TikTok Configuration Test Route
Route::get('/test-tiktok-config', function() {
    $clientKey = config('services.tiktok.client_id');
    $clientSecret = config('services.tiktok.client_secret') ? '[SECRET MASKED]' : 'Not set';
    $redirectUri = config('services.tiktok.redirect');
    
    return response()->json([
        'tiktok_config' => [
            'client_key' => $clientKey,
            'client_secret_set' => !empty(config('services.tiktok.client_secret')),
            'redirect_uri' => $redirectUri,
        ],
        'info' => 'Check if your client_key matches what you see in the TikTok Developer Portal'
    ]);
});
