<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class WinningBid extends Model
{
    protected $fillable = [
        'procurement_id',
        'bid_id',
        'vendor_id',
        'winning_amount',
        'total_votes_received',
        'total_yes_votes',
        'total_no_votes',
        'vote_percentage',
        'voting_completed_at',
        'contract_awarded_at',
        'awarded_by',
        'award_justification',
        'contract_status',
        'contract_start_date',
        'contract_end_date',
        'final_contract_value',
        'blockchain_tx_hash',
        'smart_contract_address',
        'is_on_chain',
        'blockchain_metadata',
        'offchain_hash',
        'blockchain_sync_pending',
        'last_blockchain_sync_attempt',
        'blockchain_sync_error',
    ];

    protected function casts(): array
    {
        return [
            'winning_amount' => 'decimal:2',
            'vote_percentage' => 'decimal:2',
            'voting_completed_at' => 'datetime',
            'contract_awarded_at' => 'datetime',
            'contract_start_date' => 'datetime',
            'contract_end_date' => 'datetime',
            'final_contract_value' => 'decimal:2',
            'is_on_chain' => 'boolean',
            'blockchain_sync_pending' => 'boolean',
            'blockchain_metadata' => 'json',
            'last_blockchain_sync_attempt' => 'datetime',
        ];
    }

    // Relationships
    public function procurement(): BelongsTo
    {
        return $this->belongsTo(Procurement::class);
    }

    public function bid(): BelongsTo
    {
        return $this->belongsTo(Bid::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function awardedByOfficer(): BelongsTo
    {
        return $this->belongsTo(BppaOfficer::class, 'awarded_by');
    }

    // Scopes
    public function scopeAwarded($query)
    {
        return $query->where('contract_status', 'awarded');
    }

    public function scopeInProgress($query)
    {
        return $query->where('contract_status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('contract_status', 'completed');
    }

    public function scopeOnChain($query)
    {
        return $query->where('is_on_chain', true);
    }

    public function scopeOffChain($query)
    {
        return $query->where('is_on_chain', false);
    }

    public function scopePendingBlockchainSync($query)
    {
        return $query->where('blockchain_sync_pending', true);
    }

    // Helper methods
    public function generateOffchainHash(): string
    {
        $data = [
            'procurement_id' => $this->procurement_id,
            'bid_id' => $this->bid_id,
            'vendor_id' => $this->vendor_id,
            'winning_amount' => $this->winning_amount,
            'total_votes_received' => $this->total_votes_received,
            'voting_completed_at' => $this->voting_completed_at?->toISOString(),
        ];
        
        return hash('sha256', json_encode($data));
    }

    public function syncToBlockchain(): bool
    {
        try {
            // Simulate blockchain transaction
            $mockTxHash = '0x' . Str::random(64);
            $mockContractAddress = '0x' . Str::random(40);
            
            $this->update([
                'blockchain_tx_hash' => $mockTxHash,
                'smart_contract_address' => $mockContractAddress,
                'is_on_chain' => true,
                'blockchain_sync_pending' => false,
                'blockchain_metadata' => [
                    'block_number' => rand(1000000, 2000000),
                    'gas_used' => rand(50000, 100000),
                    'confirmation_blocks' => 12,
                    'synced_at' => now()->toISOString(),
                ],
            ]);
            
            return true;
        } catch (\Exception $e) {
            $this->update([
                'blockchain_sync_error' => $e->getMessage(),
                'last_blockchain_sync_attempt' => now(),
            ]);
            
            return false;
        }
    }

    public function getContractDurationDays(): ?int
    {
        if ($this->contract_start_date && $this->contract_end_date) {
            return $this->contract_start_date->diffInDays($this->contract_end_date);
        }
        return null;
    }

    public function getContractProgress(): float
    {
        if (!$this->contract_start_date || !$this->contract_end_date) {
            return 0.0;
        }
        
        $totalDays = $this->contract_start_date->diffInDays($this->contract_end_date);
        if ($totalDays <= 0) {
            return 100.0;
        }
        
        $elapsedDays = $this->contract_start_date->diffInDays(now());
        $progress = min(100.0, max(0.0, ($elapsedDays / $totalDays) * 100));
        
        return round($progress, 2);
    }

    public function isContractActive(): bool
    {
        return in_array($this->contract_status, ['signed', 'in_progress']);
    }

    public function getTimeUntilCompletion(): ?string
    {
        if (!$this->contract_end_date || $this->contract_status === 'completed') {
            return null;
        }
        
        if (now() > $this->contract_end_date) {
            return 'Overdue';
        }
        
        return $this->contract_end_date->diffForHumans();
    }

    // Boot method to handle model events
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            $model->offchain_hash = $model->generateOffchainHash();
            
            // Set blockchain sync pending if not explicitly set
            if (!isset($model->attributes['blockchain_sync_pending'])) {
                $model->blockchain_sync_pending = true;
            }
        });
        
        static::created(function ($model) {
            // Attempt to sync to blockchain after creation
            if ($model->blockchain_sync_pending) {
                $model->syncToBlockchain();
            }
        });
    }
}
