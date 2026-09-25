<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\EnsureUserCanAccessClient;

Route::middleware(['auth:api', EnsureUserCanAccessClient::class])->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:api', EnsureUserCanAccessClient::class])->prefix('mobile')->group(function (): void {
    Route::get('/me', function (Request $request) {
        return response()->json([
            'id' => $request->user()->getAuthIdentifier(),
            'name' => $request->user()->name,
            'email' => $request->user()->email,
        ]);
    });
});
