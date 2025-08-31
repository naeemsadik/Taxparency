<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\BppaOfficer;
use App\Models\Vendor;
use App\Models\Procurement;
use App\Models\Bid;

class VotingTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create BPPA Officer if not exists
        $bppaOfficer = BppaOfficer::firstOrCreate([
            'username' => 'bppa.officer1'
        ], [
            'name' => 'BPPA Test Officer',
            'email' => 'bppa.officer1@bppa.gov.bd',
            'password' => Hash::make('bppa123'),
            'officer_id' => 'BPPA-001',
            'full_name' => 'John Smith - BPPA Officer'
        ]);

        // Create Vendors if not exist
        $vendors = [
            [
                'username' => 'abc.construction',
                'company_name' => 'ABC Construction Ltd.',
                'email' => 'info@abc-construction.com',
                'phone' => '01700000001',
                'address' => 'Dhaka, Bangladesh',
                'password' => Hash::make('vendor123'),
                'status' => 'approved',
                'vendor_license_number' => 'VL-2024-001',
                'contact_person' => 'Ahmed Rahman',
                'contact_email' => 'ahmed@abc-construction.com',
                'contact_phone' => '01700000001',
                'company_address' => '123 Construction Street, Dhaka'
            ],
            [
                'username' => 'xyz.infrastructure',
                'company_name' => 'XYZ Infrastructure Pvt.',
                'email' => 'info@xyz-infra.com',
                'phone' => '01700000002',
                'address' => 'Chittagong, Bangladesh',
                'password' => Hash::make('vendor123'),
                'status' => 'approved',
                'vendor_license_number' => 'VL-2024-002',
                'contact_person' => 'Maria Khan',
                'contact_email' => 'maria@xyz-infra.com',
                'contact_phone' => '01700000002',
                'company_address' => '456 Infrastructure Ave, Chittagong'
            ],
            [
                'username' => 'national.builders',
                'company_name' => 'National Builders Corp.',
                'email' => 'info@national-builders.com',
                'phone' => '01700000003',
                'address' => 'Sylhet, Bangladesh',
                'password' => Hash::make('vendor123'),
                'status' => 'approved',
                'vendor_license_number' => 'VL-2024-003',
                'contact_person' => 'Rashid Islam',
                'contact_email' => 'rashid@national-builders.com',
                'contact_phone' => '01700000003',
                'company_address' => '789 Builder Road, Sylhet'
            ]
        ];

        $createdVendors = [];
        foreach ($vendors as $vendorData) {
            $vendor = Vendor::firstOrCreate([
                'username' => $vendorData['username']
            ], $vendorData);
            $createdVendors[] = $vendor;
        }

        // Create a test procurement in voting status
        $procurement = Procurement::firstOrCreate([
            'procurement_id' => 'PROC-2024-TEST-001'
        ], [
            'title' => 'Test Road Construction Project',
            'description' => 'Construction of a 5km road connecting rural villages. This is a test procurement for demonstrating the citizen voting functionality in the Taxparency system.',
            'estimated_value' => 50000000.00, // 5 crore BDT
            'category' => 'Infrastructure',
            'submission_deadline' => now()->subDays(10), // Past deadline
            'project_start_date' => now()->addDays(30),
            'project_end_date' => now()->addDays(365),
            'status' => 'voting',
            'created_by' => $bppaOfficer->id,
            'voting_ends_at' => now()->addDays(7), // Voting ends in 7 days
            'blockchain_tx_hash' => '0x' . str_repeat('a', 64)
        ]);

        // Create bids for the procurement and shortlist them
        $bidData = [
            [
                'vendor_id' => $createdVendors[0]->id,
                'bid_amount' => 45000000.00,
                'technical_proposal' => 'We propose to use high-quality materials and modern construction techniques. Our team has 15+ years of experience in road construction. We will complete the project using concrete pavement with proper drainage systems.',
                'completion_days' => 300,
                'additional_notes' => 'We offer 2 years warranty on all construction work.',
                'votes_yes' => 5,
                'votes_no' => 1
            ],
            [
                'vendor_id' => $createdVendors[1]->id,
                'bid_amount' => 48000000.00,
                'technical_proposal' => 'Our approach focuses on sustainability and environmental protection. We will use eco-friendly materials and ensure minimal impact on local wildlife. Advanced machinery will ensure faster completion.',
                'completion_days' => 280,
                'additional_notes' => 'We include tree plantation along the roadside at no extra cost.',
                'votes_yes' => 3,
                'votes_no' => 2
            ],
            [
                'vendor_id' => $createdVendors[2]->id,
                'bid_amount' => 47500000.00,
                'technical_proposal' => 'We specialize in rural road construction and understand the unique challenges. Our proposal includes community engagement programs and local employment generation during construction.',
                'completion_days' => 320,
                'additional_notes' => 'We guarantee to hire 80% of workers from the local community.',
                'votes_yes' => 7,
                'votes_no' => 0
            ]
        ];

        foreach ($bidData as $index => $data) {
            $bid = Bid::firstOrCreate([
                'procurement_id' => $procurement->id,
                'vendor_id' => $data['vendor_id']
            ], [
                'bid_amount' => $data['bid_amount'],
                'technical_proposal' => $data['technical_proposal'],
                'costing_document' => 'Qm' . str_repeat(chr(65 + $index), 44), // Mock IPFS hash
                'completion_days' => $data['completion_days'],
                'additional_notes' => $data['additional_notes'],
                'status' => 'shortlisted',
                'is_shortlisted' => true,
                'shortlisted_at' => now()->subDays(1),
                'shortlisted_by' => $bppaOfficer->id,
                'votes_yes' => $data['votes_yes'],
                'votes_no' => $data['votes_no']
            ]);
        }

        echo "✅ Test data created successfully!\n";
        echo "📋 Procurement: {$procurement->title}\n";
        echo "🏢 Vendors: " . count($createdVendors) . " vendors created\n";
        echo "📝 Bids: " . count($bidData) . " shortlisted bids created\n";
        echo "🗳️ Voting ends at: {$procurement->voting_ends_at}\n";
        echo "\n";
        echo "🔑 Login credentials for testing:\n";
        echo "- Citizen: Any TIIN (e.g., 'citizen123'), any password\n";
        echo "- BPPA Officer: bppa.officer1 / bppa123\n";
        echo "- Vendors: abc.construction / vendor123\n";
    }
}
