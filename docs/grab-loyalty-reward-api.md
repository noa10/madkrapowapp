# Grab Loyalty & Reward API Integration

This document provides a comprehensive guide for integrating Grab's Loyalty & Reward APIs into Madkrapow.com. It covers the setup, configuration, and implementation details necessary for developers to maintain and extend the integration.

## Overview

The integration involves two main APIs from Grab:

1. **Rewards Tier API**: Retrieves a user's loyalty tier (e.g., opt-out, member, silver, gold, platinum).
2. **Points Earning API**: Submits events to award points to Grab users asynchronously.

## Prerequisites

- Register your application with Grab to obtain OAuth credentials (client ID and client secret).
- Ensure your application is configured to handle OAuth 2.0 flows.
- Set up a secure database to store user tokens and related data.

## Configuration

### Environment Variables

Add the following environment variables to your `.env` file:

```env
GRAB_CLIENT_ID=your_grab_client_id
GRAB_CLIENT_SECRET=your_grab_client_secret
GRAB_REDIRECT_URI="${APP_URL}/auth/grab/callback"
GRAB_ENVIRONMENT=staging # or 'production'
```

### Services Configuration

Update the `config/services.php` file to include Grab API settings:

```php
'grab' => [
    'client_id' => env('GRAB_CLIENT_ID'),
    'client_secret' => env('GRAB_CLIENT_SECRET'),
    'redirect' => env('GRAB_REDIRECT_URI'),
    'environment' => env('GRAB_ENVIRONMENT', 'staging'),
],
```

## Implementation

### Step 1: User Account Linking with Grab

1. **OAuth Flow**: Implement OAuth 2.0 to allow users to authenticate with Grab and authorize access to their loyalty data.
   - Redirect users to Grab's authorization endpoint.
   - Upon approval, receive an access token and possibly a refresh token.
   - Obtain the partnerUserID (Grab user ID) during this process.

2. **Token Storage**: Securely store the partnerUserID, access token, and refresh token in the database.
   - Use the `SocialAccount` model to manage these tokens.

3. **Token Refresh**: Handle token expiration by using the refresh token to obtain a new access token when needed.

### Step 2: Rewards Tier API Integration

The Rewards Tier API is used to fetch and display a user's loyalty tier on the Madkrapow.com application.

#### Components Involved

1. **GrabService Class**:
   - `getLoyaltyTier($accessToken)`: Makes the API request to fetch a user's tier
   - `getValidAccessToken($user)`: Gets a valid token, refreshing if necessary
   - `getUserLoyaltyTier($user)`: Combines token validation and tier fetching with error handling
   - `getTierDisplayName($tier)`: Provides user-friendly tier names
   - `getTierBadgeClasses($tier)`: Returns CSS classes for styling tier badges

2. **ProfileController**:
   - `edit()`: Displays the user's profile page with their Grab loyalty tier
   - `refreshGrabTier()`: Manually refreshes the tier information when requested

3. **Views**:
   - `profile/edit.blade.php`: Displays the user's profile including their Grab loyalty tier

#### Implementation Details

1. **API Endpoint**:
   - Staging: `https://partner-api.stg-myteksi.com/loyalty/rewards/v1/tier`
   - Production: `https://partner-api.grab.com/loyalty/rewards/v1/tier`

2. **Request Headers**:
   ```
   Authorization: Bearer {OAuth token}
   Content-Type: application/json
   ```

3. **Response Format**:
   ```json
   {"result": {"tier": "gold"}}
   ```

4. **Caching Strategy**:
   - Cache the tier for 1 hour to reduce API calls
   - Provide a "refresh" button for users to manually update
   - Automatically refresh the tier on profile load if cache is expired

5. **Error Handling**:
   - Handle 401 (unauthorized) by refreshing the token
   - Handle 429 (rate limit) by showing appropriate user message
   - Handle 500 (server error) with retry logic

#### Sample Code for Fetching Tier

```php
// In GrabService.php
public function getLoyaltyTier($accessToken)
{
    try {
        $response = Http::withToken($accessToken)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->get($this->rewardsTierUrl);
        
        if ($response->successful()) {
            $data = $response->json();
            return $data['result']['tier'] ?? null;
        }
        
        // Handle specific error status codes
        if ($response->status() === 401) {
            throw new Exception('Unauthorized access to Grab loyalty tier API');
        }
        
        Log::error('Failed to fetch Grab loyalty tier', [
            'status' => $response->status(),
            'response' => $response->json() ?? $response->body(),
        ]);
        
        return null;
    } catch (Exception $e) {
        Log::error('Exception fetching Grab loyalty tier', [
            'message' => $e->getMessage(),
        ]);
        throw $e;
    }
}
```

#### Display Format

The loyalty tier is displayed as a colored badge with the following visual cues:
- Platinum: Purple
- Gold: Yellow
- Silver: Gray
- Member: Green
- Default/Unknown: Blue

### Step 3: Points Earning API Integration

1. **Award Points**: Use the Points Earning API to award points for events (e.g., purchases).
   - Trigger this when a user completes a purchase.

2. **API Request**:
   - Prepare the request body with necessary details like source, sourceID, and payload.
   - Send a POST request to the API endpoint.

3. **Response Handling**:
   - Handle success and error responses appropriately.
   - Implement retry logic for transient errors.

#### Components Involved

1. **GrabService Class**:
   - `awardPoints($orderId)`: Submits a request to award points for a specific order.
   - `generateIdempotencyKey()`: Generates a unique key to ensure idempotency of requests.
   - `getDateHeaderForRequest()`: Provides the current date in the required format for the API request.
   - `generatePointsEarningAuthorization()`: Generates the necessary authorization header for the API request.
   - `getPointsEarningErrorMessage($response)`: Extracts and formats error messages from the API response.

2. **LoyaltyRewardController**:
   - `index()`: Displays the loyalty rewards page with user tier and recent orders.
   - `awardPurchasePoints($orderId)`: Awards points for a specific purchase order.
   - `adminAwardPoints()`: Allows admin to manually award points.

3. **Routes**:
   - `/loyalty`: Displays the loyalty rewards page.
   - `/loyalty/award-points/{orderId}`: Endpoint to award points for a specific order.
   - `/admin/loyalty/award-points`: Admin endpoint to award points.

4. **Views**:
   - `resources/views/loyalty/index.blade.php`: Displays the user's loyalty tier, recent orders, and points status.

#### Implementation Details

- **API Endpoint**:
  - Staging: `https://partner-api.stg-myteksi.com/loyalty/points/v1/earn`
  - Production: `https://partner-api.grab.com/loyalty/points/v1/earn`

- **Request Headers**:
  ```
  Authorization: Bearer {OAuth token}
  Content-Type: application/json
  Idempotency-Key: {unique-key}
  Date: {current-date}
  ```

- **Request Body**:
  ```json
  {
    "source": "madkrapow",
    "sourceID": "{orderId}",
    "payload": {
      "points": {points},
      "description": "Points awarded for purchase"
    }
  }
  ```

- **Response Handling**:
  - Log success and error responses.
  - Retry on transient errors with exponential backoff.

- **Error Handling**:
  - Handle 401 (unauthorized) by refreshing the token.
  - Handle 500 (server error) with retry logic.
  - Log all errors for monitoring.

By following these steps, developers can ensure that points are awarded correctly and efficiently, enhancing user engagement through the loyalty program.

## Error Handling

- **401 Unauthorized**: Refresh the OAuth token and retry the request.
- **500 Server Error**: Implement retry logic with exponential backoff.
- **General**: Log all errors for monitoring and implement rate limit checks.

## Security Considerations

- Encrypt sensitive data (tokens, keys) and ensure HTTPS for all API calls.
- Comply with data protection regulations (e.g., GDPR) when storing user data.

## Additional Resources

- [Grab Developer Portal](https://developer.grab.com/)
- [OAuth 2.0 Documentation](https://oauth.net/2/)

## Conclusion

By following this guide, developers can effectively integrate Grab's loyalty features into Madkrapow.com, enhancing user engagement through tier visibility and point rewards. This document should serve as a valuable resource for future development and maintenance efforts.
