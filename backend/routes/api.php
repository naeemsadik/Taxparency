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
    });
});

// Protected routes (for demo - no authentication required)
Route::prefix('v1')->group(function () {
    
    // Citizen routes
    Route::prefix('citizen')->group(function () {
        Route::post('logout', [CitizenAuthController::class, 'logout']);
        Route::get('profile', [CitizenAuthController::class, 'profile']);
        
        // Tax returns
        Route::prefix('tax-returns')->group(function () {
            Route::post('submit', [TaxReturnController::class, 'submit']);
            Route::get('my-returns/{citizenId}', [TaxReturnController::class, 'getCitizenReturns']);
            Route::get('details/{id}', [TaxReturnController::class, 'getReturnDetails']);
        });
        
        // Voting
        Route::post('vote', [ProcurementController::class, 'castVote']);
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

    // Vendor routes
    Route::prefix('vendor')->group(function () {
        Route::post('logout', [VendorAuthController::class, 'logout']);
        Route::get('profile', [VendorAuthController::class, 'profile']);
        
        // Bidding
        Route::prefix('bids')->group(function () {
            Route::post('submit', [ProcurementController::class, 'submitBid']);
            Route::get('my-bids/{vendorId}', [VendorAuthController::class, 'getMyBids']);
        });
        
        Route::get('procurements/open', [VendorAuthController::class, 'getOpenProcurements']);
    });

    // BPPA Officer routes
    Route::prefix('bppa')->group(function () {
        Route::post('logout', [BppaAuthController::class, 'logout']);
        Route::get('profile', [BppaAuthController::class, 'profile']);
        
        // Procurement management
        Route::prefix('procurements')->group(function () {
            Route::post('create', [ProcurementController::class, 'create']);
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
                'POST /api/v1/citizen/vote',
                'GET /api/v1/public/procurements/active',
            ],
            'Public Data' => [
                'GET /api/v1/public/statistics',
                'GET /api/v1/public/procurements/{id}',
            ]
        ]
    ], 404);
});
