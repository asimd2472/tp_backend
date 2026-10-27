<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WindowModel extends Model
{
    protected $fillable = [
        'window_product_id',
        'name',
        'slug',
        'short_description',
        'description',
        'starting_price',
        'is_premium',
        'is_active',
        'is_default',
        'main_image',
    ];

    protected function casts(): array
    {
        return [
            'starting_price' => 'decimal:2',
            'is_premium' => 'boolean',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(WindowProduct::class, 'window_product_id');
    }

    public function shades(): HasMany
    {
        return $this->hasMany(WindowShade::class)->orderBy('sort_order')->orderBy('id');
    }

    public function getMainImageUrlAttribute(): ?string
    {
        if (! $this->main_image) {
            return asset('uploads/default/model.webp');
        }

        return str_starts_with($this->main_image, 'http') ? $this->main_image : asset('uploads/'.ltrim($this->main_image, '/'));
    }
}
