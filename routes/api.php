<?php

use App\Http\Controllers\Abonos\AbonoController;
use App\Http\Controllers\Ciudades\CiudadController;
use App\Http\Controllers\Clientes\ClienteController;
use App\Http\Controllers\Creditos\CreditoController;
use App\Http\Controllers\Empresas\EmpresaController;
use App\Http\Controllers\Productos\ProductoController;
use App\Http\Controllers\ReporteCentralesTipo\ReporteCentralesTipoController;
use App\Http\Controllers\Tareas\TareaController;
use App\Http\Controllers\Usuarios\UsuarioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// -- Rutas publicas----------------------------------------

// Lista de ciudades CO
Route::get('/ciudades', [CiudadController::class, 'index']);

// Reporte Centrales Tipos
Route::get('/reportes', [ReporteCentralesTipoController::class, 'index']);

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
        Route::get('/listCreditsClientsActives', [ClienteController::class, 'listCreditsClientsActives']);
        Route::get('/{cliente_id}/{empresa_id?}/{parametrosValidacion?}', [ClienteController::class, 'listMyClient']);
    });

    // Creditos
    Route::prefix('creditos')->group(function () {
        Route::get('/{id}/details', [CreditoController::class, 'creditDetails']);
        Route::get('/detailCredit', [CreditoController::class, 'detailCredit']);
        Route::get('/creditsCobranza', [CreditoController::class, 'creditsCobranza']);
    });

    // Empresas
    Route::prefix('empresas')->group(function () {
        Route::get('/', [EmpresaController::class, 'listMyCompanys']);
    });

    // Abonos
    Route::prefix('abonos')->group(function () {
        // TODO
    });

    // Productos
    Route::prefix('productos')->group(function () {
        Route::get('/', [ProductoController::class, 'listProducts']);
        Route::put('/', [ProductoController::class, 'update']);
        Route::post('/', [ProductoController::class, 'store']);
        Route::delete('/', [ProductoController::class, 'destroy']);
    });

    // Tareas
    Route::prefix('tareas')->group(function () {
        Route::get('/', [TareaController::class, 'listTasks']);
    });

    // Usuarios
    Route::prefix('usuarios')->group(function () {
        Route::get('/listMyUsers', [UsuarioController::class, 'listMyUsers']);
    });
});
