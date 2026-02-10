<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\LoginLog;
use App\Models\LogoutLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Show the login form.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        // Redirect to dashboard if already authenticated
        if (Auth::check() && Auth::user()->isCoordinator()) {
            return redirect()->route('coordinator.dashboard');
        }

        return view('login');
    }

    /**
     * Handle login request with tracking.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        // Validate input
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = $credentials['email'];
        $password = $credentials['password'];
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();

        // Find user by email
        $user = User::where('email', $email)->first();

        // Case 1: User not found
        if (!$user) {
            $this->logFailedAttempt(null, $email, $ipAddress, $userAgent, 'User not found');
            
            return back()->withErrors([
                'email' => 'These credentials do not match our records.',
            ])->withInput($request->only('email'));
        }

        // Case 2: User is not active
        if (!$user->is_active) {
            $this->logFailedAttempt($user->user_id, $email, $ipAddress, $userAgent, 'Account is inactive');
            
            return back()->withErrors([
                'email' => 'Your account has been deactivated. Please contact administrator.',
            ])->withInput($request->only('email'));
        }

        // Case 3: User is not a coordinator
        if (!$user->isCoordinator()) {
            $this->logFailedAttempt($user->user_id, $email, $ipAddress, $userAgent, 'User is not a coordinator');
            
            return back()->withErrors([
                'email' => 'You do not have permission to access this system.',
            ])->withInput($request->only('email'));
        }

        // Case 4: Account is locked
        if ($user->isLocked()) {
            $lockedUntil = $user->locked_until->format('Y-m-d H:i:s');
            $this->logFailedAttempt($user->user_id, $email, $ipAddress, $userAgent, 'Account is locked until ' . $lockedUntil);
            
            $minutes = now()->diffInMinutes($user->locked_until, false);
            return back()->withErrors([
                'email' => "Your account has been locked due to multiple failed login attempts. Please try again after {$minutes} minutes.",
            ])->withInput($request->only('email'));
        }

        // Case 5: Invalid password
        if (!Hash::check($password, $user->password)) {
            $user->incrementFailedAttempts();
            
            $remaining = 5 - $user->failed_login_attempts;
            $message = $remaining > 0
                ? "Invalid credentials. You have {$remaining} attempt(s) remaining before your account is locked."
                : "Your account has been locked due to multiple failed login attempts.";
            
            $this->logFailedAttempt($user->user_id, $email, $ipAddress, $userAgent, 'Invalid password (Attempt ' . $user->failed_login_attempts . ')');
            
            return back()->withErrors([
                'email' => $message,
            ])->withInput($request->only('email'));
        }

        // Case 6: Successful login
        // Reset failed attempts
        $user->resetFailedAttempts();
        
        // Update last login information
        $user->updateLastLogin($ipAddress);
        
        // Log the user in
        Auth::login($user, $request->filled('remember'));
        
        // Store login time in session for logout tracking
        Session::put('login_time', now()->timestamp);
        
        // Regenerate session for security
        $request->session()->regenerate();
        
        // Log successful login
        $this->logSuccessfulAttempt($user->user_id, $email, $ipAddress, $userAgent);
        
        // Redirect to coordinator dashboard
        return redirect()->intended(route('coordinator.dashboard'))
                         ->with('success', 'Welcome back, ' . $user->name . '!');
    }

    /**
     * Handle logout request with tracking.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        $user = Auth::user();
        
        if ($user) {
            // Calculate session duration
            $loginTime = Session::get('login_time');
            $sessionDuration = $loginTime ? (now()->timestamp - $loginTime) : null;
            
            // Log logout
            LogoutLog::create([
                'user_id' => $user->user_id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_duration' => $sessionDuration,
                'created_at' => now(),
            ]);
        }
        
        // Logout user
        Auth::logout();
        
        // Invalidate session
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        // Redirect to login page
        return redirect()->route('login')
                         ->with('success', 'You have been logged out successfully.');
    }

    /**
     * Log successful login attempt.
     *
     * @param int $userId
     * @param string $email
     * @param string $ipAddress
     * @param string $userAgent
     * @return void
     */
    private function logSuccessfulAttempt(int $userId, string $email, string $ipAddress, string $userAgent): void
    {
        LoginLog::create([
            'user_id' => $userId,
            'email_entered' => $email,
            'status' => 'SUCCESS',
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'failure_reason' => null,
            'created_at' => now(),
        ]);
    }

    /**
     * Log failed login attempt.
     *
     * @param int|null $userId
     * @param string $email
     * @param string $ipAddress
     * @param string $userAgent
     * @param string $reason
     * @return void
     */
    private function logFailedAttempt(?int $userId, string $email, string $ipAddress, string $userAgent, string $reason): void
    {
        LoginLog::create([
            'user_id' => $userId,
            'email_entered' => $email,
            'status' => 'FAILED',
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'failure_reason' => $reason,
            'created_at' => now(),
        ]);
    }
}
