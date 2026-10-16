<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOption extends Model
{
    public const PRODUCT_SLUG = 'french-door';

    protected $table = 'french_door_option';

    protected $fillable = [
        'product_slug',
        'type',
        'parent_id',
        'name',
        'width_mm',
        'height_mm',
        'color_hex',
        'image_path',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
