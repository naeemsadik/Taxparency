<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\CitizenAuthController;
use App\Http\Controllers\Auth\NbrAuthController;
use App\Http\Controllers\Auth\VendorAuthController;
use App\Http\Controllers\Auth\BppaAuthController;
use App\Http\Controllers\TaxReturnController;
use App\Http\Controllers\ProcurementController;
use App\Http\Controllers\CitizenDashboardController;
use App\Http\Controllers\NationalLedgerController;
use App\Http\Controllers\FundRequestController;
use App\Http\Controllers\WinningBidController;
use App\Http\Controllers\BidController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes (no authentication required)
Route::prefix('v1')->group(function () {
    
    // Citizen Authentication
    Route::prefix('citizen')->group(function () {
        Route::post('register', [CitizenAuthController::class, 'register']);
        Route::post('login', [CitizenAuthController::class, 'login']);
    });

    // NBR Officer Authentication
    Route::prefix('nbr')->group(function () {
        Route::post('login', [NbrAuthController::class, 'login']);
    });

    // Vendor Authentication
    Route::prefix('vendor')->group(function () {
        Route::post('register', [VendorAuthController::class, 'register']);
        Route::post('login', [VendorAuthController::class, 'login']);
    });

    // BPPA Officer Authentication
    Route::prefix('bppa')->group(function () {
        Route::post('login', [BppaAuthController::class, 'login']);
    });

    // Public procurement data (for transparency)
    Route::prefix('public')->group(function () {
        Route::get('procurements/active', [ProcurementController::class, 'getActiveProcurements']);
        Route::get('procurements/{id}', [ProcurementController::class, 'getProcurementDetails']);
        Route::get('statistics', [TaxReturnController::class, 'getStatistics']);
        
        // National Ledger public transparency endpoints
        Route::prefix('national-ledger')->group(function () {
            Route::get('summary', [NationalLedgerController::class, 'getLedgerSummary']);
            Route::get('statistics', [NationalLedgerController::class, 'getPublicStatistics']);
            Route::get('revenue/entries', [NationalLedgerController::class, 'getRevenueEntries']);
            Route::get('expense/entries', [NationalLedgerController::class, 'getExpenseEntries']);
            Route::get('revenue/fiscal-year', [NationalLedgerController::class, 'getRevenueByFiscalYear']);
            Route::get('expense/procurement/{procurementId}', [NationalLedgerController::class, 'getExpenseByProcurement']);
        });
        
        // Public Winning Bids transparency endpoints
        Route::prefix('winning-bids')->group(function () {
            Route::get('', [WinningBidController::class, 'index']);
            Route::get('{id}', [WinningBidController::class, 'show']);
            Route::get('statistics', [WinningBidController::class, 'statistics']);
            Route::get('vendor/{vendorId}', [WinningBidController::class, 'byVendor']);
        });
    });
});

// Protected routes (for demo - no authentication required)
Route::prefix('v1')->group(function () {
    
    // Citizen routes (with session middleware)
    Route::prefix('citizen')->middleware(['web'])->group(function () {
        Route::post('logout', [CitizenAuthController::class, 'logout']);
        Route::get('profile', [CitizenAuthController::class, 'profile']);
        
        // Dashboard
        Route::get('dashboard/stats', [CitizenDashboardController::class, 'getDashboardStats']);
        
        // Tax returns
        Route::prefix('tax-returns')->group(function () {
            Route::post('submit', [TaxReturnController::class, 'submit'])->name('api.tax-returns.submit');
            Route::get('my-returns/{citizenId}', [TaxReturnController::class, 'getCitizenReturns']);
            Route::get('details/{id}', [TaxReturnController::class, 'getReturnDetails']);
        });
        
        // Procurement Voting
        Route::prefix('procurements')->group(function () {
            Route::get('active', [CitizenDashboardController::class, 'getActiveProcurements']);
            Route::get('{id}/details', [CitizenDashboardController::class, 'getProcurementDetails']);
            Route::get('bids/{bid_id}/details', [CitizenDashboardController::class, 'getBidDetails']);
            Route::post('vote', [CitizenDashboardController::class, 'castVote']);
            Route::get('my-votes', [CitizenDashboardController::class, 'getMyVotes']);
        });
        
        // National Ledger queries for citizens
        Route::prefix('national-ledger')->group(function () {
            Route::get('summary', [NationalLedgerController::class, 'getLedgerSummary']);
            Route::get('revenue/fiscal-year', [NationalLedgerController::class, 'getRevenueByFiscalYear']);
        });
    });

    // NBR Officer routes
    Route::prefix('nbr')->group(function () {
        Route::post('logout', [NbrAuthController::class, 'logout']);
        Route::get('profile', [NbrAuthController::class, 'profile']);
        
        // Tax return review
        Route::prefix('tax-returns')->group(function () {
            Route::get('pending', [TaxReturnController::class, 'getPendingReturns']);
            Route::post('review/{id}', [TaxReturnController::class, 'reviewReturn']);
            Route::get('details/{id}', [TaxReturnController::class, 'getReturnDetails']);
        });
    });

    // Vendor routes (with session middleware)
    Route::prefix('vendor')->middleware(['web'])->group(function () {
        Route::post('logout', [VendorAuthController::class, 'logout']);
        Route::get('profile', [VendorAuthController::class, 'profile']);
        
        // Bidding
        Route::prefix('bids')->group(function () {
            Route::post('submit', [BidController::class, 'submitBid']);
            Route::put('{bidId}/edit', [BidController::class, 'editBid']);
            Route::get('{bidId}/details', [BidController::class, 'getBidDetails']);
            Route::get('my-bids/{vendorId}', [BidController::class, 'getVendorBids']);
        });
        
        Route::get('procurements/open', [BidController::class, 'getOpenProcurements']);
        Route::get('winning-bids/{vendorId}', [BidController::class, 'getWinningBids']);
        
        // Fund Requests
        Route::prefix('fund-requests')->middleware(['web'])->group(function () {
            Route::post('submit', [FundRequestController::class, 'submitFundRequest']);
            Route::get('vendor/{vendorId}', [FundRequestController::class, 'getFundRequestsByVendor']);
            Route::get('{requestId}', [FundRequestController::class, 'getFundRequest']);
        });
        
        // Winning Bids (vendor-specific)
        Route::prefix('winning-bids')->middleware(['web'])->group(function () {
            Route::get('my-wins/{vendorId}', [WinningBidController::class, 'getVendorWinningBids']);
            Route::get('{id}/details', [WinningBidController::class, 'getWinningBidDetails']);
        });
    });

    // BPPA Officer routes (with session middleware)
    Route::prefix('bppa')->middleware(['web'])->group(function () {
        Route::post('logout', [BppaAuthController::class, 'logout']);
        Route::get('profile', [BppaAuthController::class, 'profile']);
        Route::get('dashboard/stats', [BppaAuthController::class, 'getDashboardStats']);
        
        // Procurement management
        Route::prefix('procurements')->group(function () {
            Route::post('create', [ProcurementController::class, 'create']);
            Route::get('{id}/details', [ProcurementController::class, 'getProcurementDetails']);
            Route::get('{id}/bids', [ProcurementController::class, 'getProcurementBids']);
            Route::post('shortlist-bids', [ProcurementController::class, 'shortlistBids']);
            Route::post('{id}/start-voting', [ProcurementController::class, 'startVoting']);
            Route::post('{id}/complete-voting', [ProcurementController::class, 'completeVoting']);
            Route::get('my-procurements/{officerId}', [BppaAuthController::class, 'getMyProcurements']);
        });
        
        // Vendor management
        Route::prefix('vendors')->group(function () {
            Route::get('pending', [BppaAuthController::class, 'getPendingVendors']);
            Route::post('approve/{id}', [BppaAuthController::class, 'approveVendor']);
        });
        
        // Fund Request Management
        Route::prefix('fund-requests')->group(function () {
            Route::get('pending', [FundRequestController::class, 'getPendingFundRequests']);
            Route::get('statistics', [FundRequestController::class, 'getStatistics']);
            Route::get('procurement/{procurementId}', [FundRequestController::class, 'getFundRequestsByProcurement']);
            Route::post('{requestId}/approve', [FundRequestController::class, 'approveFundRequest']);
            Route::post('{requestId}/reject', [FundRequestController::class, 'rejectFundRequest']);
            Route::post('{requestId}/disburse', [FundRequestController::class, 'markFundsDisbursed']);
            Route::get('{requestId}', [FundRequestController::class, 'getFundRequest']);
        });
        
        // National Ledger management
        Route::prefix('national-ledger')->group(function () {
            Route::get('summary', [NationalLedgerController::class, 'getLedgerSummary']);
            Route::get('revenue/entries', [NationalLedgerController::class, 'getRevenueEntries']);
            Route::get('expense/entries', [NationalLedgerController::class, 'getExpenseEntries']);
            Route::get('expense/procurement/{procurementId}', [NationalLedgerController::class, 'getExpenseByProcurement']);
        });
        
        // Winning Bids Management
        Route::prefix('winning-bids')->group(function () {
            Route::get('', [WinningBidController::class, 'index']);
            Route::get('{id}', [WinningBidController::class, 'show']);
            Route::get('statistics', [WinningBidController::class, 'statistics']);
            Route::post('{id}/update-status', [WinningBidController::class, 'updateContractStatus']);
            Route::get('blockchain-status', [WinningBidController::class, 'blockchainStatus']);
            Route::post('sync-blockchain', [WinningBidController::class, 'syncToBlockchain']);
        });
    });
});

// Fallback route for API documentation
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found',
        'available_endpoints' => [
            'Authentication' => [
                'POST /api/v1/citizen/login',
                'POST /api/v1/citizen/register',
                'POST /api/v1/nbr/login',
                'POST /api/v1/vendor/login',
                'POST /api/v1/vendor/register',
                'POST /api/v1/bppa/login',
            ],
            'Tax Returns' => [
                'POST /api/v1/citizen/tax-returns/submit',
                'GET /api/v1/citizen/tax-returns/my-returns/{citizenId}',
                'GET /api/v1/nbr/tax-returns/pending',
                'POST /api/v1/nbr/tax-returns/review/{id}',
            ],
            'Procurements' => [
                'POST /api/v1/bppa/procurements/create',
                'POST /api/v1/vendor/bids/submit',
                'GET /api/v1/citizen/procurements/active',
                'GET /api/v1/citizen/procurements/{id}/details',
                'GET /api/v1/citizen/procurements/bids/{bid_id}/details',
                'POST /api/v1/citizen/procurements/vote',
                'GET /api/v1/citizen/procurements/my-votes',
                'GET /api/v1/public/procurements/active',
            ],
            'Public Data' => [
                'GET /api/v1/public/statistics',
                'GET /api/v1/public/procurements/{id}',
            ],
            'Winning Bids' => [
                'GET /api/v1/public/winning-bids',
                'GET /api/v1/public/winning-bids/{id}',
                'GET /api/v1/public/winning-bids/statistics',
                'GET /api/v1/public/winning-bids/vendor/{vendorId}',
                'GET /api/v1/bppa/winning-bids/blockchain-status',
                'POST /api/v1/bppa/winning-bids/{id}/update-status',
            ]
        ]
    ], 404);
});
