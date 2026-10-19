<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_shades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_design_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color_hex', 7)->nullable();
            $table->string('swatch_image')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_design_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_shades');
    }
};
