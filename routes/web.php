<?php

use App\Http\Controllers\InsertionController;
use App\Http\Controllers\PvpController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ServiceController;
use App\Http\Middleware\VerifyWsSecurity;

use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);
Route::get('/pvp/service', [ServiceController::class, 'index']);
Route::post('/pvp/service', [PvpController::class, 'receive'])
    ->middleware(VerifyWsSecurity::class);

Route::get('/listings', [InsertionController::class, 'index']);
Route::get('/listings/{insertion}', [InsertionController::class, 'show']);
