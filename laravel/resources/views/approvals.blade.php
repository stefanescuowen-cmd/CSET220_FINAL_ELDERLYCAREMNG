@extends('layouts.app')
@section('title', 'Registration approvals')
@section('content')<h1>Registration approvals</h1>
@if(session('family_code'))<p><strong>Patient ID {{ session('family_code.patient_id') }} family code:</strong> <code>{{ session('family_code.code') }}</code>. Share it securely with the family contact; it is shown only now.</p>@endif
<table><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Submitted</th><th>Action</th></tr></thead><tbody>
@forelse($users as $user)<tr><td>{{ $user->first_name }} {{ $user->last_name }}</td><td>{{ $user->email }}</td><td>{{ $user->role_name }}</td><td>{{ $user->created_at }}</td><td>
<form method="post" action="{{ route('approvals.update', $user->id) }}">@csrf<button name="decision" value="approve">Approve</button><button name="decision" value="reject">Reject</button></form></td></tr>
@empty<tr><td colspan="5">No pending registrations.</td></tr>@endforelse</tbody></table>@endsection
