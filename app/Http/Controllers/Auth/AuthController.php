public function updateProfile(Request $request)
{
    // Validate request
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users,email,' . auth()->id(),
        'password' => 'nullable|string|min:8|confirmed',
    ]);

    $user = auth()->user();
    
    // Update user data
    $user->name = $request->name;
    $user->email = $request->email;
    
    // Only update password if provided
    if ($request->password) {
        $user->password = Hash::make($request->password);
    }
    
    // Let Laravel handle the timestamps automatically
    $user->save();
    
    return redirect()->route('profile')->with('success', 'Profile updated successfully');
}