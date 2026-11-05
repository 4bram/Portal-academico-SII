<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AcademicoController;
use App\Http\Controllers\ProfesorController;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('sii.token')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/academico/perfil', [AcademicoController::class, 'perfil']);
    Route::get('/academico/calificaciones', [AcademicoController::class, 'calificaciones']);
    Route::get('/academico/kardex', [AcademicoController::class, 'kardex']);
    Route::get('/academico/horarios', [AcademicoController::class, 'horarios']);
    Route::get('/profesores', [ProfesorController::class, 'index']);
    Route::get('/profesores/{id}', [ProfesorController::class, 'show']);
});



Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
