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
            $table->boolean('is_admin')->default(false)->index()->after('password');
            $table->unsignedBigInteger('token_limit')->nullable()->after('is_admin');
            $table->unsignedBigInteger('input_tokens_used')->default(0)->after('token_limit');
            $table->unsignedBigInteger('output_tokens_used')->default(0)->after('input_tokens_used');
            $table->unsignedBigInteger('total_tokens_used')->default(0)->after('output_tokens_used');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_admin']);
            $table->dropColumn([
                'is_admin',
                'token_limit',
                'input_tokens_used',
                'output_tokens_used',
                'total_tokens_used',
            ]);
        });
    }
};
