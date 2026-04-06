<?php

use App\Http\Controllers\Abonos\AbonoController;
use App\Http\Controllers\TipoPago\TipoPagoController;
use App\Http\Controllers\Cajeras\CajeraController;
use App\Http\Controllers\Ciudades\CiudadController;
use App\Http\Controllers\Clientes\ClienteController;
use App\Http\Controllers\Contabilidad\ContabilidadController;
use App\Http\Controllers\Creditos\CreditoController;
use App\Http\Controllers\Destinos\DestinoController;
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
        Route::get('/listClientsCredits', [ClienteController::class, 'listClientsCredits']);
        Route::get('/listMyClientsValidated', [ClienteController::class, 'listMyClientsValidated']);
        Route::get('/{cliente_id}/{empresa_id?}/{parametrosValidacion?}', [ClienteController::class, 'listMyClient']);
    });

    // Creditos
    Route::prefix('creditos')->group(function () {
        Route::get('/listCredits', [CreditoController::class, 'listCredits']);
        Route::get('/{id}/details', [CreditoController::class, 'creditDetails']);
        Route::get('/detailCredit', [CreditoController::class, 'detailCredit']);
        Route::get('/creditsCobranza', [CreditoController::class, 'creditsCobranza']);
        Route::get('/clienteCreditData', [CreditoController::class, 'clienteCreditData']);
    });

    // Abonos
    Route::prefix('abonos')->group(function () {
        Route::get('/listAbonos', [AbonoController::class, 'listAbonos']);
    });

    // Empresas
    Route::prefix('empresas')->group(function () {
        Route::get('/', [EmpresaController::class, 'listMyCompanys']);
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

    // Contabilidad
    Route::prefix('contabilidad')->group(function () {
        Route::get('/listRecibosCXC', [ContabilidadController::class, 'listRecibosCXC']);
    });

    // Usuarios
    Route::prefix('usuarios')->group(function () {
        Route::get('/listMyUsers', [UsuarioController::class, 'listMyUsers']);
    });

    // Cajeras
    Route::prefix('cajeras')->group(function () {
        Route::get('/', [CajeraController::class, 'listCajerasAbono']);
    });

    // Destinos (Líneas de crédito)
    Route::prefix('destinos')->group(function () {
        Route::get('/', [DestinoController::class, 'listDestinos']);
    });

    // Tipos pago
    Route::prefix('tipoPago')->group(function () {
        Route::get('/', [TipoPagoController::class, 'listTiposPago']);
    });
});
