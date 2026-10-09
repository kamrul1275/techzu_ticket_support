<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuthController extends Controller
{
    /**
     * Show registration form.
     */
    public function showRegister()
    {
        return view('auth.register');
    }

    /**
     * Register a new customer.
     */
    public function register(Request $request)
    {
        // Validate input before saving.
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            // The database uses "customer" as the default role.
            $user = User::create($data);

            // Log in the newly registered customer.
            Auth::login($user);

            // Protect against session fixation.
            $request->session()->regenerate();

            return redirect()->route('dashboard');

        } catch (Throwable $e) {

            Log::error('User registration failed', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return back()
                ->withInput($request->only('name', 'email'))
                ->with('error', 'Registration failed. Please try again.');
        }
    }

    /**
     * Show login form.
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Authenticate user.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        try {
            if (!Auth::attempt($credentials)) {

                return back()
                    ->withErrors([
                        'email' => 'Invalid email or password.',
                    ])
                    ->onlyInput('email');
            }

            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));

        } catch (Throwable $e) {

            Log::error('User login failed', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return back()
                ->with('error', 'Unable to log in. Please try again.');
        }
    }

    /**
     * Log out authenticated user.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}