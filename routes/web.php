<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
});

Route::get('/health', function () {
    $details = [
        'name' => env('APP_NAME'),
        'env' => env('APP_ENV'),
        'debug' => env('APP_DEBUG'),
        'url' => env('APP_URL'),
        'timezone' => config('app.timezone'),
        'locale' => config('app.locale'),
        'version' => Application::VERSION,
        'php_version' => PHP_VERSION,
    ];
    
    $details['baseUrl'] = request()->fullUrl();
    $details['requestUrl'] = request()->getRequestUri();
    $details['headers'] = request()->header();
    return response()->json($details);
});


