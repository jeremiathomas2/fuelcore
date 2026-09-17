@extends('layouts.auth')

@section('title', 'Sign in')

@section('content')
  <h1>Welcome back</h1>
  <p class="auth-sub">Sign in to manage fuel operations across all stations.</p>

  <form method="POST" action="{{ route('login.attempt') }}">
    @csrf
    <div class="form-group">
      <label class="form-label" for="email">Email address</label>
      <input class="form-control" type="email" id="email" name="email" value="{{ old('email') }}" placeholder="you@fuelcore.test" required autofocus>
      @error('email')<div class="field-error">{{ $message }}</div>@enderror
    </div>
    <div class="form-group">
      <label class="form-label" for="password">Password</label>
      <input class="form-control" type="password" id="password" name="password" placeholder="••••••••" required>
      @error('password')<div class="field-error">{{ $message }}</div>@enderror
    </div>
    <div class="form-group d-flex" style="justify-content:space-between;align-items:center;">
      <div class="form-check">
        <input type="checkbox" id="remember" name="remember" value="1">
        <label for="remember">Remember me</label>
      </div>
      <a class="link" href="{{ route('password.request') }}">Forgot password?</a>
    </div>
    <button type="submit" class="btn btn-primary btn-block">Sign In</button>
  </form>

  <div class="demo-accounts">
    <b>Demo accounts</b>
    <div style="margin-top:6px;line-height:1.7;">
      super@fuelcore.test / DemoPass123 &nbsp;·&nbsp; admin@fuelcore.test / DemoPass123<br>
      attendant@fuelcore.test / DemoPass123 &nbsp;·&nbsp; manager@fuelcore.test / DemoPass123
    </div>
  </div>
@endsection