<?php

namespace App\Http\Controllers;

use App\Models\MadkrapowProduct;
use App\Models\MadkrapowReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    /**
     * Display a listing of the reviews for a product.
     *
     * @param  int  $productId
     * @return \Illuminate\View\View
     */
    public function index($productId)
    {
        $product = MadkrapowProduct::findOrFail($productId);
        $reviews = $product->reviews()->with('user')->get();
        $averageRating = $reviews->avg('rating');
        
        return view('reviews.index', compact('product', 'reviews', 'averageRating'));
    }

    /**
     * Show the form for creating a new review.
     *
     * @param  int  $productId
     * @return \Illuminate\View\View
     */
    public function create($productId)
    {
        $product = MadkrapowProduct::findOrFail($productId);
        
        // Check if user has already reviewed this product
        $existingReview = MadkrapowReview::where('user_id', Auth::id())
            ->where('product_id', $productId)
            ->first();
            
        if ($existingReview) {
            return redirect()->route('reviews.edit', $existingReview->review_id)
                ->with('info', 'You have already reviewed this product. You can edit your review below.');
        }
        
        return view('reviews.create', compact('product'));
    }

    /**
     * Store a newly created review in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $productId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, $productId)
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $product = MadkrapowProduct::findOrFail($productId);
        
        // Check if user has already reviewed this product
        $existingReview = MadkrapowReview::where('user_id', Auth::id())
            ->where('product_id', $productId)
            ->first();
            
        if ($existingReview) {
            return redirect()->route('reviews.edit', $existingReview->review_id)
                ->with('info', 'You have already reviewed this product. You can edit your review below.');
        }
        
        auth()->user()->reviews()->create([
            'product_id' => $product->product_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'review_date' => now()
        ]);

        return redirect()->route('products.show', $product->product_id)
            ->with('success', 'Review submitted successfully!');
    }

    /**
     * Show the form for editing the specified review.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $review = MadkrapowReview::where('review_id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
            
        $product = $review->product;
        
        return view('reviews.edit', compact('review', 'product'));
    }

    /**
     * Update the specified review in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $review = MadkrapowReview::where('review_id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
            
        $review->rating = $request->rating;
        $review->comment = $request->comment;
        $review->review_date = now();
        $review->save();

        return redirect()->route('products.show', $review->product_id)
            ->with('success', 'Review updated successfully!');
    }

    /**
     * Remove the specified review from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $review = MadkrapowReview::where('review_id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
            
        $productId = $review->product_id;
        
        $review->delete();

        return redirect()->route('products.show', $productId)
            ->with('success', 'Review deleted successfully!');
    }

    /**
     * Display a listing of all reviews (admin only).
     *
     * @return \Illuminate\View\View
     */
    public function adminIndex()
    {
        // Check if user is admin (you might want to implement proper admin check)
        // if (!Auth::user()->isAdmin()) {
        //     return redirect()->route('home')->with('error', 'Unauthorized access.');
        // }
        
        $reviews = MadkrapowReview::with(['user', 'product'])
            ->orderBy('created_at', 'desc')
            ->get();
            
        return view('admin.reviews.index', compact('reviews'));
    }

    /**
     * Remove the specified review from storage (admin only).
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function adminDestroy($id)
    {
        // Check if user is admin (you might want to implement proper admin check)
        // if (!Auth::user()->isAdmin()) {
        //     return redirect()->route('home')->with('error', 'Unauthorized access.');
        // }
        
        $review = MadkrapowReview::findOrFail($id);
        $review->delete();

        return redirect()->route('admin.reviews.index')
            ->with('success', 'Review deleted successfully!');
    }
}
