<?php

use Illuminate\Support\Facades\Route;

Route::get('/', [App\Http\Controllers\HomeController::class, 'index']);
Route::get('/pvp/service', [App\Http\Controllers\ServiceController::class, 'index']);