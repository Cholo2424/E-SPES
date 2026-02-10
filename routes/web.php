<?php

use Illuminate\Support\Facades\Route;

Route::get('/login', function () {
    return view('login'); // points to resources/views/login.blade.php
});

Route::get('/forgot-password', function () {
    return view('forgotpass'); // resources/views/forgotpass.blade.php
});

Route::get('/forgotpassverifycode', function () {
    return view('forgotpassverifycode');
});