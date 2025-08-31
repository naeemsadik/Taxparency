<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Fund Request Controller
 * 
 * Handles all Fund Request related operations including:
 * - Vendor fund request submissions
 * - BPPA officer approvals/rejections
 * - Fund request tracking and statistics
 */
class FundRequestController extends Controller
{
    /**
     * Submit a new fund request (Vendors only)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function submitFundRequest(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'procurement_id' => 'required|string|max:100',
                'vendor_id' => 'required|string|max:100',
                'company_name' => 'required|string|max:200',
                'requested_amount' => 'required|numeric|min:1',
                'reason' => 'required|string|max:500',
                'justification' => 'required|string|max:2000',
                'supporting_docs' => 'nullable|string|max:100' // IPFS hash
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Generate unique request ID
            $requestId = 'FR-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            // In a real implementation, this would interact with the blockchain
            $fundRequest = [
                'request_id' => $requestId,
                'procurement_id' => $request->procurement_id,
                'vendor_id' => $request->vendor_id,
                'company_name' => $request->company_name,
                'requested_amount' => $request->requested_amount,
                'reason' => $request->reason,
                'justification' => $request->justification,
                'supporting_docs' => $request->supporting_docs ?? null,
                'submitted_timestamp' => now()->toISOString(),
                'status' => 'Pending',
                'blockchain_tx_hash' => '0x' . bin2hex(random_bytes(32)) // Mock transaction hash
            ];

            Log::info('Fund request submitted', $fundRequest);

            return response()->json([
                'success' => true,
                'data' => $fundRequest,
                'message' => 'Fund request submitted successfully'
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to submit fund request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit fund request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve fund request (BPPA officers only)
     *
     * @param Request $request
     * @param string $requestId
     * @return JsonResponse
     */
    public function approveFundRequest(Request $request, string $requestId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'approved_amount' => 'required|numeric|min:1',
                'bppa_comments' => 'required|string|max:1000',
                'bppa_officer_id' => 'required|string|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // In a real implementation, this would interact with the blockchain
            $approval = [
                'request_id' => $requestId,
                'status' => 'Approved',
                'approved_amount' => $request->approved_amount,
                'bppa_comments' => $request->bppa_comments,
                'bppa_officer_id' => $request->bppa_officer_id,
                'reviewed_timestamp' => now()->toISOString(),
                'blockchain_tx_hash' => '0x' . bin2hex(random_bytes(32)) // Mock transaction hash
            ];

            Log::info('Fund request approved', $approval);

            return response()->json([
                'success' => true,
                'data' => $approval,
                'message' => 'Fund request approved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to approve fund request', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve fund request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject fund request (BPPA officers only)
     *
     * @param Request $request
     * @param string $requestId
     * @return JsonResponse
     */
    public function rejectFundRequest(Request $request, string $requestId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'bppa_comments' => 'required|string|max:1000',
                'bppa_officer_id' => 'required|string|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // In a real implementation, this would interact with the blockchain
            $rejection = [
                'request_id' => $requestId,
                'status' => 'Rejected',
                'bppa_comments' => $request->bppa_comments,
                'bppa_officer_id' => $request->bppa_officer_id,
                'reviewed_timestamp' => now()->toISOString(),
                'blockchain_tx_hash' => '0x' . bin2hex(random_bytes(32)) // Mock transaction hash
            ];

            Log::info('Fund request rejected', $rejection);

            return response()->json([
                'success' => true,
                'data' => $rejection,
                'message' => 'Fund request rejected successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to reject fund request', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reject fund request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get fund request details
     *
     * @param string $requestId
     * @return JsonResponse
     */
    public function getFundRequest(string $requestId): JsonResponse
    {
        try {
            // Mock data - in real implementation, query blockchain
            $fundRequest = [
                'request_id' => $requestId,
                'procurement_id' => 'PROC-2024-001',
                'vendor_id' => 'ABC-CONST-001',
                'company_name' => 'ABC Construction Ltd.',
                'requested_amount' => 2500000, // 25 Lakh BDT
                'reason' => 'Additional excavation required',
                'justification' => 'Due to unexpected soil conditions discovered during excavation, additional work is required to ensure foundation stability. This includes additional concrete and steel reinforcement.',
                'supporting_docs' => 'QmX1Y2Z3ABC...',
                'submitted_timestamp' => now()->subDays(3)->toISOString(),
                'reviewed_timestamp' => now()->subDays(1)->toISOString(),
                'status' => 'Approved',
                'approved_amount' => 2200000, // 22 Lakh BDT
                'bppa_officer_id' => 'BPPA-001',
                'bppa_officer_name' => 'Mr. Rafiqul Islam',
                'bppa_comments' => 'Approved with reduced amount. Additional documentation required for soil test reports.',
                'disbursement_ref' => null,
                'blockchain_tx_hash' => '0x' . bin2hex(random_bytes(32))
            ];

            return response()->json([
                'success' => true,
                'data' => $fundRequest,
                'message' => 'Fund request details retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get fund request details', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve fund request details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get fund requests by procurement ID
     *
     * @param string $procurementId
     * @return JsonResponse
     */
    public function getFundRequestsByProcurement(string $procurementId): JsonResponse
    {
        try {
            // Mock data - in real implementation, query blockchain
            $fundRequests = [
                [
                    'request_id' => 'FR-2024-0001',
                    'vendor_id' => 'ABC-CONST-001',
                    'company_name' => 'ABC Construction Ltd.',
                    'requested_amount' => 2500000,
                    'approved_amount' => 2200000,
                    'status' => 'Approved',
                    'reason' => 'Additional excavation required',
                    'submitted_timestamp' => now()->subDays(5)->toISOString(),
                    'reviewed_timestamp' => now()->subDays(2)->toISOString()
                ],
                [
                    'request_id' => 'FR-2024-0002',
                    'vendor_id' => 'ABC-CONST-001',
                    'company_name' => 'ABC Construction Ltd.',
                    'requested_amount' => 1500000,
                    'approved_amount' => 0,
                    'status' => 'Pending',
                    'reason' => 'Material cost escalation',
                    'submitted_timestamp' => now()->subDays(1)->toISOString(),
                    'reviewed_timestamp' => null
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'procurement_id' => $procurementId,
                    'total_requests' => count($fundRequests),
                    'total_requested_amount' => array_sum(array_column($fundRequests, 'requested_amount')),
                    'total_approved_amount' => array_sum(array_column($fundRequests, 'approved_amount')),
                    'requests' => $fundRequests
                ],
                'message' => 'Fund requests for procurement retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get fund requests by procurement', [
                'procurement_id' => $procurementId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve fund requests for procurement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get fund requests by vendor ID
     *
     * @param string $vendorId
     * @return JsonResponse
     */
    public function getFundRequestsByVendor(string $vendorId): JsonResponse
    {
        try {
            // Mock data - in real implementation, query blockchain
            $fundRequests = [
                [
                    'request_id' => 'FR-2024-0001',
                    'procurement_id' => 'PROC-2024-001',
                    'procurement_title' => 'Dhaka-Chittagong Highway Extension',
                    'requested_amount' => 2500000,
                    'approved_amount' => 2200000,
                    'status' => 'Approved',
                    'reason' => 'Additional excavation required',
                    'submitted_timestamp' => now()->subDays(5)->toISOString(),
                    'reviewed_timestamp' => now()->subDays(2)->toISOString()
                ],
                [
                    'request_id' => 'FR-2024-0002',
                    'procurement_id' => 'PROC-2024-001',
                    'procurement_title' => 'Dhaka-Chittagong Highway Extension',
                    'requested_amount' => 1500000,
                    'approved_amount' => 0,
                    'status' => 'Pending',
                    'reason' => 'Material cost escalation',
                    'submitted_timestamp' => now()->subDays(1)->toISOString(),
                    'reviewed_timestamp' => null
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'vendor_id' => $vendorId,
                    'total_requests' => count($fundRequests),
                    'total_requested_amount' => array_sum(array_column($fundRequests, 'requested_amount')),
                    'total_approved_amount' => array_sum(array_column($fundRequests, 'approved_amount')),
                    'requests' => $fundRequests
                ],
                'message' => 'Fund requests for vendor retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get fund requests by vendor', [
                'vendor_id' => $vendorId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve fund requests for vendor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pending fund requests (BPPA officers)
     *
     * @return JsonResponse
     */
    public function getPendingFundRequests(): JsonResponse
    {
        try {
            // Mock data - in real implementation, query blockchain
            $pendingRequests = [
                [
                    'request_id' => 'FR-2024-0002',
                    'procurement_id' => 'PROC-2024-001',
                    'procurement_title' => 'Dhaka-Chittagong Highway Extension',
                    'vendor_id' => 'ABC-CONST-001',
                    'company_name' => 'ABC Construction Ltd.',
                    'requested_amount' => 1500000,
                    'reason' => 'Material cost escalation',
                    'submitted_timestamp' => now()->subDays(1)->toISOString(),
                    'days_pending' => 1
                ],
                [
                    'request_id' => 'FR-2024-0003',
                    'procurement_id' => 'PROC-2024-015',
                    'procurement_title' => 'Rural Road Development Project',
                    'vendor_id' => 'XYZ-INFRA-002',
                    'company_name' => 'XYZ Infrastructure Pvt.',
                    'requested_amount' => 800000,
                    'reason' => 'Bridge foundation reinforcement',
                    'submitted_timestamp' => now()->subDays(3)->toISOString(),
                    'days_pending' => 3
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'total_pending' => count($pendingRequests),
                    'total_pending_amount' => array_sum(array_column($pendingRequests, 'requested_amount')),
                    'requests' => $pendingRequests
                ],
                'message' => 'Pending fund requests retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get pending fund requests', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve pending fund requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get fund request statistics
     *
     * @return JsonResponse
     */
    public function getStatistics(): JsonResponse
    {
        try {
            // Mock data - in real implementation, query blockchain
            $statistics = [
                'total_requests' => 67,
                'pending_requests' => 8,
                'approved_requests' => 45,
                'rejected_requests' => 12,
                'funded_requests' => 40,
                'total_requested_amount' => 185000000, // 18.5 Crore BDT
                'total_approved_amount' => 142000000,  // 14.2 Crore BDT
                'approval_rate' => 75.0, // percentage
                'average_approval_time_days' => 3.2,
                'monthly_stats' => [
                    'current_month' => [
                        'requests_submitted' => 12,
                        'requests_approved' => 8,
                        'requests_rejected' => 2,
                        'amount_approved' => 15500000
                    ],
                    'last_month' => [
                        'requests_submitted' => 18,
                        'requests_approved' => 14,
                        'requests_rejected' => 3,
                        'amount_approved' => 22800000
                    ]
                ],
                'top_procurement_categories' => [
                    ['category' => 'Infrastructure', 'requests' => 35, 'amount' => 95000000],
                    ['category' => 'IT & Technology', 'requests' => 18, 'amount' => 28000000],
                    ['category' => 'Construction', 'requests' => 14, 'amount' => 19000000]
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $statistics,
                'message' => 'Fund request statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get fund request statistics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve fund request statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark funds as disbursed (BPPA officers/Admin)
     *
     * @param Request $request
     * @param string $requestId
     * @return JsonResponse
     */
    public function markFundsDisbursed(Request $request, string $requestId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'disbursement_ref' => 'required|string|max:100',
                'disbursed_amount' => 'required|numeric|min:1',
                'notes' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // In a real implementation, this would interact with the blockchain
            $disbursement = [
                'request_id' => $requestId,
                'status' => 'Funded',
                'disbursement_ref' => $request->disbursement_ref,
                'disbursed_amount' => $request->disbursed_amount,
                'notes' => $request->notes ?? null,
                'disbursed_timestamp' => now()->toISOString(),
                'blockchain_tx_hash' => '0x' . bin2hex(random_bytes(32))
            ];

            Log::info('Fund request marked as disbursed', $disbursement);

            return response()->json([
                'success' => true,
                'data' => $disbursement,
                'message' => 'Fund disbursement recorded successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to mark funds as disbursed', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to record fund disbursement',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
