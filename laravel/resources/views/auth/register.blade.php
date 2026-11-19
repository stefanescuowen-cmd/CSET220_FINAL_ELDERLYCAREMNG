@extends('layouts.app')

@section('title', 'Register')

@section('content')
<h1>Request an account</h1>
<p>New accounts require Admin or Supervisor approval.</p>

<form method="post" action="{{ route('register.submit') }}">
    @csrf

    <label>
        First name
        <input name="first_name" value="{{ old('first_name') }}" required maxlength="60">
    </label>

    <label>
        Last name
        <input name="last_name" value="{{ old('last_name') }}" required maxlength="60">
    </label>

    <label>
        Email
        <input type="email" name="email" value="{{ old('email') }}" required>
    </label>

    <label>
        Phone
        <input name="phone" value="{{ old('phone') }}">
    </label>

    <label>
        Date of birth
        <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}">
    </label>

    <label>
        Account type
        <select name="role" id="role" required>
            <option value="Patient" @selected(old('role') === 'Patient')>Patient</option>
            <option value="Family Member" @selected(old('role') === 'Family Member')>Family Member</option>
            <option value="Caregiver" @selected(old('role') === 'Caregiver')>Caregiver</option>
        </select>
    </label>

    <fieldset id="patient-fields">
        <legend>Patient details</legend>

        <label>
            Admission date
            <input type="date" name="admission_date" value="{{ old('admission_date') }}">
        </label>

        <label>
            Emergency contact
            <input name="emergency_contact_name" value="{{ old('emergency_contact_name') }}">
        </label>

        <label>
            Relationship
            <input name="emergency_contact_relationship" value="{{ old('emergency_contact_relationship') }}">
        </label>

        <label>
            Emergency contact phone
            <input name="emergency_contact_phone" value="{{ old('emergency_contact_phone') }}">
        </label>
    </fieldset>

    <fieldset id="family-fields" hidden>
        <legend>Family access</legend>

        <label>
            Patient ID
            <input type="number" name="patient_id" value="{{ old('patient_id') }}" min="1">
        </label>

        <label>
            Invitation code
            <input name="invitation_code" value="{{ old('invitation_code') }}" autocomplete="off">
        </label>
    </fieldset>

    <label>
        Password
        <input type="password" name="password" required minlength="8">
    </label>

    <label>
        Confirm password
        <input type="password" name="password_confirmation" required>
    </label>

    <button type="submit">Submit registration</button>
    <a href="{{ route('login') }}">Cancel</a>
</form>

<script>
    const role = document.querySelector('#role');
    const fields = document.querySelector('#patient-fields');
    const familyFields = document.querySelector('#family-fields');

    function toggleFields() {
        fields.hidden = role.value !== 'Patient';
        familyFields.hidden = role.value !== 'Family Member';
    }

    role.addEventListener('change', toggleFields);
    toggleFields();
</script>
@endsection