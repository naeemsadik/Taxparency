<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use App\Models\Citizen;
use App\Models\NbrOfficer;
use App\Models\Vendor;
use App\Models\BppaOfficer;

class AuthController extends Controller
{
    // Show login forms
    public function showCitizenLogin()
    {
        return view('auth.citizen-login');
    }

    public function showNbrLogin()
    {
        return view('auth.nbr-login');
    }

    public function showVendorLogin()
    {
        return view('auth.vendor-login');
    }

    public function showBppaLogin()
    {
        return view('auth.bppa-login');
    }

    // Handle citizen login
    public function citizenLogin(Request $request)
    {
        $request->validate([
            'tiin' => 'required|string',
            'password' => 'required|string',
        ]);

        try {
            // Try to find citizen in database
            $citizen = Citizen::where('tiin', $request->tiin)->first();

            // For demo mode, create citizen if not exists or allow any password
            if (!$citizen) {
                $citizen = Citizen::create([
                    'tiin' => $request->tiin,
                    'full_name' => 'Demo Citizen ' . substr($request->tiin, -3), // Demo name with TIIN suffix
                    'password' => Hash::make($request->password),
                ]);
            }

            // Set session data
            Session::put([
                'user_type' => 'citizen',
                'user_id' => $citizen->id,
                'user_name' => $citizen->full_name,
                'user_tiin' => $citizen->tiin,
                'authenticated' => true,
            ]);

            return redirect()->route('citizen.dashboard')->with('success', 'Login successful!');
        } catch (\Exception $e) {
            return back()->withErrors(['login' => 'Login failed. Please try again.'])->withInput();
        }
    }

    // Handle NBR officer login
    public function nbrLogin(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        try {
            // Demo credentials
            $validCredentials = [
                'nbr.officer1' => 'nbr123',
                'nbr.officer2' => 'nbr123',
            ];

            if (!isset($validCredentials[$request->username]) || 
                $validCredentials[$request->username] !== $request->password) {
                return back()->withErrors(['login' => 'Invalid credentials. Please use demo credentials.'])->withInput();
            }

            // Try to find NBR officer in database or create for demo
            $nbrOfficer = NbrOfficer::where('username', $request->username)->first();
            
            if (!$nbrOfficer) {
                $nbrOfficer = NbrOfficer::create([
                    'username' => $request->username,
                    'name' => 'NBR Officer',
                    'email' => $request->username . '@nbr.gov.bd',
                    'password' => Hash::make($request->password),
                ]);
            }

            // Set session data
            Session::put([
                'user_type' => 'nbr',
                'user_id' => $nbrOfficer->id,
                'user_name' => $nbrOfficer->name,
                'username' => $nbrOfficer->username,
                'authenticated' => true,
            ]);

            return redirect()->route('nbr.dashboard')->with('success', 'Login successful!');

        } catch (\Exception $e) {
            return back()->withErrors(['login' => 'Login failed. Please try again.'])->withInput();
        }
    }

    // Handle vendor login
    public function vendorLogin(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        try {
            // Demo credentials
            $validCredentials = [
                'abc.construction' => 'vendor123',
                'xyz.infrastructure' => 'vendor123',
                'national.builders' => 'vendor123',
            ];

            if (!isset($validCredentials[$request->username]) || 
                $validCredentials[$request->username] !== $request->password) {
                return back()->withErrors(['login' => 'Invalid credentials. Please use demo vendor credentials.'])->withInput();
            }

            // Map usernames to company names
            $companyNames = [
                'abc.construction' => 'ABC Construction Ltd.',
                'xyz.infrastructure' => 'XYZ Infrastructure Pvt.',
                'national.builders' => 'National Builders Corp.',
            ];

            // Try to find vendor in database or create for demo
            $vendor = Vendor::where('username', $request->username)->first();
            
            if (!$vendor) {
                $vendor = Vendor::create([
                    'username' => $request->username,
                    'company_name' => $companyNames[$request->username] ?? 'Demo Company',
                    'email' => $request->username . '@company.com',
                    'phone' => '01700000000',
                    'address' => 'Demo Address',
                    'password' => Hash::make($request->password),
                    'status' => 'approved',
                ]);
            }

            // Set session data
            Session::put([
                'user_type' => 'vendor',
                'user_id' => $vendor->id,
                'user_name' => $vendor->company_name,
                'username' => $vendor->username,
                'authenticated' => true,
            ]);

            return redirect()->route('vendor.dashboard')->with('success', 'Login successful!');

        } catch (\Exception $e) {
            return back()->withErrors(['login' => 'Login failed. Please try again.'])->withInput();
        }
    }

    // Handle BPPA officer login
    public function bppaLogin(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        try {
            // Demo credentials
            $validCredentials = [
                'bppa.officer1' => 'bppa123',
                'bppa.officer2' => 'bppa123',
            ];

            if (!isset($validCredentials[$request->username]) || 
                $validCredentials[$request->username] !== $request->password) {
                return back()->withErrors(['login' => 'Invalid credentials. Please use demo credentials.'])->withInput();
            }

            // Try to find BPPA officer in database or create for demo
            $bppaOfficer = BppaOfficer::where('username', $request->username)->first();
            
            if (!$bppaOfficer) {
                $bppaOfficer = BppaOfficer::create([
                    'username' => $request->username,
                    'name' => 'BPPA Officer',
                    'email' => $request->username . '@bppa.gov.bd',
                    'password' => Hash::make($request->password),
                ]);
            }

            // Set session data
            Session::put([
                'user_type' => 'bppa',
                'user_id' => $bppaOfficer->id,
                'user_name' => $bppaOfficer->name,
                'username' => $bppaOfficer->username,
                'authenticated' => true,
            ]);

            return redirect()->route('bppa.dashboard')->with('success', 'Login successful!');

        } catch (\Exception $e) {
            return back()->withErrors(['login' => 'Login failed. Please try again.'])->withInput();
        }
    }

    // Handle logout
    public function logout(Request $request)
    {
        Session::flush();
        return redirect()->route('home')->with('success', 'Logged out successfully!');
    }
}
