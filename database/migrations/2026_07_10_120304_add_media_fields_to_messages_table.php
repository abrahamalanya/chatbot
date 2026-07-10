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
            $table->enum('tipo', ['texto', 'opcion', 'imagen', 'documento', 'ubicacion'])->default('texto')->change();
            $table->string('media_path')->nullable()->after('tipo');
            $table->string('media_mime_type')->nullable()->after('media_path');
            $table->string('media_filename')->nullable()->after('media_mime_type');
            $table->decimal('latitude', 10, 7)->nullable()->after('media_filename');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['media_path', 'media_mime_type', 'media_filename', 'latitude', 'longitude']);
            $table->enum('tipo', ['texto', 'opcion'])->default('texto')->change();
        });
    }
};
