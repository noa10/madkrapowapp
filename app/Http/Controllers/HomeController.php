<?php

namespace App\Http\Controllers;

use App\Models\MadkrapowProduct;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Display the home page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $featuredProducts = MadkrapowProduct::orderBy('product_id', 'desc')
            ->take(4)
            ->get();
            
        return view('home', compact('featuredProducts'));
    }

    /**
     * Display the about page.
     *
     * @return \Illuminate\View\View
     */
    public function about()
    {
        return view('about');
    }

    /**
     * Display the contact page.
     *
     * @return \Illuminate\View\View
     */
    public function contact()
    {
        return view('contact');
    }

    /**
     * Process the contact form.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function processContact(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        // Here you would typically send an email or store the contact message
        // For now, we'll just redirect back with a success message
        
        return redirect()->route('contact')->with('success', 'Your message has been sent successfully!');
    }

    /**
     * Display the FAQ page.
     *
     * @return \Illuminate\View\View
     */
    public function faq()
    {
        return view('faq');
    }

    /**
     * Display the terms and conditions page.
     *
     * @return \Illuminate\View\View
     */
    public function terms()
    {
        return view('terms');
    }

    /**
     * Display the privacy policy page.
     *
     * @return \Illuminate\View\View
     */
    public function privacy()
    {
        return view('privacy');
    }

    /**
     * Display the dashboard page (admin only).
     *
     * @return \Illuminate\View\View
     */
    public function dashboard()
    {
        // Check if user is admin (you might want to implement proper admin check)
        // if (!Auth::user()->isAdmin()) {
        //     return redirect()->route('home')->with('error', 'Unauthorized access.');
        // }
        
        return view('admin.dashboard');
    }
}
