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
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('folder_id')->nullable()->after('user_id')->constrained('conversation_folders')->nullOnDelete();
            $table->boolean('is_pinned')->default(false)->after('openai_response_id');
            $table->timestamp('archived_at')->nullable()->index()->after('is_pinned');
            $table->text('system_prompt')->nullable()->after('archived_at');
            $table->string('reasoning_effort', 20)->default('none')->after('system_prompt');
            $table->decimal('temperature', 3, 2)->nullable()->after('reasoning_effort');
            $table->boolean('web_search')->default(false)->after('temperature');
            $table->boolean('use_knowledge')->default(false)->after('web_search');
            $table->string('share_token', 64)->nullable()->unique()->after('use_knowledge');
            $table->timestamp('shared_at')->nullable()->after('share_token');
            $table->index(['user_id', 'is_pinned', 'updated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'is_pinned', 'updated_at']);
            $table->dropConstrainedForeignId('folder_id');
            $table->dropColumn([
                'is_pinned',
                'archived_at',
                'system_prompt',
                'reasoning_effort',
                'temperature',
                'web_search',
                'use_knowledge',
                'share_token',
                'shared_at',
            ]);
        });
    }
};
