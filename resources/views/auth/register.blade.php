@extends('layouts.app')

@section('title', 'Register')

@section('content')
    <div style="max-width:400px;margin:60px auto;">
        <div class="card">
            <h2 style="text-align:center;margin-bottom:16px;">Create Account</h2>

            <form method="POST" action="{{ route('register') }}">
                @csrf
                <label for="name">Name</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus>

                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required>

                <label for="password">Password</label>
                <input type="text" id="password" name="password" required style="-webkit-text-security:disc;text-security:disc;">

                <label for="password_confirmation">Confirm Password</label>
                <input type="text" id="password_confirmation" name="password_confirmation" required style="-webkit-text-security:disc;text-security:disc;">

                <button type="submit" class="btn btn-primary" style="width:100%;margin-top:16px;">Register</button>
            </form>

            <p style="text-align:center;margin:16px 0 0;font-size:14px;">
                Already have an account? <a href="{{ route('login') }}">Login</a>
            </p>
        </div>
    </div>
@endsection
