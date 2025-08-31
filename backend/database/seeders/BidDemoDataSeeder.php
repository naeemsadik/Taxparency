<?php

namespace Database\Seeders;

use App\Models\Bid;
use App\Models\Procurement;
use App\Models\Vendor;
use App\Models\WinningBid;
use App\Models\BppaOfficer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BidDemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding Demo Bid Data...');
        
        // Create additional procurements for more variety
        $this->createAdditionalProcurements();
        
        // Create comprehensive bid data (only for new procurements)
        $this->createBidData();
        
        // Create winning bids data
        $this->createWinningBidsData();
        
        $this->command->info('Demo Bid Data seeding completed!');
    }
    
    private function createAdditionalProcurements()
    {
        $procurements = [
            [
                'title' => 'Smart City Surveillance System',
                'description' => 'Installation of CCTV cameras and monitoring systems across Dhaka city for enhanced security.',
                'procurement_id' => 'BD-2024-SEC-001',
                'estimated_value' => 150000000.00, // 15 Crore BDT
                'category' => 'Security & Technology',
                'submission_deadline' => now()->addDays(20),
                'project_start_date' => now()->addDays(40),
                'project_end_date' => now()->addDays(180), // 6 months
                'status' => 'open',
                'created_by' => 1,
            ],
            [
                'title' => 'Solar Power Plant Construction',
                'description' => 'Construction of 50MW solar power plant in Cox\'s Bazar with grid connectivity.',
                'procurement_id' => 'BD-2024-ENE-001',
                'estimated_value' => 800000000.00, // 80 Crore BDT
                'category' => 'Energy & Infrastructure',
                'submission_deadline' => now()->addDays(35),
                'project_start_date' => now()->addDays(60),
                'project_end_date' => now()->addDays(450), // 15 months
                'status' => 'open',
                'created_by' => 1,
            ],
            [
                'title' => 'Government Hospital Equipment Supply',
                'description' => 'Supply and installation of medical equipment for 10 district hospitals.',
                'procurement_id' => 'BD-2024-MED-001',
                'estimated_value' => 300000000.00, // 30 Crore BDT
                'category' => 'Healthcare',
                'submission_deadline' => now()->addDays(25),
                'project_start_date' => now()->addDays(45),
                'project_end_date' => now()->addDays(120), // 4 months
                'status' => 'open',
                'created_by' => 2,
            ],
            [
                'title' => 'Digital Education Platform Development',
                'description' => 'Development of online learning platform for government schools nationwide.',
                'procurement_id' => 'BD-2024-EDU-001',
                'estimated_value' => 200000000.00, // 20 Crore BDT
                'category' => 'Information Technology',
                'submission_deadline' => now()->addDays(15),
                'project_start_date' => now()->addDays(30),
                'project_end_date' => now()->addDays(240), // 8 months
                'status' => 'open',
                'created_by' => 2,
            ],
        ];
        
        foreach ($procurements as $procurement) {
            // Check if procurement already exists
            $existing = Procurement::where('procurement_id', $procurement['procurement_id'])->first();
            if (!$existing) {
                Procurement::create($procurement);
            }
        }
    }
    
    private function createBidData()
    {
        $vendors = Vendor::all();
        $procurements = Procurement::all();
        $bppaOfficers = BppaOfficer::all();
        
        // Only create bids for procurements that don't already have bids
        $existingBids = Bid::pluck('procurement_id')->unique();
        $newProcurements = $procurements->whereNotIn('id', $existingBids);
        
        $bidData = [];
        
        // Create bids for new procurements (IDs 3-6)
        foreach ($newProcurements as $procurement) {
            if ($procurement->id >= 3) { // Only for new procurements
                $bidData[] = [
                    'procurement_id' => $procurement->id,
                    'vendor_id' => 1,
                    'bid_amount' => $procurement->estimated_value * 0.95, // 5% below estimate
                    'technical_proposal' => 'Comprehensive technical proposal for ' . $procurement->title . '. Our approach includes modern methodologies and best practices.',
                    'costing_document' => 'Qm' . Str::random(44),
                    'completion_days' => rand(90, 365),
                    'additional_notes' => 'Includes warranty and support services.',
                    'status' => 'submitted',
                    'is_shortlisted' => false,
                ];
                
                $bidData[] = [
                    'procurement_id' => $procurement->id,
                    'vendor_id' => 2,
                    'bid_amount' => $procurement->estimated_value * 1.02, // 2% above estimate
                    'technical_proposal' => 'Advanced technical solution for ' . $procurement->title . ' with innovative features and enhanced capabilities.',
                    'costing_document' => 'Qm' . Str::random(44),
                    'completion_days' => rand(60, 300),
                    'additional_notes' => 'Includes premium features and extended support.',
                    'status' => 'submitted',
                    'is_shortlisted' => false,
                ];
            }
        }
        
        foreach ($bidData as $bid) {
            Bid::create($bid);
        }
    }
    
    private function createWinningBidsData()
    {
        // Create winning bids for the highway construction project
        $winningBids = [
            [
                'procurement_id' => 1,
                'bid_id' => 1, // ABC Construction's bid
                'vendor_id' => 1,
                'winning_amount' => 2350000000.00,
                'total_votes_received' => 1334,
                'total_yes_votes' => 1245,
                'total_no_votes' => 89,
                'vote_percentage' => 93.33,
                'voting_completed_at' => now()->subDays(2),
                'contract_awarded_at' => now()->subDays(1),
                'awarded_by' => 1,
                'award_justification' => 'Highest vote percentage and competitive pricing with comprehensive technical proposal.',
                'contract_status' => 'in_progress',
                'contract_start_date' => now()->subDays(1),
                'contract_end_date' => now()->addDays(539),
                'final_contract_value' => 2350000000.00,
                'blockchain_tx_hash' => '0x' . Str::random(64),
                'smart_contract_address' => '0x' . Str::random(40),
                'is_on_chain' => true,
                'blockchain_metadata' => [
                    'block_number' => 1854321,
                    'gas_used' => 85000,
                    'confirmation_blocks' => 12,
                    'synced_at' => now()->subDays(1)->toISOString(),
                ],
            ],
        ];
        
        foreach ($winningBids as $winningBid) {
            WinningBid::create($winningBid);
        }
        
        // Update the corresponding bids to 'winner' status
        Bid::whereIn('id', [1])->update(['status' => 'winner']);
    }
}
