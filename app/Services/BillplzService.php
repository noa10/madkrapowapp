<?php

namespace App\Services;

use App\Models\MadkrapowOrder;
use Billplz\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BillplzService
{
    protected $billplz;
    protected $webhookUrl;
    protected $callbackUrl;
    protected $billplzCollection;

    /**
     * Create a new BillplzService instance.
     */
    public function __construct()
    {
        // Initialize the Billplz client with SSL verification disabled for development
        $httpClient = $this->createHttpClient();
        
        if ($httpClient) {
            // Create with custom HTTP client
            $this->billplz = new \Billplz\Client(
                $httpClient,
                config('services.billplz.key'),
                config('services.billplz.x_signature')
            );
        } else {
            // Fallback to default client
            $this->billplz = \Billplz\Client::make(
                config('services.billplz.key'),
                config('services.billplz.x_signature'),
                config('services.billplz.sandbox') ? 'staging' : 'production'
            );
        }
        
        // Set sandbox mode if needed
        if (config('services.billplz.sandbox')) {
            $this->billplz->useSandbox();
        }

        // Set webhook and callback URLs
        $this->webhookUrl = route('billplz.webhook');
        $this->callbackUrl = route('payments.billplz.return');
        $this->billplzCollection = config('services.billplz.collection_id');
        
        // Log configuration for debugging
        \Log::info('Billplz service initialized', [
            'sandbox' => config('services.billplz.sandbox'),
            'collection_id' => $this->billplzCollection,
            'webhook_url' => $this->webhookUrl,
            'callback_url' => $this->callbackUrl
        ]);
    }
    
    /**
     * Create a custom HTTP client with SSL verification disabled for development
     * 
     * @return \Http\Client\Common\HttpMethodsClient|null
     */
    private function createHttpClient()
    {
        try {
            // Only disable SSL verification in local environment
            if (app()->environment('local')) {
                // Create a custom Guzzle client with SSL verification disabled
                $guzzle = new \GuzzleHttp\Client([
                    'verify' => false,
                    'timeout' => 60,
                ]);
                
                // Create PSR-18 compatible HTTP client
                $httpClient = new \Http\Adapter\Guzzle7\Client($guzzle);
                
                // Create HTTP methods client
                return new \Http\Client\Common\HttpMethodsClient(
                    $httpClient,
                    new \Http\Message\MessageFactory\GuzzleMessageFactory()
                );
            }
        } catch (\Exception $e) {
            \Log::warning('Could not create custom HTTP client for BillplzService: ' . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Create a bill for order payment
     *
     * @param MadkrapowOrder $order
     * @return array|null
     */
    public function createBill(MadkrapowOrder $order)
    {
        try {
            $user = $order->user;
            
            // Get user information
            $name = $user->name ?? 'Customer';
            $email = $user->email ?? '';
            $phone = $user->phone ?? '';
            
            // Use order_number or primary key as reference
            $orderReference = $order->order_number ?? $order->getKey();
            $primaryKey = $order->getKey(); // Get the primary key value
            
            // Description should be the order number or ID
            $description = "Payment for Order #{$orderReference}";
            
            // Get the amount in cents (Billplz requires amount in cents)
            $amount = intval((float) $order->total_amount * 100);
            
            Log::info('Creating Billplz bill', [
                'primary_key' => $primaryKey,
                'order_number' => $orderReference,
                'collection_id' => $this->billplzCollection,
                'email' => $email,
                'phone' => $phone,
                'name' => $name,
                'amount' => $amount,
                'callback_url' => $this->callbackUrl,
                'description' => $description,
                'redirect_url' => $this->callbackUrl
            ]);
            
            // Create the bill using Billplz API
            // The required parameters for v4 API are:
            // 1. collection_id
            // 2. email
            // 3. mobile (phone)
            // 4. name
            // 5. amount (in cents)
            // 6. callback_url
            // 7. description
            $response = $this->billplz->bill()->create(
                $this->billplzCollection,        // collection_id
                $email,                          // email
                $phone,                          // mobile
                $name,                           // name
                $amount,                         // amount in cents
                $this->callbackUrl,              // callback_url
                $description,                    // description
                [                                // optional parameters
                    'redirect_url' => $this->callbackUrl,
                    'reference_1_label' => 'Order ID',
                    'reference_1' => $primaryKey, // Use the primary key as reference
                    'reference_2_label' => 'Order Number',
                    'reference_2' => $orderReference
                ]
            );
            
            if ($response->isSuccessful()) {
                $data = $response->toArray();
                
                // Log successful bill creation
                Log::info('Billplz bill created successfully', [
                    'primary_key' => $primaryKey,
                    'order_number' => $orderReference,
                    'bill_id' => $data['id'],
                    'url' => $data['url']
                ]);
                
                return [
                    'success' => true,
                    'bill_id' => $data['id'],
                    'url' => $data['url']
                ];
            } else {
                // Log failure
                Log::error('Failed to create Billplz bill', [
                    'primary_key' => $primaryKey,
                    'order_number' => $orderReference,
                    'error' => $response->toArray()
                ]);
                
                return [
                    'success' => false,
                    'error' => 'Failed to create payment bill'
                ];
            }
        } catch (\Exception $e) {
            // Log exception
            Log::error('Exception creating Billplz bill', [
                'primary_key' => $order->getKey(),
                'order_number' => $orderReference ?? 'N/A',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get bill details
     *
     * @param string $billId
     * @return array
     */
    public function getBill($billId)
    {
        try {
            $response = $this->billplz->bill()->get($billId);
            
            if ($response->isSuccessful()) {
                return [
                    'success' => true,
                    'data' => $response->toArray()
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Failed to retrieve bill information'
            ];
        } catch (\Exception $e) {
            Log::error('Exception getting Billplz bill', [
                'bill_id' => $billId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate webhook data with X-Signature
     *
     * @param array $data
     * @return bool
     */
    public function verifyWebhook($data)
    {
        try {
            return $this->billplz->webhook($data)->validate();
        } catch (\Exception $e) {
            Log::error('Failed to validate Billplz webhook', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            
            return false;
        }
    }

    /**
     * Get the Billplz client instance
     *
     * @return \Billplz\Client
     */
    public function getBillplz()
    {
        return $this->billplz;
    }
} 