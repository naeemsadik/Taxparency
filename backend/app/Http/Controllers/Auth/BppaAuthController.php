<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\BppaOfficer;
use App\Models\Vendor;
use App\Models\Procurement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class BppaAuthController extends Controller
{
    /**
     * Handle BPPA officer login
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $officer = BppaOfficer::where('username', $request->username)->first();

        if (!$officer || !Hash::check($request->password, $officer->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid username or password'
            ], 401);
        }

        // Generate API token (for API usage)
        $token = $officer->createToken('bppa-access')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'officer' => [
                    'id' => $officer->id,
                    'username' => $officer->username,
                    'full_name' => $officer->full_name,
                    'officer_id' => $officer->officer_id,
                    'department' => $officer->department,
                    'designation' => $officer->designation,
                ],
                'token' => $token
            ]
        ]);
    }

    /**
     * Handle BPPA officer logout
     */
    public function logout(Request $request): JsonResponse
    {
        // For demo purposes, just return success
        return response()->json([
            'success' => true,
            'message' => 'Logout successful'
        ]);
    }

    /**
     * Get authenticated BPPA officer profile
     */
    public function profile(Request $request): JsonResponse
    {
        // For demo purposes, return sample officer data
        $officer = BppaOfficer::first(); // Get first officer for demo
        
        if (!$officer) {
            return response()->json([
                'success' => false,
                'message' => 'No BPPA officer data found'
            ], 404);
        }
        
        // Get officer statistics
        $stats = [
            'total_procurements_created' => $officer->createdProcurements()->count(),
            'active_procurements' => $officer->createdProcurements()->whereIn('status', ['open', 'closed', 'voting'])->count(),
            'completed_procurements' => $officer->createdProcurements()->where('status', 'completed')->count(),
            'vendors_approved' => $officer->approvedVendors()->count(),
            'pending_fund_requests' => 8, // Mock data - would query blockchain
            'total_fund_amount_approved' => 142000000, // Mock data
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'officer' => [
                    'id' => $officer->id,
                    'username' => $officer->username,
                    'full_name' => $officer->full_name,
                    'officer_id' => $officer->officer_id,
                    'department' => $officer->department,
                    'designation' => $officer->designation,
                    'created_at' => $officer->created_at,
                ],
                'statistics' => $stats
            ]
        ]);
    }

    /**
     * Get procurements created by this BPPA officer
     */
    public function getMyProcurements(Request $request, string $officerId): JsonResponse
    {
        try {
            $officer = BppaOfficer::where('officer_id', $officerId)->first();
            
            if (!$officer) {
                return response()->json([
                    'success' => false,
                    'message' => 'BPPA officer not found'
                ], 404);
            }

            $procurements = $officer->createdProcurements()->with(['bids'])->get();

            $processedProcurements = $procurements->map(function ($procurement) {
                return [
                    'id' => $procurement->id,
                    'procurement_id' => $procurement->procurement_id,
                    'title' => $procurement->title,
                    'category' => $procurement->category,
                    'estimated_value' => $procurement->estimated_value,
                    'status' => $procurement->status,
                    'submission_deadline' => $procurement->submission_deadline,
                    'total_bids' => $procurement->bids->count(),
                    'shortlisted_bids' => $procurement->bids->where('is_shortlisted', true)->count(),
                    'voting_ends_at' => $procurement->voting_ends_at,
                    'created_at' => $procurement->created_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'officer_id' => $officerId,
                    'total_procurements' => $procurements->count(),
                    'procurements' => $processedProcurements
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get BPPA officer procurements', [
                'officer_id' => $officerId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve procurements'
            ], 500);
        }
    }

    /**
     * Get pending vendor approvals
     */
    public function getPendingVendors(): JsonResponse
    {
        try {
            $pendingVendors = Vendor::where('is_approved', false)
                ->orWhereNull('approved_by')
                ->get();

            $processedVendors = $pendingVendors->map(function ($vendor) {
                return [
                    'id' => $vendor->id,
                    'username' => $vendor->username,
                    'company_name' => $vendor->company_name,
                    'vendor_license_number' => $vendor->vendor_license_number,
                    'contact_person' => $vendor->contact_person,
                    'contact_email' => $vendor->contact_email,
                    'contact_phone' => $vendor->contact_phone,
                    'company_address' => $vendor->company_address,
                    'created_at' => $vendor->created_at,
                    'days_pending' => now()->diffInDays($vendor->created_at),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'total_pending' => $pendingVendors->count(),
                    'vendors' => $processedVendors
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get pending vendors', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve pending vendors'
            ], 500);
        }
    }

    /**
     * Approve a vendor
     */
    public function approveVendor(Request $request, int $vendorId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'bppa_officer_id' => 'required|string',
                'approval_notes' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $vendor = Vendor::findOrFail($vendorId);
            $officer = BppaOfficer::where('officer_id', $request->bppa_officer_id)->first();

            if (!$officer) {
                return response()->json([
                    'success' => false,
                    'message' => 'BPPA officer not found'
                ], 404);
            }

            $vendor->update([
                'is_approved' => true,
                'approved_by' => $officer->id,
                'approved_at' => now(),
                'approval_notes' => $request->approval_notes
            ]);

            Log::info('Vendor approved by BPPA officer', [
                'vendor_id' => $vendorId,
                'vendor_company' => $vendor->company_name,
                'approved_by' => $officer->full_name,
                'officer_id' => $request->bppa_officer_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Vendor approved successfully',
                'data' => [
                    'vendor_id' => $vendorId,
                    'company_name' => $vendor->company_name,
                    'approved_by' => $officer->full_name,
                    'approved_at' => $vendor->approved_at
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to approve vendor', [
                'vendor_id' => $vendorId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve vendor'
            ], 500);
        }
    }

    /**
     * Get BPPA dashboard statistics
     */
    public function getDashboardStats(): JsonResponse
    {
        try {
            $stats = [
                'procurements' => [
                    'total' => Procurement::count(),
                    'active' => Procurement::whereIn('status', ['open', 'voting'])->count(),
                    'completed' => Procurement::where('status', 'completed')->count(),
                    'total_value' => Procurement::sum('estimated_value'),
                ],
                'vendors' => [
                    'total' => Vendor::count(),
                    'approved' => Vendor::where('is_approved', true)->count(),
                    'pending' => Vendor::where('is_approved', false)->orWhereNull('approved_by')->count(),
                ],
                'fund_requests' => [
                    'total' => 67, // Mock blockchain data
                    'pending' => 8,
                    'approved' => 45,
                    'rejected' => 12,
                    'total_amount_requested' => 185000000,
                    'total_amount_approved' => 142000000,
                ],
                'recent_activities' => [
                    [
                        'type' => 'fund_request',
                        'description' => 'New fund request from ABC Construction Ltd.',
                        'amount' => 1500000,
                        'timestamp' => now()->subHours(2)->toISOString()
                    ],
                    [
                        'type' => 'vendor_approval',
                        'description' => 'Approved vendor: National Builders Corp.',
                        'timestamp' => now()->subHours(5)->toISOString()
                    ],
                    [
                        'type' => 'procurement',
                        'description' => 'Created new procurement: Digital Infrastructure Project',
                        'value' => 50000000,
                        'timestamp' => now()->subDays(1)->toISOString()
                    ]
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get BPPA dashboard stats', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard statistics'
            ], 500);
        }
    }
}
