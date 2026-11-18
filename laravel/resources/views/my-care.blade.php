@extends('layouts.app')
@section('title', 'Care information')
@section('content')<h1>Care information</h1>
@if(auth()->user()->role->name==='Family Member')<form method="post" action="{{ route('my-care.lookup') }}">@csrf<label>Patient ID <input type="number" name="patient_id" value="{{ request('patient_id') }}" required></label><label>Family code <input type="password" name="family_code" required></label><label>Date <input type="date" name="date" value="{{ $date }}"></label><button>View patient</button></form>@else<form method="get"><label>Date <input type="date" name="date" value="{{ $date }}"></label><button>View date</button></form>@endif
@if($carePatient)
<h2>{{ $carePatient->first_name }} {{ $carePatient->last_name }} · Patient ID {{ $carePatient->id }} · {{ $date }}</h2>
@if($appointment)<p>Doctor: {{ $appointment->doctor_name }} · Appointment: {{ $appointment->appointment_time }} ({{ $appointment->status }})</p>@endif
<p>Caregiver: {{ $caregivers->isEmpty() ? 'Not assigned' : $caregivers->map(fn($person)=>$person->first_name.' '.$person->last_name)->join(', ') }}</p>
<h3>Medication schedule</h3><ul>@forelse($medications as $medication)<li>{{ $medication->time_slot }}: {{ $medication->medication_name }} · {{ $medication->dosage }} @if($medication->instructions)({{ $medication->instructions }})@endif</li>@empty<li>No approved medication for this date.</li>@endforelse</ul>
<table><thead><tr><th>Activity</th><th>Scheduled</th><th>Status</th></tr></thead><tbody>@forelse($activities as $activity)<tr><td>{{ $activity->activity_type }}</td><td>{{ $activity->scheduled_at }}</td><td>{{ $activity->status }}</td></tr>@empty<tr><td colspan="3">No care activities recorded for this date.</td></tr>@endforelse</tbody></table>
@endif @endsection
