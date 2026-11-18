<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>@yield('title', 'Elder Care')</title></head>
<body>
<header><a href="{{ route('dashboard') }}"><strong>Elder Care Management</strong></a>
    @auth
    <nav aria-label="Main navigation">
        <a href="{{ route('dashboard') }}">Home</a>
        <a href="{{ route('rosters.index') }}">Roster</a>
        @if(in_array(auth()->user()->role->name, ['Admin','Supervisor','Doctor','Caregiver'])) <a href="{{ route('patients.index') }}">Patients</a> @endif
        @if(in_array(auth()->user()->role->name, ['Admin','Supervisor'])) <a href="{{ route('employees.index') }}">Employees</a><a href="{{ route('approvals.index') }}">Approvals</a><a href="{{ route('reports.activities') }}">Activity report</a> @endif
        @if(in_array(auth()->user()->role->name, ['Admin','Supervisor','Doctor'])) <a href="{{ route('appointments.index') }}">Appointments</a> @endif
        @if(auth()->user()->role->name === 'Caregiver') <a href="{{ route('caregiver.home') }}">Daily care</a> @endif
        @if(in_array(auth()->user()->role->name, ['Patient','Family Member'])) <a href="{{ route('my-care') }}">My care</a> @endif
        <form method="post" action="{{ route('logout') }}">@csrf <button>Log out</button></form>
    </nav>
    @endauth
</header>
<main>
    @if(session('status')) <p role="status">{{ session('status') }}</p> @endif
    @if($errors->any()) <div role="alert"><strong>Please review the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
    @yield('content')
</main>
</body>
</html>
