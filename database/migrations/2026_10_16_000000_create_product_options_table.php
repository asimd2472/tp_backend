<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('french_door_option', function (Blueprint $table) {
            $table->id();
            $table->string('product_slug', 80);
            $table->string('type', 20);
            $table->foreignId('parent_id')->nullable()->constrained('french_door_option')->cascadeOnDelete();
            $table->string('name', 120)->nullable();
            $table->unsignedSmallInteger('width_mm')->nullable();
            $table->unsignedSmallInteger('height_mm')->nullable();
            $table->string('color_hex', 7)->nullable();
            $table->timestamps();

            $table->index(['product_slug', 'type']);
        });

        $designs = [
            'Lumière Pro',
            'Lumière Classic',
            'Lumière Classic Plus',
            'Lumière Basic',
        ];
        $shades = [
            ['name' => 'Forest Brown', 'color_hex' => '#4B2E24'],
            ['name' => 'Snowflake White', 'color_hex' => '#FFF0D4'],
        ];
        $timestamp = now();

        foreach ($designs as $name) {
            $designId = DB::table('french_door_option')->insertGetId([
                'product_slug' => 'french-door',
                'type' => 'design',
                'name' => $name,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);

            foreach ($shades as $shade) {
                DB::table('french_door_option')->insert([
                    'product_slug' => 'french-door',
                    'type' => 'shade',
                    'parent_id' => $designId,
                    'name' => $shade['name'],
                    'color_hex' => $shade['color_hex'],
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);
            }
        }

        foreach ([[2134, 2134], [2134, 2285], [2134, 2440], [2440, 2134], [2440, 2285], [2440, 2440]] as [$width, $height]) {
            DB::table('french_door_option')->insert([
                'product_slug' => 'french-door',
                'type' => 'size',
                'width_mm' => $width,
                'height_mm' => $height,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('french_door_option');
        Schema::dropIfExists('product_options');
    }
};
