<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_models', function (Blueprint $table) {
            $table->longText('accessories')->nullable();
        });

        Schema::table('window_models', function (Blueprint $table) {
            $table->longText('accessories')->nullable();
        });

        Schema::table('product_designs', function (Blueprint $table) {
            $table->decimal('starting_price', 12, 2)->nullable();
        });

        Schema::table('product_shades', function (Blueprint $table) {
            $table->decimal('starting_price', 12, 2)->nullable();
        });

        Schema::table('window_shades', function (Blueprint $table) {
            $table->decimal('starting_price', 12, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('window_shades', function (Blueprint $table) {
            $table->dropColumn('starting_price');
        });

        Schema::table('product_shades', function (Blueprint $table) {
            $table->dropColumn('starting_price');
        });

        Schema::table('product_designs', function (Blueprint $table) {
            $table->dropColumn('starting_price');
        });

        Schema::table('window_models', function (Blueprint $table) {
            $table->dropColumn('accessories');
        });

        Schema::table('product_models', function (Blueprint $table) {
            $table->dropColumn('accessories');
        });
    }
};
