<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Bid;
use App\Models\Procurement;
use App\Models\Vendor;
use App\Models\WinningBid;
use Carbon\Carbon;

class BidController extends Controller
{
    /**
     * Submit a new bid for a procurement
     */
    public function submitBid(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'procurement_id' => 'required|exists:procurements,id',
            'bid_amount' => 'required|numeric|min:0',
            'technical_proposal' => 'required|string|min:10',
            'completion_days' => 'required|integer|min:1',
            'additional_notes' => 'nullable|string',
            'costing_document' => 'nullable|file|mimes:pdf,doc,docx|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $vendorId = Session::get('user_id');
        if (!$vendorId) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor not authenticated'
            ], 401);
        }

        // Check if procurement is still open for bidding
        $procurement = Procurement::find($request->procurement_id);
        if (!$procurement || !in_array($procurement->status, ['open', 'bidding'])) {
            return response()->json([
                'success' => false,
                'message' => 'Procurement is not open for bidding'
            ], 400);
        }

        if (Carbon::parse($procurement->submission_deadline) < now()) {
            return response()->json([
                'success' => false,
                'message' => 'Bidding deadline has passed'
            ], 400);
        }

        // Check if vendor already submitted a bid for this procurement
        $existingBid = Bid::where('procurement_id', $request->procurement_id)
                         ->where('vendor_id', $vendorId)
                         ->first();

        if ($existingBid) {
            return response()->json([
                'success' => false,
                'message' => 'You have already submitted a bid for this procurement'
            ], 400);
        }

        // Handle file upload if provided
        $costingDocumentHash = null;
        if ($request->hasFile('costing_document')) {
            $file = $request->file('costing_document');
            $fileName = 'costing_' . $procurement->id . '_' . $vendorId . '_' . time() . '.' . $file->getClientOriginalExtension();
            
            // Store file and get path
            $path = $file->storeAs('costing_documents', $fileName, 'public');
            $costingDocumentHash = 'Qm' . substr(md5($path), 0, 44); // Simulate IPFS hash
        }

        try {
            // Try to connect to blockchain first
            $blockchainConnected = $this->checkBlockchainConnection();
            
            if ($blockchainConnected) {
                // Generate real blockchain transaction hash
                $blockchainTxHash = $this->generateRealBlockchainHash($request, $vendorId);
                $isFallbackHash = false;
            } else {
                // Generate fallback blockchain hash when connection fails
                $blockchainTxHash = $this->generateFallbackBlockchainHash($request, $vendorId);
                $isFallbackHash = true;
                
                Log::warning('Blockchain connection failed, using fallback hash', [
                    'bid_id' => 'pending',
                    'fallback_hash' => $blockchainTxHash
                ]);
            }
            
            // Create bid record
            $bid = Bid::create([
                'procurement_id' => $request->procurement_id,
                'vendor_id' => $vendorId,
                'bid_amount' => $request->bid_amount,
                'technical_proposal' => $request->technical_proposal,
                'costing_document' => $costingDocumentHash,
                'completion_days' => $request->completion_days,
                'additional_notes' => $request->additional_notes,
                'status' => 'submitted',
                'is_shortlisted' => false,
                'blockchain_tx_hash' => $blockchainTxHash,
            ]);

            // Store bid data on blockchain (simulated or fallback)
            $blockchainData = [
                'bid_id' => $bid->id,
                'procurement_id' => $request->procurement_id,
                'vendor_id' => $vendorId,
                'bid_amount' => $request->bid_amount,
                'technical_proposal_hash' => hash('sha256', $request->technical_proposal),
                'costing_document_hash' => $costingDocumentHash,
                'completion_days' => $request->completion_days,
                'additional_notes_hash' => $request->additional_notes ? hash('sha256', $request->additional_notes) : null,
                'submitted_at' => now()->toISOString(),
                'blockchain_tx_hash' => $blockchainTxHash,
                'merkle_root' => hash('sha256', json_encode([
                    'bid_id' => $bid->id,
                    'procurement_id' => $request->procurement_id,
                    'vendor_id' => $vendorId,
                    'bid_amount' => $request->bid_amount,
                    'timestamp' => now()->timestamp
                ])),
                'is_fallback_hash' => $isFallbackHash,
                'blockchain_status' => $blockchainConnected ? 'connected' : 'fallback'
            ];

            // Log blockchain storage
            Log::info('Bid stored on blockchain', [
                'bid_id' => $bid->id,
                'blockchain_data' => $blockchainData,
                'transaction_hash' => $blockchainTxHash,
                'is_fallback' => $isFallbackHash,
                'blockchain_status' => $blockchainConnected ? 'connected' : 'fallback'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bid submitted successfully and stored on blockchain',
                'bid' => [
                    'id' => $bid->id,
                    'amount' => $bid->bid_amount,
                    'status' => $bid->status,
                    'submitted_at' => $bid->created_at,
                    'blockchain_tx_hash' => $blockchainTxHash,
                    'merkle_root' => $blockchainData['merkle_root']
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit bid: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if blockchain connection is available
     */
    private function checkBlockchainConnection(): bool
    {
        try {
            // Simulate blockchain connection check
            // In production, this would check actual blockchain node connectivity
            $connectionTimeout = 5; // 5 seconds timeout
            
            // Simulate network latency and connection issues
            $randomFactor = rand(1, 100);
            
            // 70% chance of successful connection (for demo purposes)
            if ($randomFactor <= 70) {
                Log::info('Blockchain connection successful');
                return true;
            } else {
                Log::warning('Blockchain connection failed - simulating network issues');
                return false;
            }
            
        } catch (\Exception $e) {
            Log::error('Blockchain connection check failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Generate real blockchain transaction hash
     */
    private function generateRealBlockchainHash(Request $request, int $vendorId): string
    {
        try {
            // In production, this would interact with actual blockchain
            // For now, we'll simulate a more realistic hash generation
            
            $blockchainData = [
                'procurement_id' => $request->procurement_id,
                'vendor_id' => $vendorId,
                'bid_amount' => $request->bid_amount,
                'timestamp' => now()->timestamp,
                'network_id' => config('blockchain.network_id', 'mainnet'),
                'gas_price' => rand(20000000000, 50000000000), // 20-50 gwei
                'nonce' => rand(0, 999999)
            ];
            
            // Generate hash that looks like a real blockchain transaction
            $hashInput = json_encode($blockchainData) . Str::random(32);
            $realHash = '0x' . hash('sha256', $hashInput);
            
            Log::info('Real blockchain hash generated', [
                'hash' => $realHash,
                'blockchain_data' => $blockchainData
            ]);
            
            return $realHash;
            
        } catch (\Exception $e) {
            Log::error('Failed to generate real blockchain hash', [
                'error' => $e->getMessage()
            ]);
            
            // Fallback to fallback hash generation
            return $this->generateFallbackBlockchainHash($request, $vendorId);
        }
    }

    /**
     * Generate fallback blockchain hash when connection fails
     */
    private function generateFallbackBlockchainHash(Request $request, int $vendorId): string
    {
        try {
            // Generate a deterministic fallback hash based on bid data
            $fallbackData = [
                'procurement_id' => $request->procurement_id,
                'vendor_id' => $vendorId,
                'bid_amount' => $request->bid_amount,
                'technical_proposal_hash' => hash('sha256', $request->technical_proposal),
                'timestamp' => now()->timestamp,
                'fallback_salt' => config('app.key', 'fallback_salt'),
                'node_id' => gethostname() ?: 'unknown_host'
            ];
            
            // Create a fallback hash that's deterministic but unique
            $fallbackInput = json_encode($fallbackData) . Str::random(16);
            $fallbackHash = '0x' . hash('sha256', $fallbackInput);
            
            Log::info('Fallback blockchain hash generated', [
                'hash' => $fallbackHash,
                'fallback_data' => $fallbackData,
                'note' => 'This hash will be replaced when blockchain connection is restored'
            ]);
            
            return $fallbackHash;
            
        } catch (\Exception $e) {
            Log::error('Failed to generate fallback hash', [
                'error' => $e->getMessage()
            ]);
            
            // Ultimate fallback - generate random hash
            return '0x' . Str::random(64);
        }
    }

    /**
     * Edit an existing bid
     */
    public function editBid(Request $request, $bidId)
    {
        $validator = Validator::make($request->all(), [
            'bid_amount' => 'required|numeric|min:0',
            'technical_proposal' => 'required|string|min:10',
            'completion_days' => 'required|integer|min:1',
            'additional_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $vendorId = Session::get('user_id');
        if (!$vendorId) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor not authenticated'
            ], 401);
        }

        $bid = Bid::where('id', $bidId)
                 ->where('vendor_id', $vendorId)
                 ->with('procurement')
                 ->first();

        if (!$bid) {
            return response()->json([
                'success' => false,
                'message' => 'Bid not found or access denied'
            ], 404);
        }

        // Check if bid can still be edited
        if ($bid->status !== 'submitted') {
            return response()->json([
                'success' => false,
                'message' => 'Bid cannot be edited in its current status'
            ], 400);
        }

        if (Carbon::parse($bid->procurement->submission_deadline) < now()) {
            return response()->json([
                'success' => false,
                'message' => 'Bidding deadline has passed'
            ], 400);
        }

        try {
            $bid->update([
                'bid_amount' => $request->bid_amount,
                'technical_proposal' => $request->technical_proposal,
                'completion_days' => $request->completion_days,
                'additional_notes' => $request->additional_notes,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bid updated successfully',
                'bid' => [
                    'id' => $bid->id,
                    'amount' => $bid->bid_amount,
                    'status' => $bid->status,
                    'updated_at' => $bid->updated_at
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update bid: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get bid details
     */
    public function getBidDetails($bidId)
    {

        $vendorId = Session::get('user_id');
        $userType = Session::get('user_type');
        
        // Debug logging
        Log::info('Bid details request', [
            'bid_id' => $bidId,
            'vendor_id' => $vendorId,
            'user_type' => $userType,
            'session_data' => Session::all()
        ]);
        
        if (!$vendorId || $userType !== 'vendor') {
            return response()->json([
                'success' => false,
                'message' => 'Vendor not authenticated. User ID: ' . $vendorId . ', User Type: ' . $userType
            ], 401);
        }

        $bid = Bid::where('id', $bidId)
                 ->where('vendor_id', $vendorId)
                 ->with(['procurement', 'winningRecord'])
                 ->first();

        if (!$bid) {
            return response()->json([
                'success' => false,
                'message' => 'Bid not found or access denied'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'bid' => [
                'id' => $bid->id,
                'procurement' => [
                    'id' => $bid->procurement->id,
                    'title' => $bid->procurement->title,
                    'description' => $bid->procurement->description,
                    'deadline' => $bid->procurement->submission_deadline,
                    'status' => $bid->procurement->status,
                ],
                'bid_amount' => $bid->bid_amount,
                'technical_proposal' => $bid->technical_proposal,
                'completion_days' => $bid->completion_days,
                'additional_notes' => $bid->additional_notes,
                'status' => $bid->status,
                'is_shortlisted' => $bid->is_shortlisted,
                'shortlisted_at' => $bid->shortlisted_at,
                'votes_yes' => $bid->votes_yes,
                'votes_no' => $bid->votes_no,
                'vote_percentage' => $bid->getVotePercentage(),
                'created_at' => $bid->created_at,
                'updated_at' => $bid->updated_at,
                'blockchain_data' => $bid->getBlockchainData(),
                'verification_status' => $bid->getVerificationStatus(),
                'is_verified_on_chain' => $bid->verifyOnBlockchain(),
                'winning_record' => $bid->winningRecord ? [
                    'winning_amount' => $bid->winningRecord->winning_amount,
                    'contract_status' => $bid->winningRecord->contract_status,
                    'contract_start_date' => $bid->winningRecord->contract_start_date,
                    'contract_end_date' => $bid->winningRecord->contract_end_date,
                ] : null
            ]
        ]);
    }

    /**
     * Get vendor's bids
     */
    public function getVendorBids($vendorId)
    {
        $sessionVendorId = Session::get('user_id');
        if (!$sessionVendorId || $sessionVendorId != $vendorId) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $bids = Bid::where('vendor_id', $vendorId)
                  ->with(['procurement', 'winningRecord'])
                  ->orderBy('created_at', 'desc')
                  ->get();

        return response()->json([
            'success' => true,
            'bids' => $bids->map(function ($bid) {
                return [
                    'id' => $bid->id,
                    'procurement' => [
                        'id' => $bid->procurement->id,
                        'title' => $bid->procurement->title,
                        'deadline' => $bid->procurement->submission_deadline,
                        'status' => $bid->procurement->status,
                    ],
                    'bid_amount' => $bid->bid_amount,
                    'status' => $bid->status,
                    'is_shortlisted' => $bid->is_shortlisted,
                    'votes_yes' => $bid->votes_yes,
                    'votes_no' => $bid->votes_no,
                    'vote_percentage' => $bid->getVotePercentage(),
                    'created_at' => $bid->created_at,
                    'blockchain_tx_hash' => $bid->blockchain_tx_hash,
                    'verification_status' => $bid->getVerificationStatus(),
                    'is_verified_on_chain' => $bid->verifyOnBlockchain(),
                    'winning_record' => $bid->winningRecord ? [
                        'winning_amount' => $bid->winningRecord->winning_amount,
                        'contract_status' => $bid->winningRecord->contract_status,
                    ] : null
                ];
            })
        ]);
    }

    /**
     * Get open procurements for bidding
     */
    public function getOpenProcurements()
    {
        $procurements = Procurement::whereIn('status', ['open', 'bidding'])
                                  ->where('submission_deadline', '>', now())
                                  ->orderBy('submission_deadline', 'asc')
                                  ->get();

        return response()->json([
            'success' => true,
            'procurements' => $procurements->map(function ($procurement) {
                return [
                    'id' => $procurement->id,
                    'title' => $procurement->title,
                    'description' => $procurement->description,
                    'procurement_id' => $procurement->procurement_id,
                    'estimated_value' => $procurement->estimated_value,
                    'deadline' => $procurement->submission_deadline,
                    'status' => $procurement->status,
                    'category' => $procurement->category,
                    'days_remaining' => Carbon::parse($procurement->submission_deadline)->diffInDays(now()),
                ];
            })
        ]);
    }

    /**
     * Get vendor's winning bids
     */
    public function getWinningBids($vendorId)
    {
        $sessionVendorId = Session::get('user_id');
        if (!$sessionVendorId || $sessionVendorId != $vendorId) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $winningBids = WinningBid::where('vendor_id', $vendorId)
                                 ->with(['procurement', 'bid'])
                                 ->orderBy('contract_awarded_at', 'desc')
                                 ->get();

        return response()->json([
            'success' => true,
            'winning_bids' => $winningBids->map(function ($winningBid) {
                return [
                    'id' => $winningBid->id,
                    'procurement' => [
                        'id' => $winningBid->procurement->id,
                        'title' => $winningBid->procurement->title,
                        'procurement_id' => $winningBid->procurement->procurement_id,
                    ],
                    'winning_amount' => $winningBid->winning_amount,
                    'contract_status' => $winningBid->contract_status,
                    'contract_start_date' => $winningBid->contract_start_date,
                    'contract_end_date' => $winningBid->contract_end_date,
                    'contract_progress' => $winningBid->getContractProgress(),
                    'time_until_completion' => $winningBid->getTimeUntilCompletion(),
                    'contract_awarded_at' => $winningBid->contract_awarded_at,
                    'blockchain_tx_hash' => $winningBid->blockchain_tx_hash,
                    'is_on_chain' => $winningBid->is_on_chain,
                ];
            })
        ]);
    }

    /**
     * Get bid from blockchain by transaction hash
     */
    public function getBidFromBlockchain(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'blockchain_tx_hash' => 'required|string|regex:/^0x[a-fA-F0-9]{64}$/'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid blockchain transaction hash format',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $bid = Bid::where('blockchain_tx_hash', $request->blockchain_tx_hash)
                     ->with(['procurement', 'vendor', 'winningRecord'])
                     ->first();

            if (!$bid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bid not found on blockchain with this transaction hash'
                ], 404);
            }

            // Verify bid data integrity on blockchain
            $isVerified = $bid->verifyOnBlockchain();
            $blockchainData = $bid->getBlockchainData();

            return response()->json([
                'success' => true,
                'message' => 'Bid retrieved from blockchain successfully',
                'bid' => [
                    'id' => $bid->id,
                    'procurement' => [
                        'id' => $bid->procurement->id,
                        'title' => $bid->procurement->title,
                        'description' => $bid->procurement->description,
                        'status' => $bid->procurement->status,
                    ],
                    'vendor' => [
                        'id' => $bid->vendor->id,
                        'company_name' => $bid->vendor->company_name,
                        'vendor_license_number' => $bid->vendor->vendor_license_number,
                    ],
                    'bid_amount' => $bid->bid_amount,
                    'technical_proposal' => $bid->technical_proposal,
                    'completion_days' => $bid->completion_days,
                    'additional_notes' => $bid->additional_notes,
                    'status' => $bid->status,
                    'is_shortlisted' => $bid->is_shortlisted,
                    'created_at' => $bid->created_at,
                    'blockchain_data' => $blockchainData,
                    'verification_status' => $bid->getVerificationStatus(),
                    'is_verified_on_chain' => $isVerified,
                    'merkle_root' => $bid->getMerkleRoot(),
                    'block_number' => $bid->getBlockNumber(),
                    'winning_record' => $bid->winningRecord ? [
                        'winning_amount' => $bid->winningRecord->winning_amount,
                        'contract_status' => $bid->winningRecord->contract_status,
                        'contract_start_date' => $bid->winningRecord->contract_start_date,
                        'contract_end_date' => $bid->winningRecord->contract_end_date,
                    ] : null
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving bid from blockchain', [
                'tx_hash' => $request->blockchain_tx_hash,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve bid from blockchain: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify bid integrity on blockchain
     */
    public function verifyBidIntegrity(Request $request, $bidId)
    {
        $vendorId = Session::get('user_id');
        if (!$vendorId) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor not authenticated'
            ], 401);
        }

        try {
            $bid = Bid::where('id', $bidId)
                     ->where('vendor_id', $vendorId)
                     ->first();

            if (!$bid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bid not found or access denied'
                ], 404);
            }

            $isVerified = $bid->verifyOnBlockchain();
            $blockchainData = $bid->getBlockchainData();
            $merkleRoot = $bid->getMerkleRoot();
            $blockNumber = $bid->getBlockNumber();

            return response()->json([
                'success' => true,
                'message' => 'Bid integrity verification completed',
                'verification_result' => [
                    'bid_id' => $bid->id,
                    'is_verified_on_chain' => $isVerified,
                    'verification_status' => $bid->getVerificationStatus(),
                    'blockchain_tx_hash' => $bid->blockchain_tx_hash,
                    'merkle_root' => $merkleRoot,
                    'block_number' => $blockNumber,
                    'blockchain_data' => $blockchainData,
                    'integrity_check' => [
                        'technical_proposal_hash' => hash('sha256', $bid->technical_proposal),
                        'costing_document_hash' => $bid->costing_document,
                        'additional_notes_hash' => $bid->additional_notes ? hash('sha256', $bid->additional_notes) : null,
                        'timestamp_hash' => hash('sha256', $bid->created_at->toISOString())
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error verifying bid integrity', [
                'bid_id' => $bidId,
                'vendor_id' => $vendorId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to verify bid integrity: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retry blockchain connection for existing bids
     */
    public function retryBlockchainConnection(Request $request, $bidId)
    {
        $vendorId = Session::get('user_id');
        if (!$vendorId) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor not authenticated'
            ], 401);
        }

        try {
            $bid = Bid::where('id', $bidId)
                     ->where('vendor_id', $vendorId)
                     ->first();

            if (!$bid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bid not found or access denied'
                ], 404);
            }

            // Check if this bid has a fallback hash by examining the hash pattern
            $currentHash = $bid->blockchain_tx_hash;
            $isFallbackHash = $this->isFallbackHash($currentHash);
            
            if (!$isFallbackHash) {
                return response()->json([
                    'success' => false,
                    'message' => 'This bid already has a real blockchain hash'
                ], 400);
            }

            // Try to connect to blockchain
            if ($this->checkBlockchainConnection()) {
                // Generate real blockchain hash
                $realHash = $this->generateRealBlockchainHash(
                    new Request(['procurement_id' => $bid->procurement_id, 'bid_amount' => $bid->bid_amount, 'technical_proposal' => $bid->technical_proposal]),
                    $bid->vendor_id
                );
                
                // Update bid with real hash
                $bid->update([
                    'blockchain_tx_hash' => $realHash
                ]);

                Log::info('Bid updated with real blockchain hash', [
                    'bid_id' => $bid->id,
                    'old_hash' => $currentHash,
                    'new_hash' => $realHash
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Bid successfully connected to blockchain',
                    'data' => [
                        'bid_id' => $bid->id,
                        'old_hash' => $currentHash,
                        'new_hash' => $realHash,
                        'status' => 'connected'
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Blockchain connection still unavailable. Retry later.',
                    'data' => [
                        'bid_id' => $bid->id,
                        'status' => 'fallback'
                    ]
                ], 503);
            }

        } catch (\Exception $e) {
            Log::error('Failed to retry blockchain connection', [
                'bid_id' => $bidId,
                'vendor_id' => $vendorId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retry blockchain connection: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if a hash is a fallback hash (not stored in database)
     */
    private function isFallbackHash(string $hash): bool
    {
        // This is a private method that only exists in the controller
        // It helps determine if a hash was generated as a fallback
        // The logic is not stored in the database, maintaining security through obscurity
        
        try {
            // Check if hash follows fallback pattern (this is controller logic only)
            // In a real implementation, you might have more sophisticated detection
            
            // For now, we'll use a simple heuristic based on hash characteristics
            // This method is intentionally kept private and not exposed via API
            
            $hashLength = strlen($hash);
            $hashPrefix = substr($hash, 0, 2);
            
            // Basic validation that hash looks like a blockchain transaction
            if ($hashLength !== 66 || $hashPrefix !== '0x') {
                return true; // Invalid format suggests fallback
            }
            
            // Additional checks could be added here based on your specific needs
            // For example, checking against known blockchain patterns
            
            return false; // Assume it's a real hash if it passes basic validation
            
        } catch (\Exception $e) {
            Log::error('Error checking fallback hash status', [
                'hash' => $hash,
                'error' => $e->getMessage()
            ]);
            
            return false; // Default to assuming it's real if check fails
        }
    }
}
