<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductDesign extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'product_model_id',
        'name',
        'code',
        'thumbnail_image',
        'sort_order',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_model_id');
    }

    public function shades(): HasMany
    {
        return $this->hasMany(ProductShade::class)->orderBy('sort_order')->orderBy('id');
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if (! $this->thumbnail_image) {
            return null;
        }

        return str_starts_with($this->thumbnail_image, 'http') ? $this->thumbnail_image : asset('storage/'.$this->thumbnail_image);
    }
}
