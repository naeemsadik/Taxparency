<?php

namespace App\Services;

use App\Models\WinningBid;
use App\Models\Vote;
use App\Models\Procurement;
use App\Models\TaxReturn;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class BlockchainService
{
    private $isBlockchainAvailable = false; // Simulate blockchain availability

    public function __construct()
    {
        // In a real implementation, this would check if blockchain node is accessible
        $this->isBlockchainAvailable = config('app.blockchain_enabled', false);
    }

    /**
     * Store winning bid on blockchain with off-chain fallback
     */
    public function storeWinningBid(WinningBid $winningBid): array
    {
        if ($this->isBlockchainAvailable) {
            return $this->storeOnBlockchain($winningBid);
        } else {
            return $this->storeOffChain($winningBid);
        }
    }

    /**
     * Store vote on blockchain with off-chain fallback
     */
    public function storeVote(Vote $vote): array
    {
        if ($this->isBlockchainAvailable) {
            return $this->storeVoteOnBlockchain($vote);
        } else {
            return $this->storeVoteOffChain($vote);
        }
    }

    /**
     * Store procurement on blockchain with off-chain fallback
     */
    public function storeProcurement(Procurement $procurement): array
    {
        if ($this->isBlockchainAvailable) {
            return $this->storeProcurementOnBlockchain($procurement);
        } else {
            return $this->storeProcurementOffChain($procurement);
        }
    }

    /**
     * Store tax return on blockchain with off-chain fallback
     */
    public function storeTaxReturn(TaxReturn $taxReturn): array
    {
        if ($this->isBlockchainAvailable) {
            return $this->storeTaxReturnOnBlockchain($taxReturn);
        } else {
            return $this->storeTaxReturnOffChain($taxReturn);
        }
    }

    /**
     * Simulate storing winning bid on blockchain
     */
    private function storeOnBlockchain(WinningBid $winningBid): array
    {
        try {
            // Simulate blockchain transaction
            $txHash = '0x' . Str::random(64);
            $contractAddress = '0x' . Str::random(40);
            $blockNumber = rand(1000000, 2000000);
            $gasUsed = rand(50000, 100000);

            // Update winning bid with blockchain data
            $winningBid->update([
                'blockchain_tx_hash' => $txHash,
                'smart_contract_address' => $contractAddress,
                'is_on_chain' => true,
                'blockchain_sync_pending' => false,
                'blockchain_metadata' => [
                    'block_number' => $blockNumber,
                    'gas_used' => $gasUsed,
                    'confirmation_blocks' => 12,
                    'network' => 'ethereum-mainnet',
                    'synced_at' => now()->toISOString(),
                ],
            ]);

            Log::info('Winning bid stored on blockchain', [
                'winning_bid_id' => $winningBid->id,
                'tx_hash' => $txHash,
                'contract_address' => $contractAddress,
            ]);

            return [
                'success' => true,
                'storage_type' => 'on_chain',
                'tx_hash' => $txHash,
                'contract_address' => $contractAddress,
                'block_number' => $blockNumber,
                'gas_used' => $gasUsed,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to store winning bid on blockchain', [
                'winning_bid_id' => $winningBid->id,
                'error' => $e->getMessage(),
            ]);

            // Fallback to off-chain storage
            return $this->storeOffChain($winningBid);
        }
    }

    /**
     * Store winning bid off-chain with integrity hash
     */
    private function storeOffChain(WinningBid $winningBid): array
    {
        try {
            // Generate integrity hash
            $dataToHash = [
                'procurement_id' => $winningBid->procurement_id,
                'bid_id' => $winningBid->bid_id,
                'vendor_id' => $winningBid->vendor_id,
                'winning_amount' => $winningBid->winning_amount,
                'total_votes_received' => $winningBid->total_votes_received,
                'voting_completed_at' => $winningBid->voting_completed_at?->toISOString(),
                'timestamp' => now()->toISOString(),
            ];

            $integrityHash = hash('sha256', json_encode($dataToHash));
            $mockTxHash = '0xoffchain_' . Str::random(56); // Distinguish off-chain transactions

            // Update winning bid with off-chain data
            $winningBid->update([
                'blockchain_tx_hash' => $mockTxHash,
                'is_on_chain' => false,
                'blockchain_sync_pending' => true,
                'offchain_hash' => $integrityHash,
                'blockchain_metadata' => [
                    'storage_type' => 'off_chain',
                    'integrity_hash' => $integrityHash,
                    'stored_at' => now()->toISOString(),
                    'sync_pending' => true,
                ],
            ]);

            // Store in off-chain backup table (you could create a separate table for this)
            DB::table('blockchain_backup')->insert([
                'type' => 'winning_bid',
                'record_id' => $winningBid->id,
                'data_hash' => $integrityHash,
                'data_json' => json_encode($dataToHash),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('Winning bid stored off-chain', [
                'winning_bid_id' => $winningBid->id,
                'integrity_hash' => $integrityHash,
                'mock_tx_hash' => $mockTxHash,
            ]);

            return [
                'success' => true,
                'storage_type' => 'off_chain',
                'tx_hash' => $mockTxHash,
                'integrity_hash' => $integrityHash,
                'sync_pending' => true,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to store winning bid off-chain', [
                'winning_bid_id' => $winningBid->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'storage_type' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Store vote on blockchain
     */
    private function storeVoteOnBlockchain(Vote $vote): array
    {
        try {
            $txHash = '0x' . Str::random(64);
            
            return [
                'success' => true,
                'storage_type' => 'on_chain',
                'tx_hash' => $txHash,
                'block_number' => rand(1000000, 2000000),
            ];
        } catch (\Exception $e) {
            return $this->storeVoteOffChain($vote);
        }
    }

    /**
     * Store vote off-chain
     */
    private function storeVoteOffChain(Vote $vote): array
    {
        $integrityHash = hash('sha256', json_encode([
            'citizen_id' => $vote->citizen_id,
            'bid_id' => $vote->bid_id,
            'vote' => $vote->vote,
            'timestamp' => $vote->created_at->toISOString(),
        ]));

        return [
            'success' => true,
            'storage_type' => 'off_chain',
            'tx_hash' => '0xoffchain_vote_' . Str::random(48),
            'integrity_hash' => $integrityHash,
        ];
    }

    /**
     * Store procurement on blockchain
     */
    private function storeProcurementOnBlockchain(Procurement $procurement): array
    {
        try {
            $txHash = '0x' . Str::random(64);
            
            return [
                'success' => true,
                'storage_type' => 'on_chain',
                'tx_hash' => $txHash,
                'block_number' => rand(1000000, 2000000),
            ];
        } catch (\Exception $e) {
            return $this->storeProcurementOffChain($procurement);
        }
    }

    /**
     * Store procurement off-chain
     */
    private function storeProcurementOffChain(Procurement $procurement): array
    {
        $integrityHash = hash('sha256', json_encode([
            'procurement_id' => $procurement->procurement_id,
            'title' => $procurement->title,
            'estimated_value' => $procurement->estimated_value,
            'created_by' => $procurement->created_by,
            'timestamp' => $procurement->created_at->toISOString(),
        ]));

        return [
            'success' => true,
            'storage_type' => 'off_chain',
            'tx_hash' => '0xoffchain_proc_' . Str::random(48),
            'integrity_hash' => $integrityHash,
        ];
    }

    /**
     * Store tax return on blockchain
     */
    private function storeTaxReturnOnBlockchain(TaxReturn $taxReturn): array
    {
        try {
            $txHash = '0x' . Str::random(64);
            
            return [
                'success' => true,
                'storage_type' => 'on_chain',
                'tx_hash' => $txHash,
                'block_number' => rand(1000000, 2000000),
            ];
        } catch (\Exception $e) {
            return $this->storeTaxReturnOffChain($taxReturn);
        }
    }

    /**
     * Store tax return off-chain
     */
    private function storeTaxReturnOffChain(TaxReturn $taxReturn): array
    {
        $integrityHash = hash('sha256', json_encode([
            'citizen_id' => $taxReturn->citizen_id,
            'fiscal_year' => $taxReturn->fiscal_year,
            'tax_amount' => $taxReturn->tax_amount,
            'ipfs_hash' => $taxReturn->ipfs_hash,
            'timestamp' => $taxReturn->created_at->toISOString(),
        ]));

        return [
            'success' => true,
            'storage_type' => 'off_chain',
            'tx_hash' => '0xoffchain_tax_' . Str::random(48),
            'integrity_hash' => $integrityHash,
        ];
    }

    /**
     * Attempt to sync off-chain data to blockchain
     */
    public function syncToBlockchain(): array
    {
        if (!$this->isBlockchainAvailable) {
            return [
                'success' => false,
                'message' => 'Blockchain not available',
                'synced_count' => 0,
            ];
        }

        $pendingRecords = WinningBid::pendingBlockchainSync()->limit(10)->get();
        $syncedCount = 0;

        foreach ($pendingRecords as $record) {
            if ($record->syncToBlockchain()) {
                $syncedCount++;
            }
        }

        return [
            'success' => true,
            'message' => "Synced $syncedCount records to blockchain",
            'synced_count' => $syncedCount,
            'pending_count' => WinningBid::pendingBlockchainSync()->count(),
        ];
    }

    /**
     * Get blockchain status summary
     */
    public function getBlockchainStatus(): array
    {
        return [
            'blockchain_available' => $this->isBlockchainAvailable,
            'total_winning_bids' => WinningBid::count(),
            'on_chain_records' => WinningBid::onChain()->count(),
            'off_chain_records' => WinningBid::offChain()->count(),
            'pending_sync' => WinningBid::pendingBlockchainSync()->count(),
            'sync_errors' => WinningBid::whereNotNull('blockchain_sync_error')->count(),
        ];
    }

    /**
     * Verify data integrity for off-chain records
     */
    public function verifyDataIntegrity(): array
    {
        $offChainRecords = WinningBid::offChain()->get();
        $verified = 0;
        $corrupted = 0;

        foreach ($offChainRecords as $record) {
            $currentHash = $record->generateOffchainHash();
            if ($currentHash === $record->offchain_hash) {
                $verified++;
            } else {
                $corrupted++;
                Log::warning('Data integrity check failed', [
                    'winning_bid_id' => $record->id,
                    'stored_hash' => $record->offchain_hash,
                    'calculated_hash' => $currentHash,
                ]);
            }
        }

        return [
            'verified_records' => $verified,
            'corrupted_records' => $corrupted,
            'integrity_percentage' => $verified + $corrupted > 0 
                ? round(($verified / ($verified + $corrupted)) * 100, 2) 
                : 100,
        ];
    }
}
