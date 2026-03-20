<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PvpController;
use Illuminate\Support\Facades\App;

Route::get('/', [App\Http\Controllers\HomeController::class, 'index']);
//Route::get('/pvp/service', [App\Http\Controllers\ServiceController::class, 'index']);
Route::post('/pvp/service', [App\Http\Controllers\PvpController::class, 'receiveAsta']);