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
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('input_tokens')->default(0)->after('attachments');
            $table->unsignedBigInteger('output_tokens')->default(0)->after('input_tokens');
            $table->unsignedBigInteger('total_tokens')->default(0)->after('output_tokens');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropColumn(['input_tokens', 'output_tokens', 'total_tokens']);
        });
    }
};
