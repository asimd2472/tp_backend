<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WindowImage extends Model
{
    protected $fillable = [
        'window_shade_id',
        'image_path',
        'is_primary',
        'sort_order',
    ];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function shade(): BelongsTo
    {
        return $this->belongsTo(WindowShade::class, 'window_shade_id');
    }

    public function getUrlAttribute(): string
    {
        return str_starts_with($this->image_path, 'http') ? $this->image_path : asset('uploads/'.ltrim($this->image_path, '/'));
    }
}
