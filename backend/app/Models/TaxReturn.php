<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxReturn extends Model
{
    protected $fillable = [
        'citizen_id',
        'fiscal_year',
        'ipfs_hash',
        'total_income',
        'total_cost',
        'blockchain_tx_hash',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_comments',
    ];

    protected function casts(): array
    {
        return [
            'total_income' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'reviewed_at' => 'datetime',
        ];
    }

    // Relationships
    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(NbrOfficer::class, 'reviewed_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeDeclined($query)
    {
        return $query->where('status', 'declined');
    }
}
