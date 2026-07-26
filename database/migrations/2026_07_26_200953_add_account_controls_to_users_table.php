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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->index()->after('is_admin');
            $table->string('role', 20)->default('user')->index()->after('is_active');
            $table->json('allowed_models')->nullable()->after('role');
            $table->string('quota_period', 20)->default('monthly')->after('token_limit');
            $table->timestamp('usage_period_started_at')->nullable()->after('quota_period');
            $table->timestamp('usage_period_ends_at')->nullable()->index()->after('usage_period_started_at');
            $table->string('vector_store_id')->nullable()->after('total_tokens_used');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['role']);
            $table->dropIndex(['usage_period_ends_at']);
            $table->dropColumn([
                'is_active',
                'role',
                'allowed_models',
                'quota_period',
                'usage_period_started_at',
                'usage_period_ends_at',
                'vector_store_id',
            ]);
        });
    }
};
