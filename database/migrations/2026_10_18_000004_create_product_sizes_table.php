<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_sizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_shade_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('width_mm');
            $table->unsignedSmallInteger('height_mm');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_shade_id', 'width_mm', 'height_mm'], 'product_sizes_unique_pair');
            $table->index(['product_shade_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_sizes');
    }
};
