<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bid extends Model
{
    protected $fillable = [
        'procurement_id',
        'vendor_id',
        'bid_amount',
        'technical_proposal',
        'costing_document',
        'completion_days',
        'additional_notes',
        'status',
        'is_shortlisted',
        'shortlisted_at',
        'shortlisted_by',
        'votes_yes',
        'votes_no',
    ];

    protected function casts(): array
    {
        return [
            'bid_amount' => 'decimal:2',
            'is_shortlisted' => 'boolean',
            'shortlisted_at' => 'datetime',
        ];
    }

    // Relationships
    public function procurement(): BelongsTo
    {
        return $this->belongsTo(Procurement::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function shortlistedBy(): BelongsTo
    {
        return $this->belongsTo(BppaOfficer::class, 'shortlisted_by');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    // Scopes
    public function scopeShortlisted($query)
    {
        return $query->where('is_shortlisted', true);
    }

    public function scopeWinning($query)
    {
        return $query->where('status', 'winning');
    }

    // Helper methods
    public function getTotalVotes(): int
    {
        return $this->votes_yes + $this->votes_no;
    }

    public function getVotePercentage(): float
    {
        $total = $this->getTotalVotes();
        return $total > 0 ? ($this->votes_yes / $total) * 100 : 0;
    }
}
