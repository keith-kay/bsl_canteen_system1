<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\CustomUser;
use App\Models\User_type;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\PinMail;


class UserController extends Controller
{
    public function register(Request $request)
    {
        // Validate request data
        $validatedData = $request->validate([
            'firstname' => 'required|string',
            'lastname' => 'required|string',
            'employment_number' => 'required|string',
            'user_type_id' => 'required|string', // Should match the name attribute of the select field
            'status' => 'required|string',
            'email' => 'required|email',
        ]);

        // Generate a random 4-digit PIN
        $pin = mt_rand(1000, 9999);

        // Create a new user instance
        $user = new CustomUser();
        $user->bsl_cmn_users_firstname = $validatedData['firstname'];
        $user->bsl_cmn_users_lastname = $validatedData['lastname'];
        $user->bsl_cmn_users_employment_number = $validatedData['employment_number'];
        $user->bsl_cmn_users_pin = $pin;
        $user->bsl_cmn_users_type = $validatedData['user_type_id'];
        $user->bsl_cmn_users_status = $validatedData['status'];
        $user->bsl_cmn_users_email = $validatedData['email'];

        // Save the user to the database
        $user->save();

        // Send email with PIN
        Mail::to($validatedData['email'])->send(new PinMail($pin));

        // Redirect back with success message
        return redirect()->route('login')->with('success', 'User created successfully.');
    }

    public function login(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'pin' => 'required|digits:4', // Assuming PIN is a 4-digit number
        ]);

        // Attempt to find the user by PIN
        $user = CustomUser::where('bsl_cmn_users_pin', $validatedData['pin'])->first();
        if ($user) {
            // Log in the user
            Auth::login($user);



            // Check if user is authenticated
            if (Auth::check()) {
                // Redirect authenticated users to the dashboard or any other page
                return redirect()->route('dashboard');
            } else {
                // Debug: Log authentication failure
                logger('Authentication failed for user: ' . $user->id);
            }
        } else {
            // Debug: Log user not found
            logger('User not found for PIN: ' . $validatedData['pin']);
        }

        // If login fails, redirect back with error message
        return redirect()->back()->with('error', 'Invalid PIN');
    }

    public function showRegistrationForm()
    {
        $userTypes = User_type::pluck('bsl_cmn_user_types_name', 'bsl_cmn_user_types_id'); // Fetch all user types and select 'name' column as the dropdown option text and 'id' column as the value

        return view('register', compact('userTypes')); // Pass the $userTypes variable to the view
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function logout()
    {
        Auth::logout();

        return redirect('/');
    }
}
