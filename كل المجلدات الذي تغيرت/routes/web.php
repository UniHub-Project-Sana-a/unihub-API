<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\RoutesController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/check-url', function () {
    return asset('storage/colleges/1.png');
});

// Routes Viewer - للتطوير فقط
if (app()->environment('local', 'development')) {
    Route::get('/dev/routes', [RoutesController::class, 'index'])->name('dev.routes');
    Route::get('/dev/routes/json', [RoutesController::class, 'json'])->name('dev.routes.json');
}

// Routes Viewer - محمي بـ Admin
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/routes', [RoutesController::class, 'index'])->name('admin.routes');
    Route::get('/routes/json', [RoutesController::class, 'json'])->name('admin.routes.json');
});