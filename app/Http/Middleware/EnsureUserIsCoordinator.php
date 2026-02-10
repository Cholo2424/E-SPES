<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsCoordinator
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login')
                           ->with('error', 'Please login to access this page.');
        }

        $user = Auth::user();

        // Check if user is active
        if (!$user->is_active) {
            Auth::logout();
            return redirect()->route('login')
                           ->with('error', 'Your account has been deactivated.');
        }

        // Check if user is a coordinator
        if (!$user->isCoordinator()) {
            Auth::logout();
            return redirect()->route('login')
                           ->with('error', 'You do not have permission to access this area.');
        }

        return $next($request);
    }
}
