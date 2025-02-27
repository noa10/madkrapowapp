<?php

namespace App\Http\Controllers;

use App\Models\MadkrapowCartItem;
use App\Models\MadkrapowProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    /**
     * Display the user's cart.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $cartItems = MadkrapowCartItem::where('user_id', Auth::id())
            ->with('product')
            ->get();
            
        $subtotal = $cartItems->sum(function ($item) {
            return $item->quantity * $item->product->price;
        });
        
        return view('cart.index', compact('cartItems', 'subtotal'));
    }

    /**
     * Add a product to the cart.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function addToCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:madkrapow_product,product_id',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $product = MadkrapowProduct::findOrFail($request->product_id);
        
        // Check if product is in stock
        if ($product->stock_quantity < $request->quantity) {
            return redirect()->back()->with('error', 'Not enough stock available.');
        }
        
        // Check if product already exists in cart
        $existingCartItem = MadkrapowCartItem::where('user_id', Auth::id())
            ->where('product_id', $request->product_id)
            ->first();
            
        if ($existingCartItem) {
            // Update quantity
            $existingCartItem->quantity += $request->quantity;
            $existingCartItem->save();
        } else {
            // Create new cart item
            $cartItem = new MadkrapowCartItem();
            $cartItem->user_id = Auth::id();
            $cartItem->product_id = $request->product_id;
            $cartItem->quantity = $request->quantity;
            $cartItem->added_date = now();
            $cartItem->save();
        }

        return redirect()->route('cart.index')->with('success', 'Product added to cart successfully!');
    }

    /**
     * Update the quantity of a cart item.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateQuantity(Request $request, $cartItemId)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $cartItem = MadkrapowCartItem::where('cart_item_id', $cartItemId)
            ->where('user_id', Auth::id())
            ->firstOrFail();
            
        $product = $cartItem->product;
        
        // Check if product is in stock
        if ($product->stock_quantity < $request->quantity) {
            return redirect()->back()->with('error', 'Not enough stock available.');
        }
        
        $cartItem->quantity = $request->quantity;
        $cartItem->save();

        return redirect()->route('cart.index')->with('success', 'Cart updated successfully!');
    }

    /**
     * Remove a cart item.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function removeItem($cartItemId)
    {
        $cartItem = MadkrapowCartItem::where('cart_item_id', $cartItemId)
            ->where('user_id', Auth::id())
            ->firstOrFail();
            
        $cartItem->delete();

        return redirect()->route('cart.index')->with('success', 'Item removed from cart successfully!');
    }

    /**
     * Clear the entire cart.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function clearCart()
    {
        MadkrapowCartItem::where('user_id', Auth::id())->delete();

        return redirect()->route('cart.index')->with('success', 'Cart cleared successfully!');
    }

    /**
     * Proceed to checkout.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function checkout()
    {
        $cartItems = MadkrapowCartItem::where('user_id', Auth::id())
            ->with('product')
            ->get();
            
        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }
        
        // Check if all products are in stock
        foreach ($cartItems as $cartItem) {
            if ($cartItem->product->stock_quantity < $cartItem->quantity) {
                return redirect()->route('cart.index')->with('error', 'Some products in your cart are out of stock.');
            }
        }
        
        return redirect()->route('checkout.index');
    }
}
