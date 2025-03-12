# User Dashboard Documentation

## Overview

The user dashboard serves as the central hub for authenticated users after sign-up or sign-in. It provides a summary of account information, order statistics, recent orders, and product recommendations, giving users a comprehensive view of their activity and engagement with the Mad Krapow platform.

## Features

- **User Profile Summary**: Displays basic user information (name, email)
- **Order Statistics**: Shows total orders, pending orders, and completed orders
- **Recent Orders**: Lists the 5 most recent orders with key details
- **Product Recommendations**: Displays 4 randomly selected products

## Implementation Details

### Routes

The dashboard route is defined in `routes/web.php` within the authenticated routes group:

```php
Route::middleware(['auth'])->group(function () {
    // User dashboard
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
    
    // Other authenticated routes...
});
```

### Controller

The dashboard functionality is implemented in the `dashboard()` method of the `AuthController` (located at `app/Http/Controllers/AuthController.php`):

```php
/**
 * Display the user dashboard page.
 *
 * @return \Illuminate\View\View
 */
public function dashboard()
{
    $user = Auth::user();
    
    // Get recent orders - limit to 5
    $recentOrders = MadkrapowOrder::where('user_id', $user->user_id)
        ->orderBy('order_date', 'desc')
        ->limit(5)
        ->get();
    
    // Get recommended products
    $recommendedProducts = MadkrapowProduct::inRandomOrder()
        ->limit(4)
        ->get();
    
    // Get order statistics
    $orderCount = MadkrapowOrder::where('user_id', $user->user_id)->count();
    $pendingOrderCount = MadkrapowOrder::where('user_id', $user->user_id)
        ->where('status', 'pending')
        ->count();
    $completedOrderCount = MadkrapowOrder::where('user_id', $user->user_id)
        ->where('status', 'completed')
        ->count();
    
    return view('dashboard.index', compact(
        'user', 
        'recentOrders', 
        'recommendedProducts', 
        'orderCount', 
        'pendingOrderCount', 
        'completedOrderCount'
    ));
}
```

### View Structure

The dashboard view is located at `resources/views/dashboard/index.blade.php` and extends the main layout (`layouts.app`). It is organized into several sections:

1. **Sidebar/Account Navigation** (Left Column)
   - Profile summary with avatar
   - Navigation links (Dashboard, Profile, Orders, Cart, Logout)

2. **Main Content** (Right Column)
   - Welcome banner
   - Order statistics cards
   - Recent orders table
   - Recommended products grid

### Data Models

The dashboard interacts with the following models:

- `MadkrapowUser`: For user information
- `MadkrapowOrder`: For order data and statistics
- `MadkrapowProduct`: For product recommendations

### UI Components

#### User Avatar

A simple avatar is created using the first letter of the user's name:

```html
<div class="avatar-circle mx-auto mb-3 bg-primary text-white d-flex align-items-center justify-content-center">
    <span class="avatar-text display-4">{{ substr($user->name, 0, 1) }}</span>
</div>
```

#### Statistics Cards

Three cards display user order statistics using Bootstrap cards and icons:

```html
<div class="card shadow-sm h-100">
    <div class="card-body text-center py-4">
        <div class="stat-icon mb-3 bg-light rounded-circle p-3 d-inline-block">
            <i class="bi bi-box-seam fs-3 text-primary"></i>
        </div>
        <h3 class="stat-value mb-1">{{ $orderCount }}</h3>
        <p class="text-muted mb-0">Total Orders</p>
    </div>
</div>
```

#### Recent Orders Table

Displays a responsive table of recent orders with conditional empty state:

```html
@if($recentOrders->count() > 0)
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <!-- Table structure -->
        </table>
    </div>
@else
    <div class="p-4 text-center">
        <!-- Empty state message -->
    </div>
@endif
```

#### Product Recommendations

Displays a grid of product cards:

```html
<div class="row">
    @foreach($recommendedProducts as $product)
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card product-card h-100 border-0 shadow-sm">
                <!-- Product card structure -->
            </div>
        </div>
    @endforeach
</div>
```

## Navigation Integration

### Navbar Integration

The dashboard is integrated into the main navigation in `resources/views/layouts/app.blade.php`:

```html
<ul class="dropdown-menu" aria-labelledby="navbarDropdown">
    <li><a class="dropdown-item" href="{{ route('dashboard') }}">Dashboard</a></li>
    <li><a class="dropdown-item" href="{{ route('profile') }}">Profile</a></li>
    <!-- Other menu items -->
</ul>
```

### Consistent Sidebar

The dashboard sidebar pattern is replicated across user account pages (profile, orders) for consistent navigation.

## Authentication Redirects

After successful authentication, users are redirected to the dashboard:

```php
// In login controllers, after successful authentication:
return redirect()->intended(route('dashboard'));
```

This applies to standard login, social logins (TikTok, Facebook, Google), and registration.

## Placeholder Images

For products without images, a placeholder SVG is used:

```html
<img src="{{ $product->image_url ?? asset('images/products/placeholder.svg') }}" 
     class="card-img-top" alt="{{ $product->name }}">
```

The placeholder SVG is located at `public/images/products/placeholder.svg`.

## Customization and Extension

### Adding New Sections

To add a new section to the dashboard:

1. Update the `dashboard()` method in `AuthController` to fetch required data
2. Add the new section to the view in `resources/views/dashboard/index.blade.php`

Example of adding a "Recent Reviews" section:

```php
// In AuthController:
$recentReviews = MadkrapowReview::where('user_id', $user->user_id)
    ->orderBy('created_at', 'desc')
    ->limit(3)
    ->get();

// Add to the compact() function
return view('dashboard.index', compact(
    'user', 
    'recentOrders', 
    'recommendedProducts', 
    'orderCount', 
    'pendingOrderCount', 
    'completedOrderCount',
    'recentReviews'
));
```

Then add the corresponding HTML section to the view.

### Modifying Sidebar Navigation

The sidebar navigation can be customized by editing the list group in the dashboard view:

```html
<div class="list-group list-group-flush">
    <!-- Add or modify navigation items here -->
</div>
```

### Styling

Custom styles for the dashboard are defined in a `<style>` section at the bottom of the view. These can be extended or modified as needed.

## Responsive Design

The dashboard is built with Bootstrap 5 and is fully responsive:
- Uses Bootstrap grid system with responsive breakpoints
- Tables have `table-responsive` class for horizontal scrolling on small screens
- Media queries adjust font sizes for small screens
- Card layouts adapt to different screen sizes using column classes

## Future Enhancements

Potential improvements to consider:

1. **Personalized Product Recommendations**: Replace random products with algorithm-based recommendations
2. **Order Tracking Integration**: Add direct tracking information for recent orders
3. **User Activity Feed**: Show recent activities like reviews, wishlists, etc.
4. **Quick Actions**: Add common actions like "Reorder", "Track Order", etc.
5. **Dashboard Widgets**: Allow users to customize their dashboard layout
6. **Performance Metrics**: Add caching for dashboard queries to improve performance 