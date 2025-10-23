<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AuthPasswordController;

use App\Http\Controllers\Api\V1\UsersController;
use App\Http\Controllers\Api\V1\LookupsController;
use App\Http\Controllers\Api\V1\UserTypeController;
use App\Http\Controllers\Api\V1\UserTypePermissionController;

use App\Http\Controllers\Api\V1\Admin\SystemController;
use App\Http\Controllers\Api\V1\Admin\SettingsController; // أضف هذا السطر

Route::prefix('v1')->group(function () {

    // Auth (عام) + Rate limit
    Route::post('auth/login',           [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('auth/forgot-password', [AuthPasswordController::class, 'forgot'])->middleware('throttle:forgot');
    Route::post('auth/reset-password',  [AuthPasswordController::class, 'reset'])->middleware('throttle:reset');

    // المسارات الإدارية المحمية
    Route::middleware(['auth:api', 'activity:admin', 'throttle:60,1'])->group(function () {

        // Me/Logout
        Route::get('auth/me',     [AuthController::class, 'me']);
        Route::post('auth/logout',[AuthController::class, 'logout']);

        // Lookups (عرض فقط)
        Route::get('user-types', [LookupsController::class, 'userTypes'])
            ->name('user_types.index')
            ->middleware('perm:user_types.view,false');

        Route::get('permissions', [LookupsController::class, 'permissions'])
            ->name('permissions.index')
            ->middleware('perm:permissions.view,false');

        Route::get('colleges', [LookupsController::class, 'colleges'])
            ->name('colleges.index')
            ->middleware('perm:colleges.view,false');

        // Users CRUD
        Route::get('users',            [UsersController::class, 'index'])->name('users.index')->middleware('perm:users.view,false');
        Route::get('users/{user}',     [UsersController::class, 'show'])->name('users.show')->middleware('perm:users.view,false');
        Route::post('users',           [UsersController::class, 'store'])->name('users.store')->middleware('perm:users.create,true');
        Route::put('users/{user}',     [UsersController::class, 'update'])->name('users.update')->middleware('perm:users.update,true');
        Route::delete('users/{user}',  [UsersController::class, 'destroy'])->name('users.delete')->middleware('perm:users.delete,true');
        Route::post('users/{user}/restore', [UsersController::class, 'restore'])->name('users.restore')->middleware('perm:users.update,true');

        // UserTypes (Roles) — CRUD أساسي
        Route::post('user-types',              [UserTypeController::class, 'store'])->middleware('perm:user_types.create,false');
        Route::put('user-types/{userType}',    [UserTypeController::class, 'update'])->middleware('perm:user_types.update,false');
        Route::delete('user-types/{userType}', [UserTypeController::class, 'destroy'])->middleware('perm:user_types.delete,false');

        // UserType Permissions
        Route::get('user-types/{userTypeId}/permissions', [UserTypePermissionController::class, 'index'])
            ->middleware('perm:user_type_permissions.view,false');

        Route::post('user-types/{userType}/permissions/bulk-assign', [UserTypePermissionController::class, 'bulkAssign'])
            ->middleware('perm:user_type_permissions.manage,true');

        // Admin: Sessions & Audit logs
        Route::get('admin/sessions',         [SystemController::class, 'sessions'])->middleware('perm:sessions.view,false');
        Route::post('admin/sessions/revoke', [SystemController::class, 'revokeSession'])->middleware('perm:sessions.revoke,false');
        Route::get('admin/audit-logs',       [SystemController::class, 'auditLogs'])->middleware('perm:audit_logs.view,false');

        // Admin: Security Policy Settings
        Route::get('admin/security/policy', [SettingsController::class, 'getPolicy'])->middleware('perm:settings.view,false');
        Route::put('admin/security/policy', [SettingsController::class, 'updatePolicy'])->middleware('perm:settings.manage,false');

    });
});