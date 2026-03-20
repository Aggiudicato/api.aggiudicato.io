<?php


use App\Http\Controllers\PvpController;
use App\Http\Controllers\HomeController;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\App;

Route::get('/', [HomeController::class, 'index']);
Route::post('/pvp/service', [PvpController::class, 'receiveAsta']); 
//Route::get('/pvp/service', [App\Http\Controllers\ServiceController::class, 'index']);
