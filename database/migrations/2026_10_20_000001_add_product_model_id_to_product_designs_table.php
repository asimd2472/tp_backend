<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_designs', function (Blueprint $table) {
            if (! Schema::hasColumn('product_designs', 'product_model_id')) {
                $table->foreignId('product_model_id')->nullable()->after('product_id')->constrained()->cascadeOnDelete();
            }
        });

        $productModels = DB::table('product_models')->get()->keyBy('product_id');
        $designs = DB::table('product_designs')->get();

        foreach ($designs as $design) {
            $productModelId = $productModels->get($design->product_id)?->id;
            if ($productModelId) {
                DB::table('product_designs')
                    ->where('id', $design->id)
                    ->update(['product_model_id' => $productModelId]);
            }
        }

        Schema::table('product_designs', function (Blueprint $table) {
            $table->index(['product_model_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('product_designs', function (Blueprint $table) {
            $table->dropIndex(['product_model_id', 'sort_order']);
            $table->dropConstrainedForeignId('product_model_id');
        });
    }
};
