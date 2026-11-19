@extends('layouts.app')

@section('title', 'Registration approvals')

@section('content')
<h1>Registration approvals</h1>

@if(session('family_invitation'))
    <section>
        <h2>Family invitation created</h2>
        <p>
            Patient ID: {{ session('family_invitation.patient_id') }}
        </p>
        <p>
            Invitation code:
            <code>{{ session('family_invitation.code') }}</code>
        </p>
        <p>
            Share this code securely. It can only be used once and expires in 7 days.
        </p>
    </section>
@endif

<h2>Create a family invitation</h2>

@if($errors->any())
    <ul>
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif

<form method="post" action="{{ route('family-invitations.store') }}">
    @csrf

    <label>
        Patient
        <select name="patient_id" required>
            <option value="">Select a patient</option>
            @foreach($patients as $patient)
                <option value="{{ $patient->id }}" @selected(old('patient_id') == $patient->id)>
                    Patient ID {{ $patient->id }} -
                    {{ $patient->first_name }} {{ $patient->last_name }}
                </option>
            @endforeach
        </select>
    </label>

    <label>
        Relationship to patient
        <input
            name="relationship_to_patient"
            value="{{ old('relationship_to_patient') }}"
            maxlength="60"
            placeholder="Example: Daughter, Son, Spouse"
        >
    </label>

    <button type="submit">Generate invitation</button>
</form>

<hr>

<h2>Pending registration approvals</h2>

<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Submitted</th>
            <th>Action</th>
        </tr>
    </thead>

    <tbody>
        @forelse($users as $user)
            <tr>
                <td>{{ $user->first_name }} {{ $user->last_name }}</td>
                <td>{{ $user->email }}</td>
                <td>{{ $user->role_name }}</td>
                <td>{{ $user->created_at }}</td>
                <td>
                    <form method="post" action="{{ route('approvals.update', $user->id) }}">
                        @csrf
                        <button name="decision" value="approve">Approve</button>
                        <button name="decision" value="reject">Reject</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5">No pending registrations.</td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection