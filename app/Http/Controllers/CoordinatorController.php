<?php

namespace App\Http\Controllers;

use App\Models\LoginLog;
use App\Models\LogoutLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CoordinatorController extends Controller
{
    /**
     * Show the coordinator dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function dashboard()
    {
        $user = Auth::user();
        
        // Get recent login history (last 10 logins for current user)
        $recentLogins = LoginLog::where('user_id', $user->user_id)
                                ->orderBy('created_at', 'desc')
                                ->limit(10)
                                ->get();
        
        // Get recent logout history (last 5 logouts for current user)
        $recentLogouts = LogoutLog::where('user_id', $user->user_id)
                                  ->orderBy('created_at', 'desc')
                                  ->limit(5)
                                  ->get();
        
        // Get statistics
        $stats = [
            'total_logins' => LoginLog::where('user_id', $user->user_id)
                                      ->where('status', 'SUCCESS')
                                      ->count(),
            'failed_attempts' => LoginLog::where('user_id', $user->user_id)
                                         ->where('status', 'FAILED')
                                         ->count(),
            'total_sessions' => LogoutLog::where('user_id', $user->user_id)->count(),
            'last_login' => $user->last_login_at ? $user->last_login_at->format('M d, Y H:i:s') : 'N/A',
            'last_login_ip' => $user->last_login_ip ?? 'N/A',
        ];
        
        return view('coordinator.dashboard', compact('user', 'recentLogins', 'recentLogouts', 'stats'));
    }

    /**
     * Show complete login history.
     *
     * @return \Illuminate\View\View
     */
    public function loginHistory()
    {
        $user = Auth::user();
        
        $loginLogs = LoginLog::where('user_id', $user->user_id)
                             ->orderBy('created_at', 'desc')
                             ->paginate(20);
        
        return view('coordinator.login-history', compact('loginLogs', 'user'));
    }

    /**
     * Show complete logout history.
     *
     * @return \Illuminate\View\View
     */
    public function logoutHistory()
    {
        $user = Auth::user();
        
        $logoutLogs = LogoutLog::where('user_id', $user->user_id)
                               ->orderBy('created_at', 'desc')
                               ->paginate(20);
        
        return view('coordinator.logout-history', compact('logoutLogs', 'user'));
    }
}
