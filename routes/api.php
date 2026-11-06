<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AcademicoController;
use App\Http\Controllers\ProfesorController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminProfesorController;
use App\Http\Controllers\AdminHorarioController;
use App\Http\Controllers\AdminMateriaController;


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

Route::post('/admin/login', [AdminAuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/admin/me', [AdminAuthController::class, 'me']);

    Route::post('/admin/profesores', [AdminProfesorController::class, 'store']);
    Route::put('/admin/profesores/{profesor}', [AdminProfesorController::class, 'update']);
    Route::delete('/admin/profesores/{profesor}', [AdminProfesorController::class, 'destroy']);
    Route::post('/admin/profesores/{profesor}/horarios', [AdminHorarioController::class, 'store']);
    Route::put('/admin/horarios/{horario}', [AdminHorarioController::class, 'update']);
    Route::delete('/admin/horarios/{horario}', [AdminHorarioController::class, 'destroy']);

    Route::post('/admin/profesores/{profesor}/materias', [AdminMateriaController::class, 'store']);
    Route::put('/admin/materias/{materia}', [AdminMateriaController::class, 'update']);
    Route::delete('/admin/materias/{materia}', [AdminMateriaController::class, 'destroy']);
});


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
