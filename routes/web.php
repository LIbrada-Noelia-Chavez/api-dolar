<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// 👇 esta va afuera
Route::view('/dashboard', 'dashboard');
