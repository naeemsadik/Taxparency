<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vote extends Model
{
    protected $fillable = [
        'citizen_id',
        'bid_id',
        'vote',
        'blockchain_tx_hash',
    ];

    protected function casts(): array
    {
        return [
            'vote' => 'boolean',
        ];
    }

    // Relationships
    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    public function bid(): BelongsTo
    {
        return $this->belongsTo(Bid::class);
    }

    // Scopes
    public function scopeYes($query)
    {
        return $query->where('vote', true);
    }

    public function scopeNo($query)
    {
        return $query->where('vote', false);
    }
}
