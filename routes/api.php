<?php

use App\Http\Controllers\Api\ArcaSyncController;
use App\Http\Controllers\Api\ArcaSyncStatusController;
use Illuminate\Support\Facades\Route;

Route::post('/arca/comprobantes', ArcaSyncController::class)
    ->middleware('throttle:10,1')
    ->name('api.arca.comprobantes');

Route::post('/arca/estado', ArcaSyncStatusController::class)
    ->middleware('throttle:30,1')
    ->name('api.arca.estado');
