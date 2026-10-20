<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->decimal('starting_price', 12, 2)->default(0);
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('main_image')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_models');
    }
};
