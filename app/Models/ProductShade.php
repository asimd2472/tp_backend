<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductShade extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_design_id',
        'name',
        'color_hex',
        'swatch_image',
        'sort_order',
    ];

    public function design(): BelongsTo
    {
        return $this->belongsTo(ProductDesign::class, 'product_design_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function sizes(): HasMany
    {
        return $this->hasMany(ProductSize::class)->orderBy('width_mm')->orderBy('height_mm');
    }

    public function primaryImage(): ?ProductImage
    {
        return $this->images()->where('is_primary', true)->first();
    }

    public function getSwatchUrlAttribute(): ?string
    {
        if (! $this->swatch_image) {
            return null;
        }

        return str_starts_with($this->swatch_image, 'http') ? $this->swatch_image : asset('storage/'.$this->swatch_image);
    }
}
