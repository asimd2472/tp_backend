<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_shade_id',
        'image_path',
        'is_primary',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function shade(): BelongsTo
    {
        return $this->belongsTo(ProductShade::class, 'product_shade_id');
    }

    public function getUrlAttribute(): string
    {
        return str_starts_with($this->image_path, 'http') ? $this->image_path : asset('storage/'.$this->image_path);
    }
}
