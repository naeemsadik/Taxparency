<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use App\Models\TaxReturn;
use App\Models\Procurement;
use App\Models\Bid;
use App\Models\Vote;

class DashboardController extends Controller
{
    public function citizenDashboard()
    {
        $userId = Session::get('user_id');
        $userTiin = Session::get('user_tiin');

        if (!$userId) {
            return redirect('/login/citizen')->with('error', 'Please log in to access your dashboard.');
        }

        // Get citizen's tax returns
        $taxReturns = TaxReturn::where('citizen_id', $userId)->orderBy('created_at', 'desc')->get();

        // Get active procurements for voting (with proper relationships)
        $activeProcurements = Procurement::where('status', 'voting')
            ->where('voting_ends_at', '>', now())
            ->with([
                'shortlistedBids' => function($query) {
                    $query->where('is_shortlisted', true)
                          ->with('vendor:id,company_name,vendor_license_number,contact_person,contact_email,contact_phone,company_address');
                },
                'creator:id,full_name,officer_id'
            ])
            ->orderBy('voting_ends_at', 'asc')
            ->get()
            ->map(function ($procurement) use ($userId) {
                // Add voting status for each bid for this citizen
                if ($procurement->shortlistedBids) {
                    $procurement->shortlistedBids->each(function ($bid) use ($userId) {
                        $existingVote = Vote::where('citizen_id', $userId)
                                          ->where('bid_id', $bid->id)
                                          ->first();
                        $bid->has_voted = $existingVote !== null;
                        $bid->my_vote = $existingVote ? $existingVote->vote : null;
                        $bid->total_votes = $bid->votes_yes + $bid->votes_no;
                        $bid->vote_percentage = $bid->total_votes > 0 
                            ? round(($bid->votes_yes / $bid->total_votes) * 100, 1) 
                            : 0;
                    });
                }
                return $procurement;
            });

        // Get citizen's voting history
        $votes = Vote::where('citizen_id', $userId)
                    ->with([
                        'bid.vendor:id,company_name',
                        'bid.procurement:id,title,procurement_id,status,voting_ends_at'
                    ])
                    ->orderBy('created_at', 'desc')
                    ->get();

        // Calculate statistics
        $stats = [
            'total_returns' => $taxReturns->count(),
            'approved_returns' => $taxReturns->where('status', 'approved')->count(),
            'total_votes' => $votes->count(),
            'total_tax_paid' => $taxReturns->where('status', 'approved')->sum('tax_amount'),
            'active_procurements' => $activeProcurements->count()
        ];

        return view('dashboards.citizen', compact('taxReturns', 'activeProcurements', 'votes', 'stats'));
    }

    public function nbrDashboard()
    {
        $userId = Session::get('user_id');

        // Get pending tax returns for review
        $pendingReturns = TaxReturn::where('status', 'pending')
            ->with('citizen')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get recently reviewed returns by this officer
        $recentActivity = TaxReturn::whereIn('status', ['approved', 'declined'])
            ->where('reviewed_by', $userId)
            ->with('citizen')
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        // Calculate statistics
        $stats = [
            'total_returns' => TaxReturn::count(),
            'pending_returns' => $pendingReturns->count(),
            'approved_returns' => TaxReturn::where('status', 'approved')->count(),
            'declined_returns' => TaxReturn::where('status', 'declined')->count(),
        ];

        return view('dashboards.nbr', compact('pendingReturns', 'recentActivity', 'stats'));
    }

    public function vendorDashboard()
    {
        $userId = Session::get('user_id');

        // Get open procurements
        $openProcurements = Procurement::whereIn('status', ['open', 'bidding'])
            ->orderBy('deadline', 'asc')
            ->get();

        // Get vendor's bids
        $myBids = Bid::where('vendor_id', $userId)
            ->with('procurement')
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate statistics
        $stats = [
            'total_bids' => $myBids->count(),
            'active_bids' => $myBids->where('status', 'submitted')->count(),
            'shortlisted_bids' => $myBids->where('status', 'shortlisted')->count(),
            'won_bids' => $myBids->where('status', 'winner')->count(),
        ];

        return view('dashboards.vendor', compact('openProcurements', 'myBids', 'stats'));
    }

    public function bppaDashboard()
    {
        $userId = Session::get('user_id');

        // Get procurements managed by this officer
        $myProcurements = Procurement::where('created_by', $userId)
            ->withCount(['bids'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get procurements needing attention (review bids, shortlist, etc.)
        $actionNeeded = Procurement::where('status', 'bidding')
            ->where('deadline', '<', now())
            ->with(['bids' => function($query) {
                $query->where('status', 'submitted');
            }])
            ->get();

        // Calculate statistics
        $stats = [
            'total_procurements' => $myProcurements->count(),
            'open_procurements' => $myProcurements->where('status', 'open')->count(),
            'bidding_procurements' => $myProcurements->where('status', 'bidding')->count(),
            'completed_procurements' => $myProcurements->where('status', 'completed')->count(),
        ];

        return view('dashboards.bppa', compact('myProcurements', 'actionNeeded', 'stats'));
    }
}
