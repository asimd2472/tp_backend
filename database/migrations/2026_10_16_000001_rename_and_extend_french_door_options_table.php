<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_options') && ! Schema::hasTable('french_door_option')) {
            Schema::rename('product_options', 'french_door_option');
        }

        if (! Schema::hasTable('french_door_option')) {
            throw new RuntimeException('The French-door options table could not be found.');
        }

        Schema::table('french_door_option', function (Blueprint $table) {
            $table->string('image_path')->nullable();
            $table->boolean('is_default')->default(false);
        });

        $designId = DB::table('french_door_option')
            ->where('product_slug', 'french-door')
            ->where('type', 'design')
            ->orderBy('id')
            ->value('id');

        if ($designId) {
            DB::table('french_door_option')->where('id', $designId)->update(['is_default' => true]);

            $shadeId = DB::table('french_door_option')
                ->where('product_slug', 'french-door')
                ->where('type', 'shade')
                ->where('parent_id', $designId)
                ->orderBy('id')
                ->value('id');

            if ($shadeId) {
                DB::table('french_door_option')->where('id', $shadeId)->update(['is_default' => true]);
            }
        }

        $sizeId = DB::table('french_door_option')
            ->where('product_slug', 'french-door')
            ->where('type', 'size')
            ->orderBy('id')
            ->value('id');

        if ($sizeId) {
            DB::table('french_door_option')->where('id', $sizeId)->update(['is_default' => true]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('french_door_option')) {
            Schema::table('french_door_option', function (Blueprint $table) {
                $table->dropColumn(['image_path', 'is_default']);
            });
        }

        if (Schema::hasTable('french_door_option') && ! Schema::hasTable('product_options')) {
            Schema::rename('french_door_option', 'product_options');
        }
    }
};
