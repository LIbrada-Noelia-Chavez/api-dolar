<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Cotizacion;

class ImportarCotizaciones extends Command
{
    protected $signature = 'cotizaciones:importar';
    protected $description = 'Consulta la API del dólar y guarda las cotizaciones';

    public function handle()
    {
        $url = config('services.dolarapi.url');
        $response = Http::get($url);

        if ($response->failed()) {
            $this->error('Error al consultar la API');
            return;
        }

        $datos = $response->json();

        foreach ($datos as $item) {
            Cotizacion::create([
                'tipo'        => $item['casa'] ?? $item['nombre'],
                'tipo_valor'  => 'compra',
                'valor'       => $item['compra'] ?? 0,
                'obtenido_en' => now(),
                'fuente'      => $url,
            ]);

            Cotizacion::create([
                'tipo'        => $item['casa'] ?? $item['nombre'],
                'tipo_valor'  => 'venta',
                'valor'       => $item['venta'] ?? 0,
                'obtenido_en' => now(),
                'fuente'      => $url,
            ]);
        }

        $this->info('Cotizaciones guardadas correctamente ✅');
    }
}
