<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:api')->prefix('mobile')->group(function (): void {
    Route::get('/me', function (Request $request) {
        return response()->json([
            'id' => $request->user()->getAuthIdentifier(),
            'name' => $request->user()->name,
            'email' => $request->user()->email,
        ]);
    });
});
