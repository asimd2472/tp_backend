<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductDesignSize extends Model
{
    protected $fillable = [
        'product_design_id',
        'width_mm',
        'height_mm',
        'sort_order',
    ];

    public function design(): BelongsTo
    {
        return $this->belongsTo(ProductDesign::class, 'product_design_id');
    }
}