<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FacebookDataDeletionController extends Controller
{
    /**
     * Handle Facebook data deletion callback
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleDataDeletion(Request $request)
    {
        // Validate the request
        $request->validate([
            'signed_request' => 'required|string',
        ]);

        $signedRequest = $request->input('signed_request');
        $data = $this->parseSignedRequest($signedRequest);

        if (!$data) {
            return response()->json(['error' => 'Invalid signed request'], 400);
        }

        // Get the user ID from the signed request
        $userId = $data['user_id'] ?? null;
        
        if (!$userId) {
            return response()->json(['error' => 'No user ID provided'], 400);
        }

        // Log the deletion request
        Log::info('Facebook data deletion request received for user ID: ' . $userId);

        // TODO: Implement your data deletion logic here
        // Delete user data associated with this Facebook user ID

        // Return confirmation response
        return response()->json([
            'url' => route('facebook.data-deletion.status', ['id' => $userId]),
            'confirmation_code' => md5($userId . time()),
        ]);
    }

    /**
     * Show status of data deletion request
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function showStatus($id)
    {
        // TODO: Implement status check logic
        return response()->json([
            'status' => 'completed',
        ]);
    }

    /**
     * Parse signed request from Facebook
     *
     * @param  string  $signedRequest
     * @return array|null
     */
    private function parseSignedRequest($signedRequest)
    {
        list($encodedSig, $payload) = explode('.', $signedRequest, 2);

        $secret = env('FACEBOOK_APP_SECRET'); // Your app secret

        // Decode the data
        $sig = $this->base64UrlDecode($encodedSig);
        $data = json_decode($this->base64UrlDecode($payload), true);

        // Confirm the signature
        $expectedSig = hash_hmac('sha256', $payload, $secret, true);
        if ($sig !== $expectedSig) {
            return null;
        }

        return $data;
    }

    /**
     * Base64 URL decode
     *
     * @param  string  $input
     * @return string
     */
    private function base64UrlDecode($input)
    {
        return base64_decode(strtr($input, '-_', '+/'));
    }
}