<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WinningBid;
use App\Models\Procurement;
use App\Services\BlockchainService;
use Illuminate\Support\Facades\Validator;

class WinningBidController extends Controller
{
    protected $blockchainService;

    public function __construct(BlockchainService $blockchainService)
    {
        $this->blockchainService = $blockchainService;
    }

    /**
     * Get all winning bids with filters
     */
    public function index(Request $request)
    {
        try {
            $query = WinningBid::with([
                'procurement:id,title,procurement_id,category,estimated_value',
                'bid:id,bid_amount,completion_days',
                'vendor:id,company_name,vendor_license_number,contact_person',
                'awardedByOfficer:id,name,officer_id'
            ]);

            // Apply filters
            if ($request->has('contract_status')) {
                $query->where('contract_status', $request->contract_status);
            }

            if ($request->has('is_on_chain')) {
                $query->where('is_on_chain', $request->boolean('is_on_chain'));
            }

            if ($request->has('vendor_id')) {
                $query->where('vendor_id', $request->vendor_id);
            }

            if ($request->has('procurement_category')) {
                $query->whereHas('procurement', function($q) use ($request) {
                    $q->where('category', $request->procurement_category);
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'contract_awarded_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = min($request->get('per_page', 15), 50);
            $winningBids = $query->paginate($perPage);

            // Add calculated fields
            $winningBids->getCollection()->transform(function ($winningBid) {
                $winningBid->contract_duration_days = $winningBid->getContractDurationDays();
                $winningBid->contract_progress = $winningBid->getContractProgress();
                $winningBid->is_contract_active = $winningBid->isContractActive();
                $winningBid->time_until_completion = $winningBid->getTimeUntilCompletion();
                return $winningBid;
            });

            return response()->json([
                'success' => true,
                'data' => $winningBids
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch winning bids: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific winning bid details
     */
    public function show($id)
    {
        try {
            $winningBid = WinningBid::with([
                'procurement:id,title,procurement_id,description,category,estimated_value,created_at',
                'bid:id,bid_amount,technical_proposal,completion_days,additional_notes,costing_document',
                'vendor:id,company_name,vendor_license_number,contact_person,contact_email,contact_phone,company_address',
                'awardedByOfficer:id,name,officer_id,email'
            ])->find($id);

            if (!$winningBid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Winning bid not found'
                ], 404);
            }

            // Add calculated fields
            $winningBid->contract_duration_days = $winningBid->getContractDurationDays();
            $winningBid->contract_progress = $winningBid->getContractProgress();
            $winningBid->is_contract_active = $winningBid->isContractActive();
            $winningBid->time_until_completion = $winningBid->getTimeUntilCompletion();

            return response()->json([
                'success' => true,
                'data' => $winningBid
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch winning bid: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get winning bids statistics
     */
    public function statistics()
    {
        try {
            $stats = [
                'total_winning_bids' => WinningBid::count(),
                'total_contract_value' => WinningBid::sum('winning_amount'),
                'average_contract_value' => WinningBid::avg('winning_amount'),
                'contract_statuses' => WinningBid::selectRaw('contract_status, count(*) as count')
                    ->groupBy('contract_status')
                    ->pluck('count', 'contract_status')
                    ->toArray(),
                'blockchain_distribution' => [
                    'on_chain' => WinningBid::onChain()->count(),
                    'off_chain' => WinningBid::offChain()->count(),
                    'pending_sync' => WinningBid::pendingBlockchainSync()->count(),
                ],
                'top_vendors' => WinningBid::selectRaw('vendor_id, count(*) as wins, sum(winning_amount) as total_value')
                    ->with('vendor:id,company_name')
                    ->groupBy('vendor_id')
                    ->orderBy('total_value', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function($item) {
                        return [
                            'vendor_name' => $item->vendor->company_name,
                            'wins' => $item->wins,
                            'total_value' => $item->total_value,
                        ];
                    }),
                'monthly_awards' => WinningBid::selectRaw('DATE_FORMAT(contract_awarded_at, "%Y-%m") as month, count(*) as awards, sum(winning_amount) as total_value')
                    ->whereNotNull('contract_awarded_at')
                    ->groupBy('month')
                    ->orderBy('month', 'desc')
                    ->limit(12)
                    ->get(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update contract status
     */
    public function updateContractStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'contract_status' => 'required|in:awarded,signed,in_progress,completed,terminated',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $winningBid = WinningBid::find($id);
            
            if (!$winningBid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Winning bid not found'
                ], 404);
            }

            $oldStatus = $winningBid->contract_status;
            $newStatus = $request->contract_status;

            // Update status
            $winningBid->update([
                'contract_status' => $newStatus,
            ]);

            // Auto-set dates based on status
            if ($newStatus === 'in_progress' && !$winningBid->contract_start_date) {
                $winningBid->update(['contract_start_date' => now()]);
            }

            if ($newStatus === 'completed' && !$winningBid->contract_end_date) {
                $winningBid->update(['contract_end_date' => now()]);
            }

            return response()->json([
                'success' => true,
                'message' => "Contract status updated from '{$oldStatus}' to '{$newStatus}'",
                'data' => $winningBid->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update contract status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get blockchain status for all winning bids
     */
    public function blockchainStatus()
    {
        try {
            $status = $this->blockchainService->getBlockchainStatus();
            $integrityCheck = $this->blockchainService->verifyDataIntegrity();

            return response()->json([
                'success' => true,
                'data' => [
                    'blockchain_status' => $status,
                    'data_integrity' => $integrityCheck,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch blockchain status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync off-chain data to blockchain
     */
    public function syncToBlockchain()
    {
        try {
            $result = $this->blockchainService->syncToBlockchain();

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync to blockchain: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get winning bids for a specific vendor
     */
    public function byVendor($vendorId)
    {
        try {
            $winningBids = WinningBid::where('vendor_id', $vendorId)
                ->with([
                    'procurement:id,title,procurement_id,category',
                    'vendor:id,company_name'
                ])
                ->orderBy('contract_awarded_at', 'desc')
                ->get();

            // Add calculated fields
            $winningBids->transform(function ($winningBid) {
                $winningBid->contract_progress = $winningBid->getContractProgress();
                $winningBid->is_contract_active = $winningBid->isContractActive();
                return $winningBid;
            });

            $summary = [
                'total_wins' => $winningBids->count(),
                'total_value' => $winningBids->sum('winning_amount'),
                'average_value' => $winningBids->avg('winning_amount'),
                'active_contracts' => $winningBids->filter->isContractActive()->count(),
                'completed_contracts' => $winningBids->where('contract_status', 'completed')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'winning_bids' => $winningBids,
                    'summary' => $summary,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch vendor winning bids: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get winning bids for vendor dashboard (with enhanced details)
     */
    public function getVendorWinningBids($vendorId)
    {
        try {
            $winningBids = WinningBid::where('vendor_id', $vendorId)
                ->with([
                    'procurement:id,title,procurement_id,category,estimated_value,description',
                    'bid:id,bid_amount,technical_proposal,completion_days',
                    'vendor:id,company_name,contact_person',
                    'awardedByOfficer:id,name,officer_id'
                ])
                ->orderBy('contract_awarded_at', 'desc')
                ->get();

            // Add calculated fields for dashboard
            $winningBids->transform(function ($winningBid) {
                $winningBid->contract_duration_days = $winningBid->getContractDurationDays();
                $winningBid->contract_progress = $winningBid->getContractProgress();
                $winningBid->is_contract_active = $winningBid->isContractActive();
                $winningBid->time_until_completion = $winningBid->getTimeUntilCompletion();
                $winningBid->status_badge = $this->getStatusBadge($winningBid->contract_status);
                $winningBid->progress_color = $this->getProgressColor($winningBid->contract_progress);
                return $winningBid;
            });

            // Calculate enhanced statistics
            $stats = [
                'total_wins' => $winningBids->count(),
                'total_contract_value' => $winningBids->sum('winning_amount'),
                'average_contract_value' => $winningBids->avg('winning_amount'),
                'active_contracts' => $winningBids->filter->isContractActive()->count(),
                'completed_contracts' => $winningBids->where('contract_status', 'completed')->count(),
                'in_progress_contracts' => $winningBids->where('contract_status', 'in_progress')->count(),
                'total_active_value' => $winningBids->filter->isContractActive()->sum('winning_amount'),
                'win_rate_percentage' => 0, // This would need bid data to calculate
                'contract_statuses' => $winningBids->groupBy('contract_status')->map->count(),
                'categories' => $winningBids->groupBy('procurement.category')->map->count(),
                'blockchain_sync' => [
                    'on_chain' => $winningBids->where('is_on_chain', true)->count(),
                    'off_chain' => $winningBids->where('is_on_chain', false)->count(),
                    'pending_sync' => $winningBids->where('blockchain_sync_pending', true)->count(),
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'winning_bids' => $winningBids,
                    'statistics' => $stats,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch vendor winning bids: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed winning bid information for vendor
     */
    public function getWinningBidDetails($id)
    {
        try {
            $winningBid = WinningBid::with([
                'procurement:id,title,procurement_id,description,category,estimated_value,requirements,created_at',
                'bid:id,bid_amount,technical_proposal,completion_days,additional_notes',
                'vendor:id,company_name,vendor_license_number,contact_person,contact_email,contact_phone',
                'awardedByOfficer:id,name,officer_id,email'
            ])->find($id);

            if (!$winningBid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Winning bid not found'
                ], 404);
            }

            // Add enhanced calculated fields
            $winningBid->contract_duration_days = $winningBid->getContractDurationDays();
            $winningBid->contract_progress = $winningBid->getContractProgress();
            $winningBid->is_contract_active = $winningBid->isContractActive();
            $winningBid->time_until_completion = $winningBid->getTimeUntilCompletion();
            $winningBid->status_badge = $this->getStatusBadge($winningBid->contract_status);
            $winningBid->progress_color = $this->getProgressColor($winningBid->contract_progress);
            $winningBid->days_since_award = $winningBid->contract_awarded_at ? 
                now()->diffInDays($winningBid->contract_awarded_at) : null;

            // Contract timeline
            $timeline = [
                [
                    'phase' => 'Contract Awarded',
                    'date' => $winningBid->contract_awarded_at,
                    'status' => 'completed',
                    'description' => 'Contract has been awarded to vendor'
                ],
                [
                    'phase' => 'Contract Signed',
                    'date' => null,
                    'status' => in_array($winningBid->contract_status, ['signed', 'in_progress', 'completed']) ? 'completed' : 'pending',
                    'description' => 'Contract documents signed by both parties'
                ],
                [
                    'phase' => 'Work In Progress',
                    'date' => $winningBid->contract_start_date,
                    'status' => in_array($winningBid->contract_status, ['in_progress', 'completed']) ? 'completed' : 'pending',
                    'description' => 'Contract work is being executed'
                ],
                [
                    'phase' => 'Contract Completed',
                    'date' => $winningBid->contract_end_date,
                    'status' => $winningBid->contract_status === 'completed' ? 'completed' : 'pending',
                    'description' => 'Contract successfully completed'
                ],
            ];

            $winningBid->contract_timeline = $timeline;

            return response()->json([
                'success' => true,
                'data' => $winningBid
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch winning bid details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to get status badge configuration
     */
    private function getStatusBadge($status)
    {
        $badges = [
            'awarded' => ['text' => 'AWARDED', 'class' => 'status-awarded', 'color' => '#17a2b8'],
            'signed' => ['text' => 'SIGNED', 'class' => 'status-signed', 'color' => '#6f42c1'],
            'in_progress' => ['text' => 'IN PROGRESS', 'class' => 'status-in-progress', 'color' => '#ffc107'],
            'completed' => ['text' => 'COMPLETED', 'class' => 'status-completed', 'color' => '#28a745'],
            'terminated' => ['text' => 'TERMINATED', 'class' => 'status-terminated', 'color' => '#dc3545'],
        ];

        return $badges[$status] ?? ['text' => strtoupper($status), 'class' => 'status-unknown', 'color' => '#6c757d'];
    }

    /**
     * Helper method to get progress color
     */
    private function getProgressColor($progress)
    {
        if ($progress >= 80) return '#28a745'; // Green
        if ($progress >= 50) return '#ffc107'; // Yellow
        if ($progress >= 25) return '#fd7e14'; // Orange
        return '#dc3545'; // Red
    }
}
