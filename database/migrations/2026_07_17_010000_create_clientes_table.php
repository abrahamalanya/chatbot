<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('cliente_telefono')->unique();
            $table->foreignId('advisor_id')->nullable()->constrained('advisors')->nullOnDelete();
            $table->string('nombre')->nullable();
            $table->string('correo')->nullable();
            $table->string('documento')->nullable();
            $table->enum('tipo_credito', ['hipotecario', 'vehicular', 'diario'])->nullable();
            $table->enum('etapa', ['completado', 'no_interesado', 'sin_respuesta', 'no_califica', 'seguimiento', 'tiempo_expirado'])->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
