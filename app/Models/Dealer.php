<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Dealer extends Model
{
    protected $fillable = [
        'state',
        'district',
        'dealer_name',
        'dealer_address',
        'contact_person',
        'mobile_number',
        'email_id',
        'image',
    ];

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        $columns = [
            'state' => 'state',
            'district' => 'district',
            'dealer_name' => 'dealer_name',
            'dealer_address' => 'dealer_address',
            'contact_person' => 'contact_person',
            'mobile_number' => 'mobile_number',
            'email_id' => 'email_id',
        ];

        foreach ($columns as $filter => $column) {
            if (isset($filters[$filter]) && trim((string) $filters[$filter]) !== '') {
                $query->where($column, 'like', '%'.trim((string) $filters[$filter]).'%');
            }
        }

        return $query;
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }
}
