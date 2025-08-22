<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Procurement extends Model
{
    protected $fillable = [
        'title',
        'description',
        'procurement_id',
        'estimated_value',
        'category',
        'submission_deadline',
        'project_start_date',
        'project_end_date',
        'status',
        'created_by',
        'requirements_document',
        'blockchain_tx_hash',
        'voting_ends_at',
        'winning_bid_id',
    ];

    protected function casts(): array
    {
        return [
            'estimated_value' => 'decimal:2',
            'submission_deadline' => 'date',
            'project_start_date' => 'date',
            'project_end_date' => 'date',
            'voting_ends_at' => 'datetime',
        ];
    }

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(BppaOfficer::class, 'created_by');
    }

    public function bids(): HasMany
    {
        return $this->hasMany(Bid::class);
    }

    public function shortlistedBids(): HasMany
    {
        return $this->hasMany(Bid::class)->where('is_shortlisted', true);
    }

    public function winningBid(): BelongsTo
    {
        return $this->belongsTo(Bid::class, 'winning_bid_id');
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeVoting($query)
    {
        return $query->where('status', 'voting');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
