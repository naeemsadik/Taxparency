@extends('layouts.app')

@section('title', 'Citizen Login - Taxparency')

@section('content')
<div class="login-container">
    <div class="card" style="max-width: 400px; margin: 2rem auto;">
        <div class="logo">👤</div>
        <h1>Citizen Login</h1>
        <p>Enter your TIIN and password to access your dashboard</p>

        <form method="POST" action="{{ route('login.citizen.submit') }}">
            @csrf
            <div class="form-group">
                <label for="tiin">TIIN (Taxpayer Identification Number)</label>
                <input type="text" 
                       id="tiin" 
                       name="tiin" 
                       class="form-control" 
                       placeholder="Enter your TIIN" 
                       value="{{ old('tiin') }}" 
                       required>
                @error('tiin')
                    <small style="color: #dc3545;">{{ $message }}</small>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       class="form-control" 
                       placeholder="Enter your password" 
                       required>
                @error('password')
                    <small style="color: #dc3545;">{{ $message }}</small>
                @enderror
            </div>

            <button type="submit" class="btn" style="width: 100%;">Login</button>
        </form>

        <div class="demo-note">
            <strong>Demo Mode:</strong> Use any TIIN and password to access the demo dashboard
            <br><br>
            <strong>Example:</strong><br>
            TIIN: <code>123456789012</code><br>
            Password: <code>demo123</code>
        </div>

        <div style="text-align: center; margin-top: 1rem;">
            <a href="{{ route('home') }}" class="back-link">← Back to Home</a>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    body {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        padding: 20px;
    }

    main {
        margin-top: 0;
        padding: 0;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .login-container {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .card {
        text-align: center;
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(10px);
        border-radius: 20px;
        padding: 3rem;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    }

    .logo {
        font-size: 3rem;
        margin-bottom: 1rem;
    }

    h1 {
        color: #667eea;
        margin-bottom: 0.5rem;
        font-size: 1.8rem;
    }

    p {
        color: #666;
        margin-bottom: 2rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
        text-align: left;
    }

    .demo-note {
        background: rgba(102, 126, 234, 0.1);
        border: 1px solid #667eea;
        border-radius: 8px;
        padding: 1rem;
        margin-top: 1rem;
        font-size: 0.9rem;
        color: #667eea;
        text-align: left;
    }

    .back-link {
        color: #667eea;
        text-decoration: none;
        font-weight: 500;
    }

    .back-link:hover {
        text-decoration: underline;
    }
</style>
@endpush
