<?php

use Illuminate\Support\Facades\Route;

Route::get('/login', function () {
    return view('login'); // points to resources/views/login.blade.php
});
