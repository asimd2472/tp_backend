<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WindowShade extends Model
{
    protected $fillable = [
        'window_model_id',
        'name',
        'color_hex',
        'swatch_image',
        'sort_order',
    ];

    public function model(): BelongsTo
    {
        return $this->belongsTo(WindowModel::class, 'window_model_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(WindowImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function sizes(): HasMany
    {
        return $this->hasMany(WindowSize::class)->orderBy('width_mm')->orderBy('height_mm');
    }

    public function getSwatchUrlAttribute(): ?string
    {
        if (! $this->swatch_image) {
            return null;
        }

        return str_starts_with($this->swatch_image, 'http') ? $this->swatch_image : asset('uploads/'.ltrim($this->swatch_image, '/'));
    }
}
