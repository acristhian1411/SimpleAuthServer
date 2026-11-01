<?php

use App\Http\Controllers\Api\PasswordController;
use App\Http\Middleware\EnsureUserCanAccessClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', EnsureUserCanAccessClient::class])->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:api', EnsureUserCanAccessClient::class])->prefix('mobile')->group(function (): void {
    Route::post('/password', [PasswordController::class, 'update']);

    Route::get('/me', function (Request $request) {
        return response()->json([
            'id' => $request->user()->getAuthIdentifier(),
            'name' => $request->user()->name,
            'email' => $request->user()->email,
        ]);
    });
});
