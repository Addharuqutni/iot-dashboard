<?php

use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\SensorDataController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/sensor-data', [SensorDataController::class, 'store']);
Route::get('/sensor-readings/latest', [SensorDataController::class, 'latest']);
Route::get('/sensor-readings/history', [SensorDataController::class, 'history']);

Route::get('/evaluation/metrics', [EvaluationController::class, 'metrics']);
