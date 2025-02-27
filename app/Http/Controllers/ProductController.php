<?php

namespace App\Http\Controllers;

use App\Models\MadkrapowProduct;
use App\Models\MadkrapowReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $products = MadkrapowProduct::all();
        return view('products.index', compact('products'));
    }

    /**
     * Display the specified product.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $product = MadkrapowProduct::select('product_id', 'product_name', 'description', 'price', 'stock_quantity', 'image_path')
            ->with(['reviews.madkrapowUser'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->findOrFail($id);

        return view('products.show', [
            'product' => $product,
            'reviews' => $product->reviews()->paginate(5)
        ]);
    }

    /**
     * Show the form for creating a new product (admin only).
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // Check if user is admin (you might want to implement proper admin check)
        // if (!Auth::user()->isAdmin()) {
        //     return redirect()->route('home')->with('error', 'Unauthorized access.');
        // }
        
        return view('products.create');
    }

    /**
     * Store a newly created product in storage (admin only).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Check if user is admin (you might want to implement proper admin check)
        // if (!Auth::user()->isAdmin()) {
        //     return redirect()->route('home')->with('error', 'Unauthorized access.');
        // }
        
        $validator = Validator::make($request->all(), [
            'product_name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'description' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $product = new MadkrapowProduct();
        $product->product_name = $request->product_name;
        $product->price = $request->price;
        $product->stock_quantity = $request->stock_quantity;
        $product->description = $request->description;
        
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
            $product->image_path = $imagePath;
        }
        
        $product->save();

        return redirect()->route('products.index')->with('success', 'Product created successfully!');
    }

    /**
     * Show the form for editing the specified product (admin only).
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        // Check if user is admin (you might want to implement proper admin check)
        // if (!Auth::user()->isAdmin()) {
        //     return redirect()->route('home')->with('error', 'Unauthorized access.');
        // }
        
        $product = MadkrapowProduct::select('product_id', 'product_name', 'description', 'price', 'stock_quantity', 'image_path')
            ->findOrFail($id);
        return view('products.edit', compact('product'));
    }

    /**
     * Update the specified product in storage (admin only).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        // Check if user is admin (you might want to implement proper admin check)
        // if (!Auth::user()->isAdmin()) {
        //     return redirect()->route('home')->with('error', 'Unauthorized access.');
        // }
        
        $validator = Validator::make($request->all(), [
            'product_name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'description' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $product = MadkrapowProduct::findOrFail($id);
        $product->product_name = $request->product_name;
        $product->price = $request->price;
        $product->stock_quantity = $request->stock_quantity;
        $product->description = $request->description;
        
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            
            $imagePath = $request->file('image')->store('products', 'public');
            $product->image_path = $imagePath;
        }
        
        $product->save();

        return redirect()->route('products.index')->with('success', 'Product updated successfully!');
    }

    /**
     * Remove the specified product from storage (admin only).
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        // Check if user is admin (you might want to implement proper admin check)
        // if (!Auth::user()->isAdmin()) {
        //     return redirect()->route('home')->with('error', 'Unauthorized access.');
        // }
        
        $product = MadkrapowProduct::findOrFail($id);
        
        // Delete product image if exists
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }
        
        $product->delete();

        return redirect()->route('products.index')->with('success', 'Product deleted successfully!');
    }

    /**
     * Store a new review for the product.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeReview(Request $request, $id)
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

        $product = MadkrapowProduct::findOrFail($id);
        
        $review = new MadkrapowReview();
        $review->user_id = Auth::id();
        $review->product_id = $product->product_id;
        $review->rating = $request->rating;
        $review->comment = $request->comment;
        $review->review_date = now();
        $review->save();

        return redirect()->route('products.show', $id)->with('success', 'Review submitted successfully!');
    }
}
