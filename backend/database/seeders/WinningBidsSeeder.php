<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Procurement;
use App\Models\Bid;
use App\Models\WinningBid;
use App\Models\BppaOfficer;
use App\Models\Vendor;

class WinningBidsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "🏆 Creating winning bid records...\n";

        // Get completed procurements or create some
        $procurements = Procurement::whereIn('status', ['completed', 'voting'])
            ->with(['shortlistedBids' => function($query) {
                $query->orderBy('votes_yes', 'desc');
            }])
            ->get();

        if ($procurements->count() === 0) {
            echo "⚠️ No suitable procurements found. Creating sample data first...\n";
            $this->createSampleData();
            $procurements = Procurement::whereIn('status', ['completed', 'voting'])
                ->with(['shortlistedBids' => function($query) {
                    $query->orderBy('votes_yes', 'desc');
                }])
                ->get();
        }

        $winningBidsCreated = 0;

        foreach ($procurements as $procurement) {
            // Skip if already has a winning bid
            if ($procurement->winningBidRecord) {
                continue;
            }

            // Get the bid with highest YES votes
            $winningBid = $procurement->shortlistedBids->first();
            
            if (!$winningBid) {
                echo "⚠️ No shortlisted bids found for procurement: {$procurement->title}\n";
                continue;
            }

            // Get BPPA officer (creator or first available)
            $bppaOfficer = $procurement->creator ?? BppaOfficer::first();
            
            if (!$bppaOfficer) {
                echo "❌ No BPPA officer found\n";
                continue;
            }

            // Create winning bid record
            $winningBidRecord = WinningBid::create([
                'procurement_id' => $procurement->id,
                'bid_id' => $winningBid->id,
                'vendor_id' => $winningBid->vendor_id,
                'winning_amount' => $winningBid->bid_amount,
                'total_votes_received' => $winningBid->votes_yes + $winningBid->votes_no,
                'total_yes_votes' => $winningBid->votes_yes,
                'total_no_votes' => $winningBid->votes_no,
                'vote_percentage' => $winningBid->getTotalVotes() > 0 
                    ? round(($winningBid->votes_yes / $winningBid->getTotalVotes()) * 100, 2)
                    : 0,
                'voting_completed_at' => now()->subDays(rand(1, 30)),
                'contract_awarded_at' => now()->subDays(rand(0, 15)),
                'awarded_by' => $bppaOfficer->id,
                'award_justification' => $this->getAwardJustification($winningBid),
                'contract_status' => $this->getRandomContractStatus(),
                'contract_start_date' => now()->addDays(rand(30, 90)),
                'contract_end_date' => now()->addDays(rand(365, 730)),
                'final_contract_value' => $winningBid->bid_amount * (0.95 + (rand(0, 10) / 100)), // ±5% variation
                'blockchain_sync_pending' => rand(0, 1) === 1, // 50% chance of being synced
            ]);

            // Update procurement status and winning bid reference
            $procurement->update([
                'status' => 'completed',
                'winning_bid_id' => $winningBid->id,
            ]);

            // Update winning bid status
            $winningBid->update([
                'status' => 'winning'
            ]);

            echo "✅ Created winning bid for '{$procurement->title}' - Winner: {$winningBid->vendor->company_name}\n";
            $winningBidsCreated++;
        }

        echo "\n🎉 Summary:\n";
        echo "   - Total winning bids created: $winningBidsCreated\n";
        echo "   - On-chain records: " . WinningBid::onChain()->count() . "\n";
        echo "   - Off-chain records: " . WinningBid::offChain()->count() . "\n";
        echo "   - Pending blockchain sync: " . WinningBid::pendingBlockchainSync()->count() . "\n";

        // Create some additional contract status variations
        $this->updateContractStatuses();
    }

    private function createSampleData()
    {
        // This would create sample procurements if none exist
        // For now, we'll assume the VotingTestSeeder has already run
        echo "ℹ️ Sample data creation skipped - assuming VotingTestSeeder has run\n";
    }

    private function getAwardJustification($bid): string
    {
        $justifications = [
            "Selected based on highest citizen approval rating and competitive pricing.",
            "Winner demonstrated superior technical capability and strong community support.",
            "Awarded for best value proposition with {$bid->getTotalVotes()} total votes cast.",
            "Selected due to excellent technical proposal and {$bid->getVotePercentage()}% citizen approval.",
            "Winner chosen for optimal combination of cost-effectiveness and public confidence.",
            "Awarded based on transparent citizen voting process and technical merit evaluation.",
            "Selected for demonstrating best understanding of project requirements and community needs.",
            "Winner provided most comprehensive solution with strong citizen endorsement.",
        ];

        return $justifications[array_rand($justifications)];
    }

    private function getRandomContractStatus(): string
    {
        $statuses = [
            'awarded' => 40,    // 40% chance
            'signed' => 25,     // 25% chance
            'in_progress' => 20, // 20% chance
            'completed' => 10,   // 10% chance
            'terminated' => 5,   // 5% chance
        ];

        $rand = rand(1, 100);
        $cumulative = 0;

        foreach ($statuses as $status => $percentage) {
            $cumulative += $percentage;
            if ($rand <= $cumulative) {
                return $status;
            }
        }

        return 'awarded';
    }

    private function updateContractStatuses()
    {
        echo "\n🔄 Updating contract statuses...\n";

        // Set some contracts to different stages
        $completedContracts = WinningBid::where('contract_status', 'completed')
            ->update([
                'contract_end_date' => now()->subDays(rand(1, 180)),
                'final_contract_value' => \DB::raw('winning_amount * ' . (0.90 + (rand(0, 20) / 100))),
            ]);

        $inProgressContracts = WinningBid::where('contract_status', 'in_progress')
            ->update([
                'contract_start_date' => now()->subDays(rand(30, 180)),
                'contract_end_date' => now()->addDays(rand(60, 300)),
            ]);

        echo "   - Completed contracts updated: " . WinningBid::completed()->count() . "\n";
        echo "   - In-progress contracts updated: " . WinningBid::inProgress()->count() . "\n";
        echo "   - Signed contracts: " . WinningBid::where('contract_status', 'signed')->count() . "\n";
        echo "   - Awarded contracts: " . WinningBid::awarded()->count() . "\n";
    }
}
