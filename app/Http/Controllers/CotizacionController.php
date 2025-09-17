<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cotizacion;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\JsonResponse;

class CotizacionController extends Controller
{
    /**
     * Convierte un valor en base a la cotización actual de un tipo de dólar.
     * Ejemplo: /api/convertir?valor=100&tipo=blue
     */
    public function convertir(Request $request): JsonResponse
    {
        $valorUSD = $request->query('valor', 1);
        $tipo = $request->query('tipo', 'oficial');

        // Llamada a la API externa
        $response = Http::get(config('services.dolarapi.url') . '/' . $tipo);

        if ($response->failed()) {
            return response()->json([
                'error' => 'No se pudo obtener la cotización del dólar.'
            ], 500);
        }

        $data = $response->json();

        $cotizacion = $data['venta'] ?? null;

        if (!$cotizacion) {
            return response()->json([
                'error' => 'Cotización no disponible.'
            ], 500);
        }

        // Guardar en base de datos
        $registro = Cotizacion::create([
            'tipo' => $tipo,
            'tipo_valor' => 'venta',
            'valor' => $cotizacion,
            'obtenido_en' => now(),
        ]);

        $resultado = $valorUSD * $cotizacion;

        return response()->json([
            'tipo' => $tipo,
            'valor_dolar' => $valorUSD,
            'cotizacion' => $cotizacion,
            'resultado_en_pesos' => round($resultado, 2),
            'guardado' => $registro,
        ]);
    }

    /**
     * Devuelve todas las cotizaciones guardadas en la base de datos.
     */
    public function index(): JsonResponse
    {
        $cotizaciones = Cotizacion::orderBy('obtenido_en', 'desc')->get();

        return response()->json($cotizaciones);
    }

    /**
     * Devuelve el promedio mensual según tipo y tipo_valor.
     * Ejemplo: /api/cotizaciones/promedio?tipo=blue&tipo_valor=venta&mes=9&anio=2025
     */
    public function promedio(Request $request): JsonResponse
    {
        $tipo = $request->query('tipo', 'oficial');
        $tipo_valor = $request->query('tipo_valor', 'venta');
        $mes = $request->query('mes', now()->month);
        $anio = $request->query('anio', now()->year);

        $promedio = Cotizacion::where('tipo', $tipo)
            ->where('tipo_valor', $tipo_valor)
            ->whereMonth('obtenido_en', $mes)
            ->whereYear('obtenido_en', $anio)
            ->avg('valor');

        return response()->json([
            'tipo' => $tipo,
            'tipo_valor' => $tipo_valor,
            'mes' => $mes,
            'anio' => $anio,
            'promedio' => round($promedio, 2),
        ]);
    }
}

