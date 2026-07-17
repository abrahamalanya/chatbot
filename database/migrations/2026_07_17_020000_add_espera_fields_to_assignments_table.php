<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->timestamp('warning_sent_at')->nullable()->after('conversation_expires_at');
            $table->unsignedTinyInteger('warning_count')->default(0)->after('warning_sent_at');
            $table->boolean('esperando_nota')->default(false)->after('warning_count');
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn(['warning_sent_at', 'warning_count', 'esperando_nota']);
        });
    }
};
