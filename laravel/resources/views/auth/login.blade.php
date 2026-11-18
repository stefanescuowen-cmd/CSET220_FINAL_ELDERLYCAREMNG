@extends('layouts.app')
@section('title', 'Log in')
@section('content')
<h1>Log in</h1>
<form method="post" action="{{ route('login.submit') }}">@csrf
    <label>Email <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email"></label>
    <label>Password <input type="password" name="password" required autocomplete="current-password"></label>
    <button type="submit">Log in</button>
</form>
<p><a href="{{ route('register') }}">Request an account</a></p>
@endsection
