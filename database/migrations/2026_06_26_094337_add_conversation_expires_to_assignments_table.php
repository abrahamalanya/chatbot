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
        Schema::table('assignments', function (Blueprint $table) {
            $table->unsignedSmallInteger('conversation_duration')->default(15)->after('status');
            $table->timestamp('conversation_expires_at')->nullable()->after('conversation_duration');
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn(['conversation_duration', 'conversation_expires_at']);
        });
    }
};
