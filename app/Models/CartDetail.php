<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartDetail extends Model
{
    protected $fillable = [
        'cart_details',
        'construction_stage',
        'pincode',
        'address',
    ];

    protected function casts(): array
    {
        return [
            'cart_details' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
