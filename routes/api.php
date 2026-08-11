<?php

use App\Http\Controllers\Api\ArcaSyncController;
use Illuminate\Support\Facades\Route;

Route::post('/arca/comprobantes', ArcaSyncController::class)
    ->middleware('throttle:10,1')
    ->name('api.arca.comprobantes');
