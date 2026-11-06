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
use Illuminate\Support\Facades\DB;


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

     Route::get('/admin/profesores', [ProfesorController::class, 'index']);
    Route::get('/admin/profesores/{id}', [ProfesorController::class, 'show']);

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


Route::get('/diag', function () {
    $out = [
        'app_key_set' => (bool) config('app.key'),
        'app_debug' => config('app.debug'),
        'env_APP_DEBUG' => getenv('APP_DEBUG'),
        'cache_store' => config('cache.default'),
        'db_host_pooler' => str_contains((string) config('database.connections.pgsql.host'), 'pooler.supabase.com'),
        'db_user_con_punto' => str_starts_with((string) config('database.connections.pgsql.username'), 'postgres.'),
        'db_password_set' => (bool) config('database.connections.pgsql.password'),
    ];

    try {
        DB::connection()->getPdo();
        $out['db'] = 'ok';
    } catch (\Throwable $e) {
        $out['db'] = class_basename($e) . ': ' . substr($e->getMessage(), 0, 160);
    }

    return $out;
});