<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('window_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('model')->nullable();
            $table->string('slug')->unique();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->decimal('starting_price', 12, 2)->default(0);
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('main_image')->nullable();
            $table->json('faqs')->nullable();
            $table->json('specifications')->nullable();
            $table->decimal('rating', 2, 1)->nullable();
            $table->text('review')->nullable();
            $table->timestamps();
        });

        Schema::create('window_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('window_product_id')->constrained('window_products')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->decimal('starting_price', 12, 2)->default(0);
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->string('main_image')->nullable();
            $table->timestamps();

            $table->index(['window_product_id', 'name']);
        });

        Schema::create('window_shades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('window_model_id')->constrained('window_models')->cascadeOnDelete();
            $table->string('name');
            $table->string('color_hex', 7)->nullable();
            $table->string('swatch_image')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['window_model_id', 'sort_order']);
        });

        Schema::create('window_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('window_shade_id')->constrained('window_shades')->cascadeOnDelete();
            $table->string('image_path');
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['window_shade_id', 'sort_order']);
        });

        Schema::create('window_sizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('window_shade_id')->constrained('window_shades')->cascadeOnDelete();
            $table->unsignedSmallInteger('width_mm');
            $table->unsignedSmallInteger('height_mm');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['window_shade_id', 'width_mm', 'height_mm'], 'window_sizes_unique_pair');
            $table->index(['window_shade_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('window_sizes');
        Schema::dropIfExists('window_images');
        Schema::dropIfExists('window_shades');
        Schema::dropIfExists('window_models');
        Schema::dropIfExists('window_products');
    }
};
