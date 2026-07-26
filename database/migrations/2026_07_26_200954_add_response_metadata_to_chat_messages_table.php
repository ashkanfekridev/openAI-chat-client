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
            $table->string('openai_response_id')->nullable()->index()->after('total_tokens');
            $table->json('citations')->nullable()->after('openai_response_id');
            $table->unsignedBigInteger('estimated_cost_micros')->default(0)->after('citations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropIndex(['openai_response_id']);
            $table->dropColumn(['openai_response_id', 'citations', 'estimated_cost_micros']);
        });
    }
};
