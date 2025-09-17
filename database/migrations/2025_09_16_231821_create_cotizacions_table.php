<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizacions', function (Blueprint $table) {
            $table->id();
            $table->string('tipo');                              // oficial, blue, mep, ccl
            $table->enum('tipo_valor', ['compra','venta']);
            $table->decimal('valor', 12, 2);
            $table->dateTime('obtenido_en');                     // timestamp de la cotización
            $table->string('fuente')->nullable();
            $table->timestamps();

            $table->index(['tipo','tipo_valor','obtenido_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizacions');
    }
};
