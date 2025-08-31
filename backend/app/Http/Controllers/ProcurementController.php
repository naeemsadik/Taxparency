<?php

namespace App\Http\Controllers;

use App\Models\Procurement;
use App\Models\Bid;
use App\Models\Vote;
use App\Models\Citizen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class ProcurementController extends Controller
{
    /**
     * Create a new procurement (BPPA Officer)
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'procurement_id' => 'required|string|unique:procurements,procurement_id',
            'estimated_value' => 'required|numeric|min:0',
            'category' => 'required|string|max:100',
            'submission_deadline' => 'required|date|after:today',
            'project_start_date' => 'nullable|date',
            'project_end_date' => 'nullable|date|after:project_start_date',
            'created_by' => 'required|exists:bppa_officers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $procurement = Procurement::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Procurement created successfully',
                'data' => $procurement->load('creator:id,full_name,officer_id')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create procurement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit a bid for a procurement (Vendor)
     */
    public function submitBid(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'procurement_id' => 'required|exists:procurements,id',
            'vendor_id' => 'required|exists:vendors,id',
            'bid_amount' => 'required|numeric|min:0',
            'technical_proposal' => 'required|string',
            'completion_days' => 'required|integer|min:1',
            'additional_notes' => 'nullable|string',
            'costing_document' => 'required|file|mimes:pdf|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if vendor already submitted a bid
        $existingBid = Bid::where('procurement_id', $request->procurement_id)
                         ->where('vendor_id', $request->vendor_id)
                         ->first();

        if ($existingBid) {
            return response()->json([
                'success' => false,
                'message' => 'Bid already submitted for this procurement'
            ], 409);
        }

        try {
            // Store PDF file and simulate IPFS upload
            $file = $request->file('costing_document');
            $fileName = 'bids/' . Str::uuid() . '.pdf';
            $filePath = $file->storeAs('public', $fileName);
            $mockIpfsHash = 'Qm' . Str::random(44);

            $bid = Bid::create([
                'procurement_id' => $request->procurement_id,
                'vendor_id' => $request->vendor_id,
                'bid_amount' => $request->bid_amount,
                'technical_proposal' => $request->technical_proposal,
                'costing_document' => $mockIpfsHash,
                'completion_days' => $request->completion_days,
                'additional_notes' => $request->additional_notes,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bid submitted successfully',
                'data' => [
                    'bid' => $bid->load(['procurement:id,title,procurement_id', 'vendor:id,company_name']),
                    'ipfs_hash' => $mockIpfsHash
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit bid: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Shortlist bids (BPPA Officer)
     */
    public function shortlistBids(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'procurement_id' => 'required|exists:procurements,id',
            'bid_ids' => 'required|array|max:4',
            'bid_ids.*' => 'exists:bids,id',
            'officer_id' => 'required|exists:bppa_officers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Update selected bids as shortlisted
            Bid::whereIn('id', $request->bid_ids)
               ->where('procurement_id', $request->procurement_id)
               ->update([
                   'is_shortlisted' => true,
                   'shortlisted_at' => now(),
                   'shortlisted_by' => $request->officer_id,
                   'status' => 'shortlisted'
               ]);

            // Update procurement status
            $procurement = Procurement::find($request->procurement_id);
            $procurement->update(['status' => 'shortlisted']);

            $shortlistedBids = Bid::with(['vendor:id,company_name'])
                                 ->whereIn('id', $request->bid_ids)
                                 ->get();

            return response()->json([
                'success' => true,
                'message' => 'Bids shortlisted successfully',
                'data' => $shortlistedBids
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to shortlist bids: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start public voting (BPPA Officer)
     */
    public function startVoting(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'voting_duration_days' => 'required|integer|min:1|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $procurement = Procurement::find($id);
        if (!$procurement) {
            return response()->json([
                'success' => false,
                'message' => 'Procurement not found'
            ], 404);
        }

        if ($procurement->status !== 'shortlisted') {
            return response()->json([
                'success' => false,
                'message' => 'Procurement must have shortlisted bids to start voting'
            ], 409);
        }

        try {
            $votingEnds = now()->addDays($request->voting_duration_days);
            $mockTxHash = '0x' . Str::random(64);

            $procurement->update([
                'status' => 'voting',
                'voting_ends_at' => $votingEnds,
                'blockchain_tx_hash' => $mockTxHash
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Voting started successfully',
                'data' => [
                    'procurement' => $procurement,
                    'voting_ends_at' => $votingEnds,
                    'blockchain_tx' => $mockTxHash
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start voting: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cast vote on a bid (Citizen)
     */
    public function castVote(Request $request)
    {
        // Get citizen ID from session
        $citizen_id = Session::get('user_id');
        
        if (!$citizen_id) {
            return response()->json([
                'success' => false,
                'message' => "Not authenticated. Please log in as a citizen. Citizen ID is $citizen_id"
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'bid_id' => 'required|exists:bids,id',
            'vote' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $bid = Bid::with('procurement')->find($request->bid_id);
        
        // Check if procurement is in voting phase
        if ($bid->procurement->status !== 'voting' || 
            now() > $bid->procurement->voting_ends_at) {
            return response()->json([
                'success' => false,
                'message' => 'Voting is not active for this procurement'
            ], 409);
        }

        // Check if citizen already voted for this bid
        $existingVote = Vote::where('citizen_id', $citizen_id)
                           ->where('bid_id', $request->bid_id)
                           ->first();

        if ($existingVote) {
            return response()->json([
                'success' => false,
                'message' => 'You have already voted for this bid'
            ], 409);
        }

        try {
            $mockTxHash = '0x' . Str::random(64);

            // Create vote record
            $vote = Vote::create([
                'citizen_id' => $citizen_id,
                'bid_id' => $request->bid_id,
                'vote' => $request->vote,
                'blockchain_tx_hash' => $mockTxHash
            ]);

            // Update bid vote counts
            if ($request->vote) {
                $bid->increment('votes_yes');
            } else {
                $bid->increment('votes_no');
            }

            return response()->json([
                'success' => true,
                'message' => 'Vote cast successfully',
                'data' => [
                    'vote' => $vote,
                    'blockchain_tx' => $mockTxHash,
                    'current_votes' => [
                        'yes' => $bid->fresh()->votes_yes,
                        'no' => $bid->fresh()->votes_no
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cast vote: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active procurements for voting
     */
    public function getActiveProcurements()
    {
        $procurements = Procurement::with(['shortlistedBids.vendor:id,company_name'])
                                  ->where('status', 'voting')
                                  ->where('voting_ends_at', '>', now())
                                  ->orderBy('voting_ends_at', 'asc')
                                  ->get();

        return response()->json([
            'success' => true,
            'data' => $procurements
        ]);
    }

    /**
     * Get procurement details with bids and votes
     */
    public function getProcurementDetails($id)
    {
        $procurement = Procurement::with([
                'shortlistedBids.vendor:id,company_name',
                'shortlistedBids.votes',
                'creator:id,full_name,officer_id'
            ])->find($id);

        if (!$procurement) {
            return response()->json([
                'success' => false,
                'message' => 'Procurement not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $procurement
        ]);
    }

    /**
     * Complete voting and determine winner (BPPA Officer)
     */
    public function completeVoting(Request $request, $id)
    {
        $procurement = Procurement::with('shortlistedBids')->find($id);
        
        if (!$procurement) {
            return response()->json([
                'success' => false,
                'message' => 'Procurement not found'
            ], 404);
        }

        if ($procurement->status !== 'voting') {
            return response()->json([
                'success' => false,
                'message' => 'Procurement is not in voting phase'
            ], 409);
        }

        if (now() <= $procurement->voting_ends_at) {
            return response()->json([
                'success' => false,
                'message' => 'Voting period has not ended yet'
            ], 409);
        }

        try {
            // Find winning bid (highest YES votes)
            $winningBid = $procurement->shortlistedBids()
                                    ->orderBy('votes_yes', 'desc')
                                    ->first();

            if ($winningBid) {
                $winningBid->update(['status' => 'winning']);
                $procurement->update([
                    'winning_bid_id' => $winningBid->id,
                    'status' => 'completed'
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Voting completed successfully',
                'data' => [
                    'procurement' => $procurement->fresh(),
                    'winning_bid' => $winningBid ? $winningBid->load('vendor:id,company_name') : null
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete voting: ' . $e->getMessage()
            ], 500);
        }
    }
}
