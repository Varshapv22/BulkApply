@extends('layouts.app')

@section('title', 'Login')

@section('content')
    <div style="max-width:400px;margin:60px auto;">
        <div class="card">
            <h2 style="text-align:center;margin-bottom:16px;">Login to BulkApply</h2>

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>

                <label for="password">Password</label>
                <input type="text" id="password" name="password" required style="-webkit-text-security:disc;text-security:disc;">

                <div style="margin:12px 0;display:flex;align-items:center;gap:8px;">
                    <input type="checkbox" id="remember" name="remember" style="width:auto;">
                    <label for="remember" style="margin:0;font-weight:400;">Remember me</label>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;">Login</button>
            </form>

            <p style="text-align:center;margin:16px 0 0;font-size:14px;">
                Don't have an account? <a href="{{ route('register') }}">Register</a>
            </p>
        </div>
    </div>
@endsection
