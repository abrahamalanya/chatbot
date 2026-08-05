<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('whatsapp_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('phone_number_id')->unique();
            $table->string('display_phone_number')->nullable();
            $table->boolean('activo')->default(true);
            $table->string('template_asesor_asignado')->nullable();
            $table->string('template_asesor_acepto')->nullable();
            $table->timestamps();
        });

        // Migra la configuración productiva actual (un solo número en .env)
        // para no perder el hilo de las conversaciones ya existentes.
        $phoneNumberId = env('WHATSAPP_PHONE_NUMBER_ID');

        if (!empty($phoneNumberId)) {
            DB::table('whatsapp_numbers')->insert([
                'nombre'           => 'Línea principal',
                'phone_number_id'  => $phoneNumberId,
                'activo'           => true,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_numbers');
    }
};
