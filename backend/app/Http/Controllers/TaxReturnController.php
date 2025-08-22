<?php

namespace App\Http\Controllers;

use App\Models\TaxReturn;
use App\Models\Citizen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TaxReturnController extends Controller
{
    /**
     * Submit a new tax return
     */
    public function submit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'citizen_id' => 'required|exists:citizens,id',
            'fiscal_year' => 'required|string|max:10',
            'total_income' => 'required|numeric|min:0',
            'total_cost' => 'required|numeric|min:0',
            'tax_document' => 'required|file|mimes:pdf|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if tax return already exists for this citizen and fiscal year
        $existingReturn = TaxReturn::where('citizen_id', $request->citizen_id)
                                  ->where('fiscal_year', $request->fiscal_year)
                                  ->first();

        if ($existingReturn) {
            return response()->json([
                'success' => false,
                'message' => 'Tax return already submitted for this fiscal year'
            ], 409);
        }

        try {
            // Store PDF file
            $file = $request->file('tax_document');
            $fileName = 'tax_returns/' . Str::uuid() . '.pdf';
            $filePath = $file->storeAs('public', $fileName);

            // Simulate IPFS upload (generate mock IPFS hash)
            $mockIpfsHash = 'Qm' . Str::random(44);

            // Create tax return record
            $taxReturn = TaxReturn::create([
                'citizen_id' => $request->citizen_id,
                'fiscal_year' => $request->fiscal_year,
                'ipfs_hash' => $mockIpfsHash,
                'total_income' => $request->total_income,
                'total_cost' => $request->total_cost,
                'status' => 'pending',
            ]);

            // Simulate blockchain transaction
            $mockTxHash = '0x' . Str::random(64);
            $taxReturn->update(['blockchain_tx_hash' => $mockTxHash]);

            return response()->json([
                'success' => true,
                'message' => 'Tax return submitted successfully',
                'data' => [
                    'tax_return' => $taxReturn,
                    'ipfs_hash' => $mockIpfsHash,
                    'blockchain_tx' => $mockTxHash
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit tax return: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tax returns for a citizen
     */
    public function getCitizenReturns($citizenId)
    {
        $citizen = Citizen::find($citizenId);
        if (!$citizen) {
            return response()->json([
                'success' => false,
                'message' => 'Citizen not found'
            ], 404);
        }

        $taxReturns = $citizen->taxReturns()
                            ->with('reviewer:id,full_name,officer_id')
                            ->orderBy('created_at', 'desc')
                            ->get();

        return response()->json([
            'success' => true,
            'data' => $taxReturns
        ]);
    }

    /**
     * Get specific tax return details
     */
    public function getReturnDetails($id)
    {
        $taxReturn = TaxReturn::with(['citizen:id,tiin,full_name', 'reviewer:id,full_name,officer_id'])
                            ->find($id);

        if (!$taxReturn) {
            return response()->json([
                'success' => false,
                'message' => 'Tax return not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $taxReturn
        ]);
    }

    /**
     * Get pending tax returns for NBR officers
     */
    public function getPendingReturns()
    {
        $pendingReturns = TaxReturn::with(['citizen:id,tiin,full_name'])
                                 ->where('status', 'pending')
                                 ->orderBy('created_at', 'asc')
                                 ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $pendingReturns
        ]);
    }

    /**
     * Review and approve/decline tax return (NBR Officer)
     */
    public function reviewReturn(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'officer_id' => 'required|exists:nbr_officers,id',
            'status' => 'required|in:approved,declined',
            'comments' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $taxReturn = TaxReturn::find($id);
        if (!$taxReturn) {
            return response()->json([
                'success' => false,
                'message' => 'Tax return not found'
            ], 404);
        }

        if ($taxReturn->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Tax return has already been reviewed'
            ], 409);
        }

        try {
            $taxReturn->update([
                'status' => $request->status,
                'reviewed_by' => $request->officer_id,
                'reviewed_at' => now(),
                'review_comments' => $request->comments ?? ''
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tax return reviewed successfully',
                'data' => $taxReturn->load(['citizen:id,tiin,full_name', 'reviewer:id,full_name,officer_id'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to review tax return: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tax return statistics
     */
    public function getStatistics()
    {
        $stats = [
            'total_returns' => TaxReturn::count(),
            'pending_returns' => TaxReturn::where('status', 'pending')->count(),
            'approved_returns' => TaxReturn::where('status', 'approved')->count(),
            'declined_returns' => TaxReturn::where('status', 'declined')->count(),
            'total_tax_collected' => TaxReturn::where('status', 'approved')->sum('total_cost'),
            'total_income_reported' => TaxReturn::where('status', 'approved')->sum('total_income'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
