<?php

namespace Database\Seeders;

use App\Models\Citizen;
use App\Models\NbrOfficer;
use App\Models\Vendor;
use App\Models\BppaOfficer;
use App\Models\Procurement;
use App\Models\Bid;
use App\Models\TaxReturn;
use App\Models\Vote;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('Seeding Citizens...');
        $this->seedCitizens();
        
        $this->command->info('Seeding NBR Officers...');
        $this->seedNbrOfficers();
        
        $this->command->info('Seeding BPPA Officers...');
        $this->seedBppaOfficers();
        
        $this->command->info('Seeding Vendors...');
        $this->seedVendors();
        
        $this->command->info('Seeding Tax Returns...');
        $this->seedTaxReturns();
        
        $this->command->info('Seeding Procurements...');
        $this->seedProcurements();
        
        $this->command->info('Seeding Bids...');
        $this->seedBids();
        
        $this->command->info('Seeding Votes...');
        $this->seedVotes();
        
        $this->command->info('Seeding Demo Bid Data...');
        $this->call(BidDemoDataSeeder::class);
        
        $this->command->info('Database seeding completed!');
    }
    
    private function seedCitizens()
    {
        $citizens = [
            [
                'tiin' => '123456789',
                'full_name' => 'John Doe',
                'password' => Hash::make('password123'),
            ],
            [
                'tiin' => '987654321',
                'full_name' => 'Jane Smith',
                'password' => Hash::make('password123'),
            ],
            [
                'tiin' => '456789123',
                'full_name' => 'Ahmed Rahman',
                'password' => Hash::make('password123'),
            ],
            [
                'tiin' => '789123456',
                'full_name' => 'Fatima Khan',
                'password' => Hash::make('password123'),
            ],
            [
                'tiin' => '321654987',
                'full_name' => 'Mohammad Ali',
                'password' => Hash::make('password123'),
            ],
        ];
        
        foreach ($citizens as $citizen) {
            Citizen::create($citizen);
        }
    }
    
    private function seedNbrOfficers()
    {
        $officers = [
            [
                'username' => 'nbr.officer1',
                'full_name' => 'Dr. Abdul Karim',
                'password' => Hash::make('nbr123'),
                'officer_id' => 'NBR001',
                'department' => 'Income Tax Division',
            ],
            [
                'username' => 'nbr.officer2',
                'full_name' => 'Ms. Rashida Begum',
                'password' => Hash::make('nbr123'),
                'officer_id' => 'NBR002',
                'department' => 'VAT Division',
            ],
        ];
        
        foreach ($officers as $officer) {
            NbrOfficer::create($officer);
        }
    }
    
    private function seedBppaOfficers()
    {
        $officers = [
            [
                'username' => 'bppa.officer1',
                'full_name' => 'Mr. Rafiqul Islam',
                'password' => Hash::make('bppa123'),
                'officer_id' => 'BPPA001',
                'department' => 'Procurement Management',
                'designation' => 'Senior Procurement Officer',
            ],
            [
                'username' => 'bppa.officer2',
                'full_name' => 'Ms. Salma Khatun',
                'password' => Hash::make('bppa123'),
                'officer_id' => 'BPPA002',
                'department' => 'Vendor Management',
                'designation' => 'Vendor Relations Officer',
            ],
        ];
        
        foreach ($officers as $officer) {
            BppaOfficer::create($officer);
        }
    }
    
    private function seedVendors()
    {
        $vendors = [
            [
                'username' => 'abc.construction',
                'company_name' => 'ABC Construction Ltd.',
                'password' => Hash::make('vendor123'),
                'vendor_license_number' => 'VL001',
                'contact_person' => 'Mr. Kamal Hossain',
                'contact_email' => 'kamal@abc-construction.com',
                'contact_phone' => '+8801712345678',
                'company_address' => '123 Main Street, Dhaka 1000',
                'is_approved' => true,
                'approved_at' => now(),
                'approved_by' => 1,
            ],
            [
                'username' => 'xyz.infrastructure',
                'company_name' => 'XYZ Infrastructure Pvt.',
                'password' => Hash::make('vendor123'),
                'vendor_license_number' => 'VL002',
                'contact_person' => 'Ms. Nusrat Jahan',
                'contact_email' => 'nusrat@xyz-infra.com',
                'contact_phone' => '+8801887654321',
                'company_address' => '456 Commercial Area, Chittagong 4000',
                'is_approved' => true,
                'approved_at' => now(),
                'approved_by' => 1,
            ],
            [
                'username' => 'national.builders',
                'company_name' => 'National Builders Corp.',
                'password' => Hash::make('vendor123'),
                'vendor_license_number' => 'VL003',
                'contact_person' => 'Mr. Shahidul Islam',
                'contact_email' => 'shahid@national-builders.com',
                'contact_phone' => '+8801923456789',
                'company_address' => '789 Industrial Zone, Sylhet 3100',
                'is_approved' => true,
                'approved_at' => now(),
                'approved_by' => 2,
            ],
        ];
        
        foreach ($vendors as $vendor) {
            Vendor::create($vendor);
        }
    }
    
    private function seedTaxReturns()
    {
        $taxReturns = [
            [
                'citizen_id' => 1,
                'fiscal_year' => '2023-24',
                'ipfs_hash' => 'QmX7KkWvCmKJkKqKjKKjKKjKKjKKjKKjKKjKKjKKjKKj',
                'total_income' => 850000.00,
                'total_cost' => 127500.00,
                'blockchain_tx_hash' => '0x1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef',
                'status' => 'approved',
                'reviewed_by' => 1,
                'reviewed_at' => now()->subDays(30),
                'review_comments' => 'All documents are in order. Tax calculation is correct.',
            ],
            [
                'citizen_id' => 1,
                'fiscal_year' => '2022-23',
                'ipfs_hash' => 'QmY8LlXwDnKLlLrLkLKlKKlKKlKKlKKlKKlKKlKKlKKl',
                'total_income' => 720000.00,
                'total_cost' => 108000.00,
                'blockchain_tx_hash' => '0xabcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890',
                'status' => 'approved',
                'reviewed_by' => 2,
                'reviewed_at' => now()->subDays(365),
                'review_comments' => 'Approved after verification of supporting documents.',
            ],
            [
                'citizen_id' => 2,
                'fiscal_year' => '2023-24',
                'ipfs_hash' => 'QmZ9MmYxEoKMmMsNmNMmNNmNNmNNmNNmNNmNNmNNmNNm',
                'total_income' => 650000.00,
                'total_cost' => 97500.00,
                'blockchain_tx_hash' => '0xdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890abc',
                'status' => 'pending',
            ],
        ];
        
        foreach ($taxReturns as $taxReturn) {
            TaxReturn::create($taxReturn);
        }
    }
    
    private function seedProcurements()
    {
        $procurements = [
            [
                'title' => 'Highway Construction Project - Phase 2',
                'description' => 'Construction of 50km highway connecting Dhaka to Chittagong with modern infrastructure and safety features.',
                'procurement_id' => 'BD-2024-HW-002',
                'estimated_value' => 2500000000.00, // 250 Crore BDT
                'category' => 'Infrastructure',
                'submission_deadline' => now()->addDays(30),
                'project_start_date' => now()->addDays(60),
                'project_end_date' => now()->addDays(730), // 2 years
                'status' => 'voting',
                'created_by' => 1,
                'blockchain_tx_hash' => '0x' . Str::random(64),
                'voting_ends_at' => now()->addDays(7),
            ],
            [
                'title' => 'Digital Bangladesh IT Infrastructure',
                'description' => 'Setting up IT infrastructure for government offices across 10 districts.',
                'procurement_id' => 'BD-2024-IT-001',
                'estimated_value' => 500000000.00, // 50 Crore BDT
                'category' => 'Information Technology',
                'submission_deadline' => now()->addDays(45),
                'project_start_date' => now()->addDays(75),
                'project_end_date' => now()->addDays(365), // 1 year
                'status' => 'open',
                'created_by' => 1,
            ],
        ];
        
        foreach ($procurements as $procurement) {
            Procurement::create($procurement);
        }
    }
    
    private function seedBids()
    {
        $bids = [
            [
                'procurement_id' => 1,
                'vendor_id' => 1,
                'bid_amount' => 2350000000.00,
                'technical_proposal' => 'Complete highway construction with modern materials and equipment.',
                'costing_document' => 'Qm' . Str::random(44),
                'completion_days' => 540,
                'additional_notes' => 'Includes 2-year maintenance warranty.',
                'status' => 'shortlisted',
                'is_shortlisted' => true,
                'shortlisted_at' => now()->subDays(5),
                'shortlisted_by' => 1,
                'votes_yes' => 1245,
                'votes_no' => 89,
            ],
            [
                'procurement_id' => 1,
                'vendor_id' => 2,
                'bid_amount' => 2420000000.00,
                'technical_proposal' => 'Highway construction with advanced safety systems.',
                'costing_document' => 'Qm' . Str::random(44),
                'completion_days' => 480,
                'additional_notes' => 'Uses eco-friendly materials.',
                'status' => 'shortlisted',
                'is_shortlisted' => true,
                'shortlisted_at' => now()->subDays(5),
                'shortlisted_by' => 1,
                'votes_yes' => 987,
                'votes_no' => 234,
            ],
            [
                'procurement_id' => 1,
                'vendor_id' => 3,
                'bid_amount' => 2480000000.00,
                'technical_proposal' => 'Premium highway construction with extended features.',
                'costing_document' => 'Qm' . Str::random(44),
                'completion_days' => 600,
                'additional_notes' => 'Includes smart traffic management system.',
                'status' => 'shortlisted',
                'is_shortlisted' => true,
                'shortlisted_at' => now()->subDays(5),
                'shortlisted_by' => 1,
                'votes_yes' => 756,
                'votes_no' => 445,
            ],
        ];
        
        foreach ($bids as $bid) {
            Bid::create($bid);
        }
    }
    
    private function seedVotes()
    {
        // Create sample votes for the first procurement
        $citizens = Citizen::all();
        $bids = Bid::where('procurement_id', 1)->get();
        
        foreach ($citizens as $citizen) {
            foreach ($bids as $bid) {
                // Simulate random voting (80% yes, 20% no)
                // $vote = rand(1, 10) <= 8;
                
                // Vote::create([
                //     'citizen_id' => $citizen->id,
                //     'bid_id' => $bid->id,
                //     'vote' => $vote,
                //     'blockchain_tx_hash' => '0x' . Str::random(64),
                // ]);
            }
        }
    }
}
