<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->enum('tipo', ['texto', 'opcion', 'imagen', 'documento', 'ubicacion', 'video', 'audio'])->default('texto')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->enum('tipo', ['texto', 'opcion', 'imagen', 'documento', 'ubicacion'])->default('texto')->change();
        });
    }
};
