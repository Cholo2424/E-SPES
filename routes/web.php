<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CoordinatorController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication Routes (Accessible to guests only)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// Logout Route (Accessible to authenticated users only)
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Coordinator Routes (Protected by auth and coordinator middleware)
Route::middleware(['auth', 'coordinator'])->prefix('coordinator')->name('coordinator.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [CoordinatorController::class, 'dashboard'])->name('dashboard');
    
    // Login History
    Route::get('/login-history', [CoordinatorController::class, 'loginHistory'])->name('login.history');
    
    // Logout History
    Route::get('/logout-history', [CoordinatorController::class, 'logoutHistory'])->name('logout.history');
});

// Forgot Password Routes (For future implementation)
Route::get('/forgot-password', function () {
    return view('forgotpass');
})->name('password.request');

Route::get('/forgotpass-verifcode', function () {
    return view('forgotpass-verifcode');
})->name('password.verify');
