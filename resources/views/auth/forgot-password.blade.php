@extends('layouts.auth')

@section('title', 'Forgot password')

@section('content')
  <h1>Reset your password</h1>
  <p class="auth-sub">Enter your email and we will send you a password reset link.</p>

  <form method="POST" action="{{ route('password.email') }}">
    @csrf
    <div class="form-group">
      <label class="form-label" for="email">Email address</label>
      <input class="form-control" type="email" id="email" name="email" value="{{ old('email') }}" placeholder="you@fuelcore.test" required autofocus>
      @error('email')<div class="field-error">{{ $message }}</div>@enderror
    </div>
    <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>
  </form>

  <div class="auth-foot">
    <a href="{{ route('login') }}">&larr; Back to sign in</a>
  </div>
@endsection