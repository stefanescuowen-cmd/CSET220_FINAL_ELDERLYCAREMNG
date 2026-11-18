@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<h1>Welcome, {{ $user->first_name }}</h1><p>{{ $role }} account</p>
@if(in_array($role, ['Admin','Supervisor']))
<dl><dt>Registered patients</dt><dd>{{ $counts['patients'] }}</dd><dt>Appointments today</dt><dd>{{ $counts['appointments'] }}</dd><dt>Accounts awaiting review</dt><dd>{{ $counts['pending'] }}</dd></dl>
@endif
@if($role === 'Patient' || $role === 'Family Member') <p><a href="{{ route('my-care') }}">View care information</a></p> @endif
@if($role === 'Admin')
<h2>Generate this month's invoice</h2><form method="post" action="{{ route('billing.generate') }}">@csrf<label>Patient ID <input type="number" name="patient_id" required min="1"></label><button>Update invoice</button></form>
@endif
@if($role === 'Admin' || $role === 'Supervisor') <p><a href="{{ route('reports.activities') }}">Review activity report</a></p> @endif
@endsection
