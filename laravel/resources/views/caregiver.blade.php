@extends('layouts.app')
@section('title', 'Daily care')
@section('content')<h1>Today's assigned patients</h1>
@forelse($patients as $patient)<h2>{{ $patient->first_name }} {{ $patient->last_name }} (ID {{ $patient->patient_id }})</h2>
<table><thead><tr><th>Activity</th><th>Scheduled time</th><th>Medication / dosage</th><th>Status</th><th></th></tr></thead><tbody>@foreach($activities->where('patient_id', $patient->patient_id) as $activity)@php($slot = str_replace(' Medication', '', $activity->activity_type))@php($med = $medications->first(fn($item)=>$item->patient_id===$patient->patient_id && $item->time_slot===$slot))<tr><td>{{ $activity->activity_type }}</td><td>{{ $activity->scheduled_at }}</td><td>{{ $med ? $med->medication_name.' · '.$med->dosage : '—' }}</td><td>{{ $activity->status }}</td><td>@if($activity->status==='Pending')<form method="post" action="{{ route('activities.complete',$activity->id) }}">@csrf<button>Mark complete</button></form>@endif</td></tr>@endforeach</tbody></table>
@empty<p>No patients are assigned to you today.</p>@endforelse @endsection
