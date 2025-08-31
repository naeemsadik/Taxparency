<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use App\Models\Procurement;
use App\Models\Bid;
use App\Models\Vendor;

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
            'estimated_value' => 'required|numeric|min:0',
            'category' => 'required|string|max:100',
            'submission_deadline' => 'required|date|after:today',
            'project_start_date' => 'nullable|date',
            'project_end_date' => 'nullable|date|after:project_start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $officerId = Session::get('user_id');
            if (!$officerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'BPPA officer not authenticated'
                ], 401);
            }

            $procurementId = 'BD-' . date('Y') . '-' . strtoupper(substr($request->category, 0, 3)) . '-' . str_pad(Procurement::count() + 1, 3, '0', STR_PAD_LEFT);

            $procurementData = $request->all();
            $procurementData['procurement_id'] = $procurementId;
            $procurementData['created_by'] = $officerId;
            $procurementData['status'] = 'open';

            $procurement = Procurement::create($procurementData);

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
     * Get procurement details (BPPA Officer or Public)
     */
    public function getProcurementDetails($id)
    {
        try {
            $officerId = Session::get('user_id');
            $userType = Session::get('user_type');
            
            if ($officerId && $userType === 'bppa') {
                $procurement = Procurement::with([
                    'bids.vendor:id,company_name',
                    'creator:id,full_name,officer_id'
                ])->where('id', $id)
                  ->where('created_by', $officerId)
                  ->first();

                if (!$procurement) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Procurement not found or access denied'
                    ], 404);
                }

                // Add bids count for BPPA view
                $procurement->bids_count = $procurement->bids->count();

                return response()->json([
                    'success' => true,
                    'data' => $procurement
                ]);
            } else {
                // Public request - return limited info
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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch procurement details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Shortlist bids for a procurement (BPPA Officer)
     */
    public function shortlistBids(Request $request)
    {
        try {
            // Get the authenticated BPPA officer ID from session
            $officerId = Session::get('user_id');
            if (!$officerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'BPPA officer not authenticated'
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'procurement_id' => 'required|exists:procurements,id',
                'bid_ids' => 'required|array',
                'bid_ids.*' => 'exists:bids,id',
                'shortlist_comments' => 'required|array',
                'shortlist_comments.*' => 'required|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $procurement = Procurement::where('id', $request->procurement_id)
                ->where('created_by', $officerId)
                ->first();

            if (!$procurement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Procurement not found or access denied'
                ], 404);
            }

            if ($procurement->status !== 'bidding') {
                return response()->json([
                    'success' => false,
                    'message' => 'Procurement is not in bidding phase'
                ], 409);
            }

            // First, unshortlist all existing bids for this procurement
            Bid::where('procurement_id', $request->procurement_id)
                ->update([
                    'is_shortlisted' => false,
                    'shortlisted_at' => null,
                    'shortlisted_by' => null,
                    'shortlist_comment' => null
                ]);

            // Shortlist the selected bids
            $shortlistedBids = [];
            foreach ($request->bid_ids as $index => $bidId) {
                $bid = Bid::find($bidId);
                if ($bid && $bid->procurement_id == $request->procurement_id) {
                    $bid->update([
                        'is_shortlisted' => true,
                        'shortlisted_at' => now(),
                        'shortlisted_by' => $officerId,
                        'shortlist_comment' => $request->shortlist_comments[$index] ?? '',
                        'status' => 'shortlisted'
                    ]);
                    $shortlistedBids[] = $bid->load('vendor:id,company_name');
                }
            }

            // Update procurement status to voting if bids were shortlisted
            if (count($shortlistedBids) > 0) {
                $procurement->update([
                    'status' => 'voting',
                    'voting_ends_at' => now()->addDays(7) // 7 days voting period
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Bids shortlisted successfully',
                'data' => [
                    'shortlisted_bids' => $shortlistedBids,
                    'procurement_status' => $procurement->fresh()->status,
                    'voting_ends_at' => $procurement->fresh()->voting_ends_at
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to shortlist bids: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all bids for a procurement (BPPA Officer)
     */
    public function getProcurementBids($id)
    {
        try {
            $officerId = Session::get('user_id');
            if (!$officerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'BPPA officer not authenticated'
                ], 401);
            }

            $procurement = Procurement::where('id', $id)
                ->where('created_by', $officerId)
                ->with([
                    'bids' => function($query) {
                        $query->with('vendor:id,company_name,vendor_license_number,contact_person,contact_email,contact_phone')
                              ->orderBy('created_at', 'desc');
                    }
                ])
                ->first();

            if (!$procurement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Procurement not found or access denied'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'procurement' => $procurement,
                    'bids_count' => $procurement->bids->count(),
                    'shortlisted_count' => $procurement->bids->where('is_shortlisted', true)->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch procurement bids: ' . $e->getMessage()
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
                    'procurement' => $procurement->fresh(),
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
