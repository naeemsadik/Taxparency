<?php

namespace App\Http\Controllers;

use App\Models\Procurement;
use App\Models\Bid;
use App\Models\Vote;
use App\Models\Citizen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CitizenDashboardController extends Controller
{
    /**
     * Get all active procurements available for voting
     */
    public function getActiveProcurements()
    {
        $citizen_id = Session::get('user_id');
        
        if (!$citizen_id) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated. Please log in as a citizen.'
            ], 401);
        }

        try {
            $procurements = Procurement::with([
                    'shortlistedBids.vendor:id,company_name',
                    'creator:id,full_name,officer_id'
                ])
                ->where('status', 'voting')
                ->where('voting_ends_at', '>', now())
                ->orderBy('voting_ends_at', 'asc')
                ->get()
                ->map(function ($procurement) use ($citizen_id) {
                    // Add voting status for each bid
                    $procurement->shortlistedBids->each(function ($bid) use ($citizen_id) {
                        $bid->has_voted = Vote::where('citizen_id', $citizen_id)
                                            ->where('bid_id', $bid->id)
                                            ->exists();
                        $bid->my_vote = Vote::where('citizen_id', $citizen_id)
                                          ->where('bid_id', $bid->id)
                                          ->value('vote');
                    });
                    return $procurement;
                });

            return response()->json([
                'success' => true,
                'data' => $procurements
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch active procurements: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed information about a specific procurement and its shortlisted bids
     */
    public function getProcurementDetails($id)
    {
        $citizen_id = Session::get('user_id');
        
        if (!$citizen_id) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated. Please log in as a citizen.'
            ], 401);
        }

        try {
            $procurement = Procurement::with([
                    'shortlistedBids.vendor:id,company_name,vendor_license_number,contact_person,contact_phone,contact_email,company_address',
                    'shortlistedBids.votes',
                    'creator:id,full_name,officer_id'
                ])->find($id);

            if (!$procurement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Procurement not found'
                ], 404);
            }

            // Add detailed voting information for citizen
            $procurement->shortlistedBids->each(function ($bid) use ($citizen_id) {
                $bid->has_voted = Vote::where('citizen_id', $citizen_id)
                                    ->where('bid_id', $bid->id)
                                    ->exists();
                $bid->my_vote = Vote::where('citizen_id', $citizen_id)
                                  ->where('bid_id', $bid->id)
                                  ->value('vote');
                
                // Calculate vote statistics
                $bid->total_votes = $bid->votes_yes + $bid->votes_no;
                $bid->vote_percentage = $bid->total_votes > 0 
                    ? round(($bid->votes_yes / $bid->total_votes) * 100, 1) 
                    : 0;
            });

            return response()->json([
                'success' => true,
                'data' => $procurement
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch procurement details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed information about a specific bid
     */
    public function getBidDetails($bid_id)
    {
        $citizen_id = Session::get('user_id');
        
        if (!$citizen_id) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated. Please log in as a citizen.'
            ], 401);
        }

        try {
            $bid = Bid::with([
                    'vendor:id,company_name,license_number,contact_person,phone,email,address',
                    'procurement:id,title,description,procurement_id,estimated_value,category,voting_ends_at,status',
                    'votes'
                ])->find($bid_id);

            if (!$bid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bid not found'
                ], 404);
            }

            if (!$bid->is_shortlisted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bid is not shortlisted for public voting'
                ], 403);
            }

            // Add voting information for this citizen
            $bid->has_voted = Vote::where('citizen_id', $citizen_id)
                                ->where('bid_id', $bid->id)
                                ->exists();
            $bid->my_vote = Vote::where('citizen_id', $citizen_id)
                              ->where('bid_id', $bid->id)
                              ->value('vote');
            
            // Calculate vote statistics
            $bid->total_votes = $bid->votes_yes + $bid->votes_no;
            $bid->vote_percentage = $bid->total_votes > 0 
                ? round(($bid->votes_yes / $bid->total_votes) * 100, 1) 
                : 0;

            return response()->json([
                'success' => true,
                'data' => $bid
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch bid details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cast a vote on a shortlisted bid
     */
    public function castVote(Request $request)
    {
        $citizen_id = Session::get('user_id');
        
        if (!$citizen_id) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated. Please log in as a citizen.'
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

        try {
            $bid = Bid::with('procurement')->find($request->bid_id);
            
            // Check if bid is shortlisted
            if (!$bid->is_shortlisted) {
                return response()->json([
                    'success' => false,
                    'message' => 'This bid is not available for public voting'
                ], 403);
            }
            
            // Check if procurement is in voting phase
            if ($bid->procurement->status !== 'voting') {
                return response()->json([
                    'success' => false,
                    'message' => 'Voting is not active for this procurement'
                ], 409);
            }
            
            // Check if voting period has expired
            if (now() > $bid->procurement->voting_ends_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Voting period has ended'
                ], 409);
            }

            // Check if citizen already voted for ANY bid in this procurement
            $existingVote = Vote::where('citizen_id', $citizen_id)
                               ->whereHas('bid', function($query) use ($bid) {
                                   $query->where('procurement_id', $bid->procurement_id);
                               })
                               ->first();

            if ($existingVote) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already voted on a bid in this procurement. You can only vote once per procurement.'
                ], 409);
            }

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

            // Automatically reject all other shortlisted bids for this citizen
            $otherShortlistedBids = Bid::where('procurement_id', $bid->procurement_id)
                ->where('id', '!=', $bid->id)
                ->where('is_shortlisted', true)
                ->get();

            foreach ($otherShortlistedBids as $otherBid) {
                // Create automatic rejection votes for other bids
                Vote::create([
                    'citizen_id' => $citizen_id,
                    'bid_id' => $otherBid->id,
                    'vote' => false, // Automatically vote NO
                    'blockchain_tx_hash' => '0x' . Str::random(64)
                ]);

                // Update vote counts for rejected bids
                $otherBid->increment('votes_no');
            }

            return response()->json([
                'success' => true,
                'message' => 'Vote cast successfully. Other shortlisted bids have been automatically rejected.',
                'data' => [
                    'vote' => $vote,
                    'blockchain_tx' => $mockTxHash,
                    'auto_rejected_bids' => $otherShortlistedBids->count(),
                    'current_votes' => [
                        'yes' => $bid->fresh()->votes_yes,
                        'no' => $bid->fresh()->votes_no,
                        'total' => $bid->fresh()->votes_yes + $bid->fresh()->votes_no
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
     * Get citizen's voting history
     */
    public function getMyVotes()
    {
        $citizen_id = Session::get('user_id');
        
        if (!$citizen_id) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated. Please log in as a citizen.'
            ], 401);
        }

        try {
            $votes = Vote::with([
                    'bid.vendor:id,company_name',
                    'bid.procurement:id,title,procurement_id,status,voting_ends_at'
                ])
                ->where('citizen_id', $citizen_id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $votes
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch voting history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dashboard statistics for citizen
     */
    public function getDashboardStats()
    {
        $citizen_id = Session::get('user_id');
        
        if (!$citizen_id) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated. Please log in as a citizen.'
            ], 401);
        }

        try {
            $stats = [
                'active_procurements' => Procurement::where('status', 'voting')
                                                   ->where('voting_ends_at', '>', now())
                                                   ->count(),
                'my_votes_count' => Vote::where('citizen_id', $citizen_id)->count(),
                'total_procurements' => Procurement::count(),
                'completed_procurements' => Procurement::where('status', 'completed')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}
