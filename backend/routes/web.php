<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NationalLedgerController;

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/national-ledger', [NationalLedgerController::class, 'showPublicLedger'])->name('national-ledger');

// Authentication routes
Route::prefix('login')->name('login.')->group(function () {
    Route::get('/citizen', [AuthController::class, 'showCitizenLogin'])->name('citizen');
    Route::post('/citizen', [AuthController::class, 'citizenLogin'])->name('citizen.submit');
    
    Route::get('/nbr', [AuthController::class, 'showNbrLogin'])->name('nbr');
    Route::post('/nbr', [AuthController::class, 'nbrLogin'])->name('nbr.submit');
    
    Route::get('/vendor', [AuthController::class, 'showVendorLogin'])->name('vendor');
    Route::post('/vendor', [AuthController::class, 'vendorLogin'])->name('vendor.submit');
    
    Route::get('/bppa', [AuthController::class, 'showBppaLogin'])->name('bppa');
    Route::post('/bppa', [AuthController::class, 'bppaLogin'])->name('bppa.submit');
});

// Logout route
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected dashboard routes (with session-based authentication)
Route::middleware(['web.auth'])->group(function () {
    Route::get('/citizen/dashboard', [DashboardController::class, 'citizenDashboard'])->name('citizen.dashboard');
    Route::get('/nbr/dashboard', [DashboardController::class, 'nbrDashboard'])->name('nbr.dashboard');
    Route::get('/vendor/dashboard', [DashboardController::class, 'vendorDashboard'])->name('vendor.dashboard');
    Route::get('/bppa/dashboard', [DashboardController::class, 'bppaDashboard'])->name('bppa.dashboard');
});

// Legacy BPPA route (for backward compatibility)
Route::get('/bppa/dashboard-legacy', function () {
    return view('bppa.dashboard');
});
