<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropForeign(['advisor_id']);
            $table->foreignId('advisor_id')->nullable()->change()->constrained()->nullOnDelete();
            $table->enum('status', ['pending', 'assigned', 'closed'])->default('pending')->after('cliente_telefono');
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->foreignId('advisor_id')->nullable(false)->change();
        });
    }
};
