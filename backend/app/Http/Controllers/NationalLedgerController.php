<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * National Ledger Controller
 * 
 * Handles all National Ledger related operations including:
 * - Revenue and expense queries
 * - Ledger summary statistics
 * - Public transparency data
 */
class NationalLedgerController extends Controller
{
    /**
     * Get national ledger summary
     *
     * @return JsonResponse
     */
    public function getLedgerSummary(): JsonResponse
    {
        try {
            // In a real implementation, this would interact with the blockchain
            // For now, returning mock data that matches the PRD requirements
            
            $summary = [
                'totalRevenue' => 1250000000, // 125 Crore BDT
                'totalExpense' => 750000000,  // 75 Crore BDT
                'availableBalance' => 500000000, // 50 Crore BDT
                'totalRevenueEntries' => 15643,
                'totalExpenseEntries' => 1247,
                'lastUpdated' => now()->toISOString(),
            ];

            return response()->json([
                'success' => true,
                'data' => $summary,
                'message' => 'National ledger summary retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get national ledger summary', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve national ledger summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get revenue entries with pagination
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getRevenueEntries(Request $request): JsonResponse
    {
        try {
            $fiscalYear = $request->get('fiscal_year');
            $page = (int) $request->get('page', 1);
            $limit = (int) $request->get('limit', 10);

            // Mock data - in real implementation, query blockchain
            $revenueEntries = [
                [
                    'tiin' => '123456789012',
                    'amount' => 150000,
                    'fiscalYear' => '2024-25',
                    'timestamp' => now()->subDays(1)->toISOString(),
                    'validator' => '0x742d35Cc1234567890123456789012345678Ab',
                    'validatorName' => 'Dr. Abdul Karim',
                    'taxReturnHash' => 'QmX1Y2Z3...'
                ],
                [
                    'tiin' => '987654321098',
                    'amount' => 280000,
                    'fiscalYear' => '2024-25',
                    'timestamp' => now()->subDays(2)->toISOString(),
                    'validator' => '0x742d35Cc1234567890123456789012345678Ab',
                    'validatorName' => 'Dr. Abdul Karim',
                    'taxReturnHash' => 'QmA2B3C4...'
                ]
            ];

            // Filter by fiscal year if provided
            if ($fiscalYear) {
                $revenueEntries = array_filter($revenueEntries, function ($entry) use ($fiscalYear) {
                    return $entry['fiscalYear'] === $fiscalYear;
                });
            }

            $totalEntries = count($revenueEntries);
            $paginatedEntries = array_slice($revenueEntries, ($page - 1) * $limit, $limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'entries' => $paginatedEntries,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $limit,
                        'total' => $totalEntries,
                        'total_pages' => ceil($totalEntries / $limit)
                    ]
                ],
                'message' => 'Revenue entries retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get revenue entries', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve revenue entries',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get expense entries with pagination
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getExpenseEntries(Request $request): JsonResponse
    {
        try {
            $procurementId = $request->get('procurement_id');
            $page = (int) $request->get('page', 1);
            $limit = (int) $request->get('limit', 10);

            // Mock data - in real implementation, query blockchain
            $expenseEntries = [
                [
                    'procurementId' => 'PROC-2024-001',
                    'amount' => 25000000, // 2.5 Crore BDT
                    'timestamp' => now()->subDays(5)->toISOString(),
                    'bppaOfficer' => '0x123d35Cc1234567890123456789012345678Cd',
                    'bppaOfficerName' => 'Mr. Rafiqul Islam',
                    'vendorId' => 'ABC-CONST-001',
                    'vendorName' => 'ABC Construction Ltd.',
                    'description' => 'Dhaka-Chittagong Highway Extension Project',
                    'isFundRequest' => false
                ],
                [
                    'procurementId' => 'PROC-2024-001',
                    'amount' => 2500000, // 25 Lakh BDT (additional fund)
                    'timestamp' => now()->subDays(2)->toISOString(),
                    'bppaOfficer' => '0x123d35Cc1234567890123456789012345678Cd',
                    'bppaOfficerName' => 'Mr. Rafiqul Islam',
                    'vendorId' => 'ABC-CONST-001',
                    'vendorName' => 'ABC Construction Ltd.',
                    'description' => 'Additional funds for unexpected soil condition',
                    'isFundRequest' => true
                ]
            ];

            // Filter by procurement ID if provided
            if ($procurementId) {
                $expenseEntries = array_filter($expenseEntries, function ($entry) use ($procurementId) {
                    return $entry['procurementId'] === $procurementId;
                });
            }

            $totalEntries = count($expenseEntries);
            $paginatedEntries = array_slice($expenseEntries, ($page - 1) * $limit, $limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'entries' => $paginatedEntries,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $limit,
                        'total' => $totalEntries,
                        'total_pages' => ceil($totalEntries / $limit)
                    ]
                ],
                'message' => 'Expense entries retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get expense entries', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve expense entries',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get revenue by fiscal year
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getRevenueByFiscalYear(Request $request): JsonResponse
    {
        try {
            $fiscalYear = $request->get('fiscal_year', '2024-25');

            // Mock data - in real implementation, query blockchain
            $revenueData = [
                'fiscal_year' => $fiscalYear,
                'total_revenue' => 450000000, // 45 Crore BDT
                'total_entries' => 8742,
                'monthly_breakdown' => [
                    'April' => 38000000,
                    'May' => 42000000,
                    'June' => 39000000,
                    'July' => 45000000,
                    'August' => 41000000,
                    'September' => 38000000,
                    'October' => 42000000,
                    'November' => 44000000,
                    'December' => 46000000,
                    'January' => 36000000,
                    'February' => 39000000,
                    'March' => 0 // Current month
                ],
                'top_districts' => [
                    ['name' => 'Dhaka', 'amount' => 120000000],
                    ['name' => 'Chittagong', 'amount' => 85000000],
                    ['name' => 'Sylhet', 'amount' => 45000000],
                    ['name' => 'Rajshahi', 'amount' => 38000000],
                    ['name' => 'Khulna', 'amount' => 35000000]
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $revenueData,
                'message' => 'Revenue data by fiscal year retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get revenue by fiscal year', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve revenue data by fiscal year',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get expense by procurement
     *
     * @param string $procurementId
     * @return JsonResponse
     */
    public function getExpenseByProcurement(string $procurementId): JsonResponse
    {
        try {
            // Mock data - in real implementation, query blockchain
            $expenseData = [
                'procurement_id' => $procurementId,
                'total_expense' => 27500000, // 2.75 Crore BDT
                'original_budget' => 25000000, // 2.5 Crore BDT
                'additional_funds' => 2500000, // 25 Lakh BDT
                'total_entries' => 2,
                'breakdown' => [
                    [
                        'type' => 'Initial Award',
                        'amount' => 25000000,
                        'date' => now()->subDays(30)->toDateString(),
                        'description' => 'Initial procurement award'
                    ],
                    [
                        'type' => 'Additional Fund Request',
                        'amount' => 2500000,
                        'date' => now()->subDays(5)->toDateString(),
                        'description' => 'Additional funds for unexpected soil condition'
                    ]
                ],
                'vendor_info' => [
                    'vendor_id' => 'ABC-CONST-001',
                    'company_name' => 'ABC Construction Ltd.',
                    'project_status' => 'In Progress'
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $expenseData,
                'message' => 'Expense data by procurement retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get expense by procurement', [
                'procurement_id' => $procurementId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve expense data by procurement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get public transparency statistics
     *
     * @return JsonResponse
     */
    public function getPublicStatistics(): JsonResponse
    {
        try {
            // Mock data for public transparency - in real implementation, query blockchain
            $publicStats = [
                'current_fiscal_year' => '2024-25',
                'total_national_revenue' => 1250000000, // 125 Crore BDT
                'total_national_expense' => 750000000,  // 75 Crore BDT
                'available_balance' => 500000000, // 50 Crore BDT
                'transparency_metrics' => [
                    'total_tax_returns_processed' => 15643,
                    'approved_tax_returns' => 14896,
                    'total_procurements' => 247,
                    'completed_procurements' => 189,
                    'active_procurements' => 23,
                    'citizen_votes_cast' => 8934,
                    'total_fund_requests' => 67,
                    'approved_fund_requests' => 45
                ],
                'recent_activity' => [
                    [
                        'type' => 'tax_return_approved',
                        'description' => 'Tax return approved for TIIN: 123456789012',
                        'amount' => 150000,
                        'timestamp' => now()->subHours(2)->toISOString()
                    ],
                    [
                        'type' => 'procurement_awarded',
                        'description' => 'Procurement PROC-2024-045 awarded to XYZ Infrastructure',
                        'amount' => 18500000,
                        'timestamp' => now()->subHours(6)->toISOString()
                    ],
                    [
                        'type' => 'fund_request_approved',
                        'description' => 'Additional fund request approved for PROC-2024-001',
                        'amount' => 2500000,
                        'timestamp' => now()->subDays(1)->toISOString()
                    ]
                ],
                'last_updated' => now()->toISOString()
            ];

            return response()->json([
                'success' => true,
                'data' => $publicStats,
                'message' => 'Public transparency statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get public statistics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve public statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show public national ledger blade template
     *
     * @return \Illuminate\View\View
     */
    public function showPublicLedger()
    {
        try {
            // Get data for the blade template
            $summary = [
                'total_revenue' => '125.00',
                'total_expense' => '75.00',
                'available_balance' => '50.00',
                'transparency_score' => '98.5'
            ];

            $revenueEntries = [
                [
                    'tiin' => '123456789012',
                    'amount' => 150000,
                    'fiscal_year' => '2024-25',
                    'validator_name' => 'Dr. Abdul Karim (NBR)',
                    'created_at' => now()->subDays(1),
                    'blockchain_hash' => '0x7d2f...a8c9'
                ],
                [
                    'tiin' => '987654321098',
                    'amount' => 280000,
                    'fiscal_year' => '2024-25',
                    'validator_name' => 'Dr. Abdul Karim (NBR)',
                    'created_at' => now()->subDays(2),
                    'blockchain_hash' => '0x9f3a...b7d2'
                ]
            ];

            $expenseEntries = [
                [
                    'procurement_id' => 'PROC-2024-045',
                    'amount' => 18500000,
                    'vendor_name' => 'XYZ Infrastructure Pvt.',
                    'type' => 'Initial Award',
                    'approved_by' => 'Mr. Rafiqul Islam (BPPA)',
                    'created_at' => now()->subDays(1),
                    'blockchain_hash' => '0x8a7b...c9d2'
                ]
            ];

            $analytics = [
                'tax_returns_processed' => '15,643',
                'total_procurements' => '247',
                'citizen_votes_cast' => '8,934',
                'fund_requests' => '67'
            ];

            $recentActivity = [
                [
                    'icon' => '💰',
                    'title' => 'Tax Return Approved',
                    'description' => 'Tax return approved for TIIN: 123456789012',
                    'details' => 'Amount: ৳1,50,000 | Verified by: Dr. Abdul Karim (NBR)',
                    'time' => '2 hours ago',
                    'hash' => '0x7d2f...a8c9',
                    'color' => '#28a745'
                ],
                [
                    'icon' => '🏗️',
                    'title' => 'Procurement Awarded',
                    'description' => 'Procurement PROC-2024-045 awarded to XYZ Infrastructure',
                    'details' => 'Amount: ৳1.85 Crore | Approved by: Mr. Rafiqul Islam (BPPA)',
                    'time' => '6 hours ago',
                    'hash' => '0x8a7b...c9d2',
                    'color' => '#dc3545'
                ]
            ];

            return view('national-ledger', compact('summary', 'revenueEntries', 'expenseEntries', 'analytics', 'recentActivity'));

        } catch (\Exception $e) {
            Log::error('Failed to show public ledger', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return with empty data on error
            return view('national-ledger', [
                'summary' => [],
                'revenueEntries' => [],
                'expenseEntries' => [],
                'analytics' => [],
                'recentActivity' => []
            ]);
        }
    }
}
