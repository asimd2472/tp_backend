<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_models', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->after('is_active');
            $table->index(['product_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::table('product_models', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'is_default']);
            $table->dropColumn('is_default');
        });
    }
};
