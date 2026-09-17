@extends('layouts.auth')

@section('title', 'Reset password')

@section('content')
  <h1>Choose a new password</h1>
  <p class="auth-sub">For {{ request('email') }}</p>

  <form method="POST" action="{{ route('password.store') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div class="form-group">
      <label class="form-label" for="email">Email address</label>
      <input class="form-control" type="email" id="email" name="email" value="{{ $email ?? old('email') }}" required>
      @error('email')<div class="field-error">{{ $message }}</div>@enderror
    </div>
    <div class="form-group">
      <label class="form-label" for="password">New password</label>
      <input class="form-control" type="password" id="password" name="password" placeholder="At least 8 characters" required>
      @error('password')<div class="field-error">{{ $message }}</div>@enderror
    </div>
    <div class="form-group">
      <label class="form-label" for="password_confirmation">Confirm password</label>
      <input class="form-control" type="password" id="password_confirmation" name="password_confirmation" placeholder="Repeat password" required>
    </div>
    <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
  </form>

  <div class="auth-foot">
    <a href="{{ route('login') }}">&larr; Back to sign in</a>
  </div>
@endsection