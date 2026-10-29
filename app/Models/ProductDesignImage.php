<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductDesignImage extends Model
{
    protected $fillable = [
        'product_design_id',
        'image_path',
        'is_primary',
        'sort_order',
    ];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function design(): BelongsTo
    {
        return $this->belongsTo(ProductDesign::class, 'product_design_id');
    }

    public function getUrlAttribute(): string
    {
        return str_starts_with($this->image_path, 'http')
            ? $this->image_path
            : asset('uploads/'.ltrim($this->image_path, '/'));
    }
}