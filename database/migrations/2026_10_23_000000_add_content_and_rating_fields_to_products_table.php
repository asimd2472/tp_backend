<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('faqs')->nullable();
            $table->json('specifications')->nullable();
            $table->decimal('rating', 2, 1)->nullable();
            $table->text('review')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['faqs', 'specifications', 'rating', 'review']);
        });
    }
};
