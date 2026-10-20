<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Product extends Model
{
    use HasFactory;

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
    ];

    protected function casts(): array
    {
        return [
            'starting_price' => 'decimal:2',
            'is_premium' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function models(): HasMany
    {
        return $this->hasMany(ProductModel::class)->orderBy('name')->orderBy('id');
    }

    public function designs(): HasManyThrough
    {
        return $this->hasManyThrough(ProductDesign::class, ProductModel::class)->orderBy('sort_order')->orderBy('id');
    }

    public function shades(): HasManyThrough
    {
        return $this->hasManyThrough(ProductShade::class, ProductDesign::class);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if (! $this->main_image) {
            return null;
        }

        return str_starts_with($this->main_image, 'http') ? $this->main_image : asset('uploads/'.$this->main_image);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
