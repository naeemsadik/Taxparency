<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Citizen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CitizenAuthController extends Controller
{
    /**
     * Show the citizen login form
     */
    public function showLogin()
    {
        return view('auth.citizen.login');
    }

    /**
     * Handle citizen login
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tiin' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $citizen = Citizen::where('tiin', $request->tiin)->first();

        if (!$citizen || !Hash::check($request->password, $citizen->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid TIIN or password'
            ], 401);
        }

        // Generate API token (for API usage)
        $token = $citizen->createToken('citizen-access')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'citizen' => [
                    'id' => $citizen->id,
                    'tiin' => $citizen->tiin,
                    'full_name' => $citizen->full_name,
                ],
                'token' => $token
            ]
        ]);
    }

    /**
     * Handle citizen registration
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tiin' => 'required|string|unique:citizens,tiin',
            'full_name' => 'required|string|max:255',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $citizen = Citizen::create([
            'tiin' => $request->tiin,
            'full_name' => $request->full_name,
            'password' => Hash::make($request->password),
        ]);

        $token = $citizen->createToken('citizen-access')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'data' => [
                'citizen' => [
                    'id' => $citizen->id,
                    'tiin' => $citizen->tiin,
                    'full_name' => $citizen->full_name,
                ],
                'token' => $token
            ]
        ], 201);
    }

    /**
     * Handle citizen logout
     */
    public function logout(Request $request)
    {
        // For demo purposes, just return success
        return response()->json([
            'success' => true,
            'message' => 'Logout successful'
        ]);
    }

    /**
     * Get authenticated citizen profile
     */
    public function profile(Request $request)
    {
        // For demo purposes, return sample citizen data
        $citizen = Citizen::first(); // Get first citizen for demo
        
        if (!$citizen) {
            return response()->json([
                'success' => false,
                'message' => 'No citizen data found'
            ], 404);
        }
        
        // Get citizen statistics
        $stats = [
            'total_tax_returns' => $citizen->taxReturns()->count(),
            'approved_returns' => $citizen->taxReturns()->where('status', 'approved')->count(),
            'pending_returns' => $citizen->taxReturns()->where('status', 'pending')->count(),
            'total_votes' => $citizen->votes()->count(),
            'total_tax_paid' => $citizen->taxReturns()->where('status', 'approved')->sum('total_cost'),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'citizen' => [
                    'id' => $citizen->id,
                    'tiin' => $citizen->tiin,
                    'full_name' => $citizen->full_name,
                    'created_at' => $citizen->created_at,
                ],
                'statistics' => $stats
            ]
        ]);
    }
}
