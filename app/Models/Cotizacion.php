<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    protected $fillable = ['tipo','tipo_valor','valor','obtenido_en','fuente'];
    protected $casts = [
        'obtenido_en' => 'datetime',
        'valor' => 'decimal:2',
    ];
}
