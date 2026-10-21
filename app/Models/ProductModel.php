<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductModel extends Model
{
    use HasFactory;

    protected $table = 'product_models';

    protected $fillable = [
        'product_id',
        'name',
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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function designs(): HasMany
    {
        return $this->hasMany(ProductDesign::class, 'product_model_id')->orderBy('sort_order')->orderBy('id');
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if ($this->main_image) {
            return str_starts_with($this->main_image, 'http') ? $this->main_image : asset('uploads/'.$this->main_image);
        }

        return asset('uploads/default/model.webp');
    }

    public function getMainImageUrlAttribute(): ?string
    {
        return $this->thumbnail_url;
    }
}
