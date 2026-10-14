<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TugasController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::get('/tugas', [TugasController::class, 'index']);
Route::get('/tugas/{tugas}', [TugasController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/tugas', [TugasController::class, 'store']);
    Route::put('/tugas/{tugas}', [TugasController::class, 'update']);
    Route::delete('/tugas/{tugas}', [TugasController::class, 'destroy']);
});
