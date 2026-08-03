<?php

use App\Http\Controllers\Api\V1\Admin\TokenController as AdminTokenController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\TokenController;
use App\Http\Controllers\Api\V1\UserController;
use App\Enums\Permission;
use App\Enums\Role;
use App\Enums\TokenAbility;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('register', RegisterController::class)->middleware('throttle:6,1');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:6,1');

    Route::post('forgot-password', [PasswordResetController::class, 'forgot'])
        ->middleware('throttle:6,1');
    Route::post('reset-password', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:6,1');

    Route::get('email/verify/{id}/{hash}', [AuthController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('api.verification.verify');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);

        Route::get('email/verification-status', [AuthController::class, 'notice']);
        Route::post('email/verification-notification', [AuthController::class, 'send'])
            ->middleware('throttle:6,1');

        Route::get('user', UserController::class);

        Route::middleware('verified')->group(function () {
            Route::patch('profile', [ProfileController::class, 'update'])
                ->middleware([
                    'permission:'.Permission::ProfileWrite->value,
                    'ability:'.TokenAbility::ProfileWrite->value,
                ]);

            Route::put('profile/password', [ProfileController::class, 'updatePassword'])
                ->middleware([
                    'permission:'.Permission::ProfileWrite->value,
                    'ability:'.TokenAbility::ProfileWrite->value,
                ]);

            Route::middleware([
                'permission:'.Permission::TokensManage->value,
                'ability:'.TokenAbility::TokensManage->value,
            ])->group(function () {
                Route::get('tokens', [TokenController::class, 'index']);
                Route::post('tokens', [TokenController::class, 'store']);
                Route::delete('tokens', [TokenController::class, 'destroyAll']);
                Route::delete('tokens/{token}', [TokenController::class, 'destroy']);
            });

            Route::prefix('admin')->middleware([
                'role:'.Role::Admin->value,
                'ability:'.TokenAbility::TokensManage->value,
            ])->group(function () {
                Route::get('tokens', [AdminTokenController::class, 'index']);
                Route::delete('tokens/{token}', [AdminTokenController::class, 'destroy']);
            });
        });
    });
});
