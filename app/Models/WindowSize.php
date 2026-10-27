<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WindowSize extends Model
{
    protected $fillable = [
        'window_shade_id',
        'width_mm',
        'height_mm',
        'sort_order',
    ];

    public function shade(): BelongsTo
    {
        return $this->belongsTo(WindowShade::class, 'window_shade_id');
    }
}
