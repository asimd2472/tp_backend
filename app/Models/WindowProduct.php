<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WindowProduct extends Model
{
    protected $fillable = [
        'name',
        'category',
        'model',
        'slug',
        'short_description',
        'description',
        'starting_price',
        'is_premium',
        'is_active',
        'main_image',
        'faqs',
        'specifications',
        'rating',
        'review',
    ];

    protected function casts(): array
    {
        return [
            'starting_price' => 'decimal:2',
            'is_premium' => 'boolean',
            'is_active' => 'boolean',
            'faqs' => 'array',
            'specifications' => 'array',
            'rating' => 'decimal:1',
        ];
    }

    public function models(): HasMany
    {
        return $this->hasMany(WindowModel::class)->orderBy('name')->orderBy('id');
    }

    public function getThumbnailUrlAttribute(): string
    {
        if (! $this->main_image) {
            return asset('uploads/default/model.webp');
        }

        return str_starts_with($this->main_image, 'http') ? $this->main_image : asset('uploads/'.ltrim($this->main_image, '/'));
    }
}
