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
        Schema::table('assignments', function (Blueprint $table) {
            $table->foreignId('whatsapp_number_id')->nullable()->after('advisor_id')->constrained()->nullOnDelete();
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('whatsapp_number_id')->nullable()->after('advisor_id')->constrained()->nullOnDelete();
        });

        // Backfill: todo lo existente pertenece a la "Línea principal" migrada
        // en create_whatsapp_numbers_table (si es que se sembró alguna).
        $lineaPrincipalId = DB::table('whatsapp_numbers')->where('nombre', 'Línea principal')->value('id');

        if ($lineaPrincipalId) {
            DB::table('assignments')->whereNull('whatsapp_number_id')->update(['whatsapp_number_id' => $lineaPrincipalId]);
            DB::table('messages')->whereNull('whatsapp_number_id')->update(['whatsapp_number_id' => $lineaPrincipalId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('whatsapp_number_id');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('whatsapp_number_id');
        });
    }
};
