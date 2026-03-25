<?php

use App\Http\Controllers\CiudadController;
use App\Http\Controllers\Clientes\ClienteController;
use App\Http\Controllers\Empresas\EmpresaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// -- Rutas publicas----------------------------------------

// Lista de ciudades CO
Route::get('/ciudades', [CiudadController::class, 'index']);

// -- Rutas protegidas --------------------------------------------------------
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    // Usuario autenticado
    Route::get('/user', fn(Request $request) => $request->user()->load([
        'persona:id,nombre',
        'empresa:id,razon_social',
    ]));

    // Clientes
    Route::prefix('clientes')->group(function () {
        Route::get('/', [ClienteController::class, 'listMyClients']);
        Route::get('/listCreditsClients', [ClienteController::class, 'listCreditsClients']);
        Route::get('/listMyClientsValidated', [ClienteController::class, 'listMyClientsValidated']);
        Route::get('/{cliente_id}/{empresa_id?}/{parametrosValidacion?}', [ClienteController::class, 'listMyClient']);
    });

    // Empresas
    Route::prefix('empresas')->group(function () {
        Route::get('/', [EmpresaController::class, 'listMyCompanys']);
    });
});
