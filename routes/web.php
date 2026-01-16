<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/check-url', function () {
    return asset('storage/colleges/1.png');
});