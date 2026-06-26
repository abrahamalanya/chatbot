<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['advisor_id']);
            $table->foreignId('advisor_id')->nullable()->change()->constrained()->nullOnDelete();
            $table->enum('tipo', ['texto', 'opcion'])->default('texto')->after('sender');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('tipo');
            $table->foreignId('advisor_id')->nullable(false)->change();
        });
    }
};
