<?php

use App\Http\Controllers\Clientes\ClienteController;
use App\Http\Controllers\Empresas\EmpresaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// -- Rutas protegidas --------------------------------------------------------
Route::middleware('auth:sanctum')->group(function () {
    // Usuario autenticado
    Route::get('/user', fn(Request $request) => $request->user()->load([
        'persona:id,nombre',
        'empresa:id,razon_social',
    ]));

    // Clientes
    Route::prefix('clientes')->group(function () {
        Route::get('/', [ClienteController::class, 'listMyClients']);
    });

    // Empresas
    Route::prefix('empresas')->group(function () {
        Route::get('/', [EmpresaController::class, 'listMyCompanys']);
    });
});
