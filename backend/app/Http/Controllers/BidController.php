<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
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
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bid submitted successfully',
                'bid' => [
                    'id' => $bid->id,
                    'amount' => $bid->bid_amount,
                    'status' => $bid->status,
                    'submitted_at' => $bid->created_at
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
}
