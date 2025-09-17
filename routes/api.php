<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CotizacionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Aquí definís las rutas de tu API. Todas estarán prefijadas con /api
| automáticamente, ya que este archivo maneja las rutas de la API.
|
*/

// Convierte un valor en base al tipo de dólar
Route::get('/convertir', [CotizacionController::class, 'convertir']);

// Devuelve todas las cotizaciones históricas
Route::get('/cotizaciones', [CotizacionController::class, 'index']);

// Devuelve el promedio mensual según tipo y tipo_valor
Route::get('/cotizaciones/promedio', [CotizacionController::class, 'promedio']);
