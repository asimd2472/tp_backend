<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dealers', function (Blueprint $table) {
            $table->id();
            $table->string('state')->nullable()->index();
            $table->string('district')->nullable()->index();
            $table->string('dealer_name')->index();
            $table->text('dealer_address')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('mobile_number', 50)->nullable()->index();
            $table->string('email_id')->nullable()->index();
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dealers');
    }
};
