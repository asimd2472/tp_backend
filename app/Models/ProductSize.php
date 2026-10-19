<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSize extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_shade_id',
        'width_mm',
        'height_mm',
        'sort_order',
    ];

    public function shade(): BelongsTo
    {
        return $this->belongsTo(ProductShade::class, 'product_shade_id');
    }
}
