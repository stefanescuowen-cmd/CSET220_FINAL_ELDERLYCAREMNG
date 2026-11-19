<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CareController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $this->actor($request); $role = $user->role->name;
        $counts = ['patients' => DB::table('patients')->count(), 'appointments' => DB::table('appointments')->whereDate('appointment_date', today())->count(), 'pending' => DB::table('users')->where('is_approved', false)->count()];
        return view('dashboard', compact('user', 'role', 'counts'));
    }

    
    public function myCare(Request $request)
    {
        $actor = $this->actor($request);

        $date = $request->validate([
            'date' => ['nullable', 'date'],
        ])['date'] ?? today()->toDateString();

        $patient = DB::table('patients')
            ->where('user_id', $actor->id)
            ->first();

        $familyPatients = collect();

        if ($actor->role->name === 'Patient') {
            abort_unless($patient, 404);
        }

        if ($actor->role->name === 'Family Member') {
            $familyPatients = DB::table('patient_family_members')
                ->join('patients', 'patients.id', '=', 'patient_family_members.patient_id')
                ->join('users', 'users.id', '=', 'patients.user_id')
                ->where('patient_family_members.family_user_id', $actor->id)
                ->where('users.is_approved', true)
                ->where('users.is_active', true)
                ->select(
                    'patients.id',
                    'users.first_name',
                    'users.last_name'
                )
                ->orderBy('users.last_name')
                ->get();

            if (! $request->filled('patient_id')) {
                return view('my-care', [
                    'activities' => collect(),
                    'appointment' => null,
                    'date' => $date,
                    'carePatient' => null,
                    'medications' => collect(),
                    'caregivers' => collect(),
                    'familyPatients' => $familyPatients,
                ]);
            }

            $data = $request->validate([
                'patient_id' => ['required', 'integer', 'exists:patients,id'],
            ]);

            $patient = DB::table('patients')
                ->join('patient_family_members', 'patient_family_members.patient_id', '=', 'patients.id')
                ->where('patients.id', $data['patient_id'])
                ->where('patient_family_members.family_user_id', $actor->id)
                ->first(['patients.*']);

            abort_unless($patient, 403, 'You do not have access to this patient.');
        }

        $patientId = $patient->id;

        $activities = DB::table('activity_records')
            ->join('daily_care_records', 'daily_care_records.id', '=', 'activity_records.care_record_id')
            ->where('daily_care_records.patient_id', $patientId)
            ->whereDate('daily_care_records.care_date', $date)
            ->select('activity_records.*')
            ->orderBy('scheduled_at')
            ->get();

        $appointment = DB::table('appointments')
            ->where('patient_id', $patientId)
            ->whereDate('appointment_date', $date)
            ->first();

        if ($appointment) {
            $doctor = DB::table('users')
                ->where('id', $appointment->doctor_user_id)
                ->first();

            $appointment->doctor_name = trim($doctor->first_name . ' ' . $doctor->last_name);
        }

        $medications = DB::table('prescription_medications')
            ->join('prescriptions', 'prescriptions.id', '=', 'prescription_medications.prescription_id')
            ->where('prescriptions.patient_id', $patientId)
            ->where('prescriptions.status', 'Approved')
            ->whereDate('prescription_medications.active_from', '<=', $date)
            ->where(fn ($q) => $q->whereNull('prescription_medications.active_until')
                ->orWhereDate('prescription_medications.active_until', '>=', $date))
            ->orderBy('prescription_medications.time_slot')
            ->get(['prescription_medications.*']);

        $caregivers = DB::table('roster_patient_assignments')
            ->join('rosters', 'rosters.id', '=', 'roster_patient_assignments.roster_id')
            ->join('users', 'users.id', '=', 'roster_patient_assignments.caregiver_user_id')
            ->where('roster_patient_assignments.patient_id', $patientId)
            ->whereDate('rosters.roster_date', $date)
            ->select('users.first_name', 'users.last_name')
            ->get();

        $carePatient = DB::table('patients')
            ->join('users', 'users.id', '=', 'patients.user_id')
            ->where('patients.id', $patientId)
            ->select('patients.id', 'users.first_name', 'users.last_name')
            ->first();

        return view('my-care', compact(
            'activities',
            'appointment',
            'date',
            'carePatient',
            'medications',
            'caregivers',
            'familyPatients'
        ));
    }
    public function approvals()
    {
        $users = DB::table('users')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->where('users.is_approved', false)
            ->where('users.is_active', true)
            ->select('users.*', 'roles.name as role_name')
            ->orderBy('users.created_at')
            ->get();

        $patients = DB::table('patients')
            ->join('users', 'users.id', '=', 'patients.user_id')
            ->where('users.is_approved', true)
            ->where('users.is_active', true)
            ->select(
                'patients.id',
                'users.first_name',
                'users.last_name'
            )
            ->orderBy('users.last_name')
            ->get();

        return view('approvals', compact('users', 'patients'));
    }

    public function approve(Request $request, int $user)
    {
        $request->validate([
            'decision' => ['required', 'in:approve,reject'],
        ]);

        $target = DB::table('users')->where('id', $user)->first();

        abort_unless($target, 404);

        DB::table('users')
            ->where('id', $user)
            ->update([
                'is_approved' => $request->decision === 'approve',
                'is_active' => $request->decision === 'approve',
                'updated_at' => now(),
            ]);

        return back()->with('status', 'Registration reviewed.');
    }

    public function createFamilyInvitation(Request $request)
    {
        $data = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'relationship_to_patient' => ['nullable', 'string', 'max:60'],
        ]);

        $patient = DB::table('patients')
            ->join('users', 'users.id', '=', 'patients.user_id')
            ->where('patients.id', $data['patient_id'])
            ->where('users.is_approved', true)
            ->where('users.is_active', true)
            ->select('patients.id')
            ->first();

        abort_unless($patient, 404, 'The selected patient is unavailable.');

        $code = strtoupper(bin2hex(random_bytes(16)));

        DB::table('patient_family_invitations')->insert([
            'patient_id' => $patient->id,
            'code_hash' => Hash::make($code),
            'relationship_to_patient' => $data['relationship_to_patient'] ?? 'Family Member',
            'created_by_user_id' => $request->user()->id,
            'expires_at' => now()->addDays(7),
            'used_at' => null,
            'used_by_user_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()
            ->with('family_invitation', [
                'patient_id' => $patient->id,
                'code' => $code,
            ])
            ->with('status', 'Invitation created. Share the code securely; it can only be used once and expires in 7 days.');
    }

    public function patients(Request $request)
    {
        $query = DB::table('patients')->join('users', 'users.id', '=', 'patients.user_id')->leftJoin('patient_groups', 'patient_groups.id', '=', 'patients.group_id')->where('users.is_approved', true)->where('users.is_active', true);
        $this->search($query, $request, ['users.first_name', 'users.last_name', 'users.email', 'users.phone', 'patients.id', 'patients.emergency_contact_name']);
        if ($request->filled('group_id')) $query->where('patients.group_id', $request->integer('group_id'));
        $rows = $query->select('patients.*', 'users.first_name', 'users.last_name', 'users.phone', 'users.date_of_birth', 'patient_groups.name as group_name')->orderBy('users.last_name')->paginate(20)->withQueryString();
        return view('patients', ['patients' => $rows, 'groups' => DB::table('patient_groups')->orderBy('name')->get()]);
    }

    public function assignPatientGroup(Request $request, int $patient)
    {
        $data = $request->validate(['group_id' => ['nullable', 'exists:patient_groups,id']]);
        DB::table('patients')->where('id', $patient)->update(['group_id' => $data['group_id'] ?? null, 'updated_at' => now()]);
        return back()->with('status', 'Patient group updated.');
    }

    public function createGroup(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:60', 'unique:patient_groups,name'], 'description' => ['nullable', 'string']]);
        DB::table('patient_groups')->insert([...$data, 'created_at' => now(), 'updated_at' => now()]);
        return back()->with('status', 'Patient group created.');
    }

    public function employees(Request $request)
    {
        $actor = $this->actor($request);
        $query = DB::table('employees')->join('users', 'users.id', '=', 'employees.user_id')->join('roles', 'roles.id', '=', 'users.role_id');
        $this->search($query, $request, ['users.first_name', 'users.last_name', 'users.email', 'employees.employee_number']);
        if ($request->filled('role')) $query->where('roles.name', $request->string('role'));
        $rows = $query->select('employees.*', 'users.first_name', 'users.last_name', 'roles.name as role_name')->orderBy('users.last_name')->paginate(20)->withQueryString();
        return view('employees', ['employees' => $rows, 'roles' => DB::table('roles')->orderBy('name')->get(), 'canEditSalary' => $actor->role->name === 'Admin']);
    }

    public function updateSalary(Request $request, int $employee)
    {
        $data = $request->validate(['salary' => ['required', 'numeric', 'min:0', 'max:99999999.99']]);
        DB::table('employees')->where('id', $employee)->update(['salary' => $data['salary'], 'updated_at' => now()]);
        return back()->with('status', 'Salary updated.');
    }

    public function createEmployee(Request $request)
    {
        $actor = $this->actor($request);
        $isAdmin = $actor->role->name === 'Admin';
        $allowedRoles = $isAdmin ? 'Supervisor,Doctor,Caregiver' : 'Doctor,Caregiver';
        $data = $request->validate(['first_name' => ['required', 'string', 'max:60'], 'last_name' => ['required', 'string', 'max:60'], 'email' => ['required', 'email', 'unique:users,email'], 'phone' => ['nullable', 'string', 'max:25'], 'role' => ['required', 'in:'.$allowedRoles], 'employee_number' => ['nullable', 'string', 'max:30', 'unique:employees,employee_number'], 'salary' => ['nullable', 'numeric', 'min:0'], 'hire_date' => ['nullable', 'date'], 'password' => ['required', 'confirmed', 'min:8']]);
        DB::transaction(function () use ($data, $isAdmin) {
            $roleId = DB::table('roles')->where('name', $data['role'])->value('id');
            $userId = DB::table('users')->insertGetId(['role_id' => $roleId, 'first_name' => $data['first_name'], 'last_name' => $data['last_name'], 'email' => $data['email'], 'phone' => $data['phone'] ?? null, 'password' => \Illuminate\Support\Facades\Hash::make($data['password']), 'is_approved' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('employees')->insert(['user_id' => $userId, 'employee_number' => $data['employee_number'] ?? null, 'salary' => $isAdmin ? ($data['salary'] ?? null) : null, 'hire_date' => $data['hire_date'] ?? null, 'created_at' => now(), 'updated_at' => now()]);
        });
        return back()->with('status', 'Employee account created and activated.');
    }

    public function doctors(Request $request)
    {
        $data = $request->validate(['date' => ['required', 'date'], 'time' => ['required', 'date_format:H:i'], 'patient_id' => ['required', 'exists:patients,id']]);
        $doctors = DB::table('roster_doctors')->join('rosters', 'rosters.id', '=', 'roster_doctors.roster_id')->join('patients', 'patients.group_id', '=', 'rosters.group_id')->join('users', 'users.id', '=', 'roster_doctors.doctor_user_id')->join('roles', 'roles.id', '=', 'users.role_id')
            ->where('patients.id', $data['patient_id'])->whereDate('rosters.roster_date', $data['date'])->where('roles.name', 'Doctor')->where('users.is_active', true)->where('users.is_approved', true)->whereTime('roster_doctors.available_from', '<=', $data['time'])->whereTime('roster_doctors.available_until', '>=', $data['time'])
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('appointments')->whereColumn('appointments.doctor_user_id', 'users.id')->whereDate('appointments.appointment_date', $data['date'])->whereTime('appointments.appointment_time', $data['time'])->where('appointments.status', 'Scheduled'))
            ->select('users.id', 'users.first_name', 'users.last_name')->distinct()->get();
        return response()->json($doctors);
    }

    public function appointments(Request $request)
    {
        $actor = $this->actor($request);
        $query = DB::table('appointments')->join('patients', 'patients.id', '=', 'appointments.patient_id')->join('users as patient_users', 'patient_users.id', '=', 'patients.user_id')->join('users as doctor_users', 'doctor_users.id', '=', 'appointments.doctor_user_id');
        if ($actor->role->name === 'Doctor') $query->where('appointments.doctor_user_id', $actor->id);
        if ($request->filled('q')) { $term = '%'.$request->string('q')->trim().'%'; $query->where(fn ($q) => $q->where('patient_users.first_name', 'like', $term)->orWhere('patient_users.last_name', 'like', $term)->orWhere('appointments.patient_id', 'like', $term)); }
        if ($request->filled('date')) $query->whereDate('appointments.appointment_date', $request->input('date'));
        $appointments = $query->select('appointments.*', 'patient_users.first_name as patient_first_name', 'patient_users.last_name as patient_last_name', 'doctor_users.first_name as doctor_first_name', 'doctor_users.last_name as doctor_last_name')->orderByDesc('appointments.appointment_date')->paginate(20)->withQueryString();
        $drafts = $actor->role->name === 'Doctor' ? DB::table('prescriptions')->where('prescribed_by_user_id', $actor->id)->where('status', 'Draft')->get() : collect();
        $medicationRows = $actor->role->name === 'Doctor' ? DB::table('prescription_medications')->join('prescriptions', 'prescriptions.id', '=', 'prescription_medications.prescription_id')->join('patients', 'patients.id', '=', 'prescriptions.patient_id')->where('prescriptions.prescribed_by_user_id', $actor->id)->where('prescriptions.status', 'Approved')->select('prescription_medications.*', 'patients.id as patient_id')->orderByDesc('prescription_medications.created_at')->get() : collect();
        return view('appointments', compact('appointments', 'drafts', 'medicationRows'));
    }

    public function rosters(Request $request)
    {
        $date = $request->input('date', today()->toDateString());
        $rows = DB::table('rosters')->join('patient_groups', 'patient_groups.id', '=', 'rosters.group_id')->join('users as supervisors', 'supervisors.id', '=', 'rosters.supervisor_user_id')->whereDate('rosters.roster_date', $date)->select('rosters.*', 'patient_groups.name as group_name', 'supervisors.first_name as supervisor_first', 'supervisors.last_name as supervisor_last')->get();
        foreach ($rows as $row) {
            $row->doctors = DB::table('roster_doctors')->join('users', 'users.id', '=', 'roster_doctors.doctor_user_id')->where('roster_id', $row->id)->get(['users.first_name', 'users.last_name', 'available_from', 'available_until']);
            $row->caregivers = DB::table('roster_caregivers')->join('users', 'users.id', '=', 'roster_caregivers.caregiver_user_id')->where('roster_id', $row->id)->orderBy('caregiver_slot')->get(['users.first_name', 'users.last_name', 'caregiver_slot']);
        }
        return view('rosters', ['rosters' => $rows, 'date' => $date, 'groups' => DB::table('patient_groups')->orderBy('name')->get(), 'supervisors' => $this->usersInRole('Supervisor'), 'doctors' => $this->usersInRole('Doctor'), 'caregivers' => $this->usersInRole('Caregiver')]);
    }

    public function createRoster(Request $request)
    {
        $data = $request->validate(['group_id' => ['required', 'exists:patient_groups,id'], 'roster_date' => ['required', 'date'], 'supervisor_user_id' => ['required', 'exists:users,id'], 'doctor_user_id' => ['required', 'exists:users,id'], 'available_from' => ['required', 'date_format:H:i'], 'available_until' => ['required', 'date_format:H:i', 'after:available_from'], 'caregivers' => ['required', 'array', 'min:1', 'max:4'], 'caregivers.*' => ['required', 'distinct', 'exists:users,id'], 'activity_times' => ['required', 'array'], 'activity_times.*' => ['required', 'date_format:H:i']]);
        if (DB::table('rosters')->where('group_id', $data['group_id'])->whereDate('roster_date', $data['roster_date'])->exists()) throw ValidationException::withMessages(['roster_date' => 'A roster already exists for this group and date.']);
        abort_unless(DB::table('users')->join('roles', 'roles.id', '=', 'users.role_id')->where('users.id', $data['supervisor_user_id'])->where('roles.name', 'Supervisor')->where('users.is_active', true)->where('users.is_approved', true)->exists(), 422);
        abort_unless(DB::table('users')->join('roles', 'roles.id', '=', 'users.role_id')->where('users.id', $data['doctor_user_id'])->where('roles.name', 'Doctor')->where('users.is_active', true)->where('users.is_approved', true)->exists(), 422);
        foreach ($data['caregivers'] as $caregiverId) abort_unless(DB::table('users')->join('roles', 'roles.id', '=', 'users.role_id')->where('users.id', $caregiverId)->where('roles.name', 'Caregiver')->where('users.is_active', true)->where('users.is_approved', true)->exists(), 422);
        DB::transaction(function () use ($data) {
            $rosterId = DB::table('rosters')->insertGetId(['group_id' => $data['group_id'], 'supervisor_user_id' => $data['supervisor_user_id'], 'roster_date' => $data['roster_date'], 'created_at' => now(), 'updated_at' => now()]);
            DB::table('roster_doctors')->insert(['roster_id' => $rosterId, 'doctor_user_id' => $data['doctor_user_id'], 'available_from' => $data['available_from'], 'available_until' => $data['available_until']]);
            foreach ($data['caregivers'] as $index => $caregiverId) DB::table('roster_caregivers')->insert(['roster_id' => $rosterId, 'caregiver_user_id' => $caregiverId, 'caregiver_slot' => $index + 1]);
            $patients = DB::table('patients')->where('group_id', $data['group_id'])->pluck('id');
            foreach ($patients as $index => $patientId) {
                $caregiverId = $data['caregivers'][$index % count($data['caregivers'])];
                DB::table('roster_patient_assignments')->insert(['roster_id' => $rosterId, 'patient_id' => $patientId, 'caregiver_user_id' => $caregiverId]);
                $careId = DB::table('daily_care_records')->insertGetId(['patient_id' => $patientId, 'care_date' => $data['roster_date'], 'status' => 'Scheduled', 'created_at' => now(), 'updated_at' => now()]);
                foreach ($data['activity_times'] as $activity => $time) DB::table('activity_records')->insert(['care_record_id' => $careId, 'activity_type' => $activity, 'status' => 'Pending', 'scheduled_at' => Carbon::parse($data['roster_date'].' '.$time), 'created_at' => now(), 'updated_at' => now()]);
            }
        });
        return back()->with('status', 'Roster saved. Patients in the group were assigned evenly across caregivers and daily activities were scheduled.');
    }

    public function scheduleAppointment(Request $request)
    {
        $data = $request->validate(['patient_id' => ['required', 'exists:patients,id'], 'doctor_user_id' => ['required', 'exists:users,id'], 'appointment_date' => ['required', 'date', 'after_or_equal:today'], 'appointment_time' => ['required', 'date_format:H:i'], 'notes' => ['nullable', 'string']]);
        DB::transaction(function () use ($data) {
            $rosterDoctor = DB::table('roster_doctors')->join('rosters', 'rosters.id', '=', 'roster_doctors.roster_id')->join('patients', 'patients.group_id', '=', 'rosters.group_id')->join('users', 'users.id', '=', 'roster_doctors.doctor_user_id')->join('roles', 'roles.id', '=', 'users.role_id')->join('users as patient_users', 'patient_users.id', '=', 'patients.user_id')->where('patients.id', $data['patient_id'])->where('patient_users.is_approved', true)->where('patient_users.is_active', true)->where('rosters.roster_date', $data['appointment_date'])->where('roster_doctors.doctor_user_id', $data['doctor_user_id'])->where('roles.name', 'Doctor')->where('users.is_active', true)->where('users.is_approved', true)->whereTime('roster_doctors.available_from', '<=', $data['appointment_time'])->whereTime('roster_doctors.available_until', '>=', $data['appointment_time'])->select('roster_doctors.id')->lockForUpdate()->first();
            if (! $rosterDoctor) throw ValidationException::withMessages(['doctor_user_id' => 'The doctor is not rostered at that date and time.']);
            if (DB::table('appointments')->where('doctor_user_id', $data['doctor_user_id'])->where('appointment_date', $data['appointment_date'])->where('appointment_time', $data['appointment_time'])->where('status', 'Scheduled')->exists()) throw ValidationException::withMessages(['appointment_time' => 'The doctor already has an appointment at that time.']);
            DB::table('appointments')->insert([...$data, 'status' => 'Scheduled', 'meeting_confirmed' => false, 'created_at' => now(), 'updated_at' => now()]);
        });
        return back()->with('status', 'Appointment scheduled.');
    }

    public function markMeeting(Request $request, int $appointment)
    {
        $actor = $this->actor($request);
        $record = DB::table('appointments')->where('id', $appointment)->first(); abort_unless($record && $record->doctor_user_id === $actor->id, 404);
        abort_unless($record->appointment_date === today()->toDateString() && $record->status === 'Scheduled', 403, 'Only a scheduled meeting can be confirmed on its appointment date.');
        DB::table('appointments')->where('id', $appointment)->update(['meeting_confirmed' => true, 'status' => 'Completed', 'updated_at' => now()]);
        return back()->with('status', 'Appointment meeting recorded.');
    }

    public function createPrescription(Request $request)
    {
        $actor = $this->actor($request);
        $data = $request->validate(['appointment_id' => ['required', 'exists:appointments,id'], 'doctor_comments' => ['nullable', 'string'], 'medications' => ['required', 'array', 'min:1'], 'medications.*.time_slot' => ['required', 'in:Morning,Afternoon,Night'], 'medications.*.medication_name' => ['required', 'string', 'max:120'], 'medications.*.dosage' => ['required', 'string', 'max:120'], 'medications.*.instructions' => ['nullable', 'string'], 'medications.*.active_from' => ['required', 'date', 'after_or_equal:today']]);
        $appointment = DB::table('appointments')->where('id', $data['appointment_id'])->first();
        abort_unless($appointment && $appointment->doctor_user_id === $actor->id, 404);
        if ($appointment->appointment_date !== today()->toDateString() || ! $appointment->meeting_confirmed) throw ValidationException::withMessages(['appointment_id' => 'A prescription can only be created after meeting the patient on the appointment date.']);
        $slots = array_column($data['medications'], 'time_slot'); if (count($slots) !== count(array_unique($slots))) throw ValidationException::withMessages(['medications' => 'Only one medication is allowed per time slot.']);
        DB::transaction(function () use ($data, $appointment, $actor) {
            $id = DB::table('prescriptions')->insertGetId(['appointment_id' => $appointment->id, 'patient_id' => $appointment->patient_id, 'prescribed_by_user_id' => $actor->id, 'status' => 'Draft', 'doctor_comments' => $data['doctor_comments'] ?? null, 'approved_at' => null, 'created_at' => now(), 'updated_at' => now()]);
            foreach ($data['medications'] as $med) DB::table('prescription_medications')->insert(['prescription_id' => $id, ...$med, 'active_until' => null, 'is_active' => false, 'created_at' => now(), 'updated_at' => now()]);
        });
        return back()->with('status', 'Prescription saved as a draft. Review and approve it to activate the medications.');
    }

    public function approvePrescription(Request $request, int $prescription)
    {
        $actor = $this->actor($request);
        DB::transaction(function () use ($actor, $prescription) {
            $draft = DB::table('prescriptions')->where('id', $prescription)->lockForUpdate()->first();
            abort_unless($draft && $draft->prescribed_by_user_id === $actor->id && $draft->status === 'Draft', 404);
            $appointment = DB::table('appointments')->where('id', $draft->appointment_id)->first();
            abort_unless($appointment && $appointment->meeting_confirmed && $appointment->appointment_date === today()->toDateString(), 403, 'Approval requires a confirmed meeting on the appointment date.');
            $draftMedications = DB::table('prescription_medications')->where('prescription_id', $prescription)->get();
            foreach ($draftMedications as $medication) {
                $conflict = DB::table('prescription_medications')->join('prescriptions', 'prescriptions.id', '=', 'prescription_medications.prescription_id')->where('prescriptions.patient_id', $draft->patient_id)->where('prescriptions.status', 'Approved')->where('prescription_medications.is_active', true)->where('prescription_medications.time_slot', $medication->time_slot)->where('prescription_medications.prescription_id', '!=', $prescription)->where(fn ($q) => $q->whereNull('prescription_medications.active_until')->orWhereDate('prescription_medications.active_until', '>=', $medication->active_from))->exists();
                if ($conflict) throw ValidationException::withMessages(['medications' => 'This patient already has an active medication in the '.$medication->time_slot.' slot. Discontinue the previous medication before approving its replacement.']);
            }
            DB::table('prescriptions')->where('id', $prescription)->update(['status' => 'Approved', 'approved_at' => now(), 'updated_at' => now()]);
            DB::table('prescription_medications')->where('prescription_id', $prescription)->update(['is_active' => true, 'updated_at' => now()]);
        });
        return back()->with('status', 'Prescription approved and medications activated.');
    }

    public function discontinueMedication(Request $request, int $medication)
    {
        $actor = $this->actor($request);
        $record = DB::table('prescription_medications')->join('prescriptions', 'prescriptions.id', '=', 'prescription_medications.prescription_id')->where('prescription_medications.id', $medication)->select('prescription_medications.*', 'prescriptions.prescribed_by_user_id')->first();
        abort_unless($record && $record->prescribed_by_user_id === $actor->id && $record->is_active, 404);
        abort_if($record->active_from > today()->toDateString(), 422, 'A future medication has not started and cannot be discontinued yet.');
        DB::table('prescription_medications')->where('id', $medication)->update(['is_active' => false, 'active_until' => today()->toDateString(), 'updated_at' => now()]);
        return back()->with('status', 'Medication discontinued. The history is preserved.');
    }

    public function caregiver(Request $request)
    {
        $actor = $this->actor($request);
        $this->markMissed();
        $rows = DB::table('roster_patient_assignments')->join('rosters', 'rosters.id', '=', 'roster_patient_assignments.roster_id')->join('patients', 'patients.id', '=', 'roster_patient_assignments.patient_id')->join('users as patient_users', 'patient_users.id', '=', 'patients.user_id')->whereDate('rosters.roster_date', today())->where('roster_patient_assignments.caregiver_user_id', $actor->id)->select('patients.id as patient_id', 'patient_users.first_name', 'patient_users.last_name')->get();
        $medications = DB::table('prescription_medications')->join('prescriptions', 'prescriptions.id', '=', 'prescription_medications.prescription_id')->whereIn('prescriptions.patient_id', $rows->pluck('patient_id'))->where('prescriptions.status', 'Approved')->where('prescription_medications.is_active', true)->whereDate('prescription_medications.active_from', '<=', today())->where(fn ($q) => $q->whereNull('prescription_medications.active_until')->orWhereDate('prescription_medications.active_until', '>=', today()))->get(['prescriptions.patient_id', 'prescription_medications.time_slot', 'prescription_medications.medication_name', 'prescription_medications.dosage']);
        return view('caregiver', ['patients' => $rows, 'medications' => $medications, 'activities' => DB::table('activity_records')->join('daily_care_records', 'daily_care_records.id', '=', 'activity_records.care_record_id')->whereIn('daily_care_records.patient_id', $rows->pluck('patient_id'))->whereDate('daily_care_records.care_date', today())->select('activity_records.*', 'daily_care_records.patient_id')->get()]);
    }

    public function completeActivity(Request $request, int $activity)
    {
        $actor = $this->actor($request);
        $this->markMissed(); $record = DB::table('activity_records')->join('daily_care_records', 'daily_care_records.id', '=', 'activity_records.care_record_id')->where('activity_records.id', $activity)->select('activity_records.*', 'daily_care_records.patient_id')->first();
        abort_unless($record && $record->status === 'Pending' && Carbon::parse($record->scheduled_at)->isFuture(), 403, 'This activity is no longer editable.');
        $assigned = DB::table('roster_patient_assignments')->join('rosters', 'rosters.id', '=', 'roster_patient_assignments.roster_id')->where('roster_patient_assignments.patient_id', $record->patient_id)->where('roster_patient_assignments.caregiver_user_id', $actor->id)->whereDate('rosters.roster_date', today())->exists();
        abort_unless($assigned, 403);
        DB::transaction(function () use ($activity, $actor, $record) {
            DB::table('activity_records')->where('id', $activity)->where('status', 'Pending')->update(['status' => 'Completed', 'completed_at' => now(), 'completed_by_user_id' => $actor->id, 'updated_at' => now()]);
            DB::table('daily_care_records')->where('id', $record->care_record_id)->where('status', 'Scheduled')->update(['status' => 'Completed', 'completed_by_user_id' => $actor->id, 'completed_at' => now(), 'updated_at' => now()]);
        });
        return back()->with('status', 'Activity completed.');
    }

    public function report(Request $request)
    {
        $data = $request->validate(['date' => ['nullable', 'date']]); $date = $data['date'] ?? today()->toDateString(); $this->markMissed();
        $rows = DB::table('activity_records')->join('daily_care_records', 'daily_care_records.id', '=', 'activity_records.care_record_id')->join('patients', 'patients.id', '=', 'daily_care_records.patient_id')->join('users as patient_users', 'patient_users.id', '=', 'patients.user_id')
            ->leftJoin('rosters', function ($join) { $join->on('rosters.group_id', '=', 'patients.group_id')->on('rosters.roster_date', '=', 'daily_care_records.care_date'); })->leftJoin('roster_patient_assignments as assignment', function ($join) { $join->on('assignment.patient_id', '=', 'patients.id')->on('assignment.roster_id', '=', 'rosters.id'); })
            ->leftJoin('users as caregiver_users', 'caregiver_users.id', '=', 'assignment.caregiver_user_id')->leftJoin('appointments', function ($join) { $join->on('appointments.patient_id', '=', 'patients.id')->on('appointments.appointment_date', '=', 'daily_care_records.care_date'); })->leftJoin('users as doctor_users', 'doctor_users.id', '=', 'appointments.doctor_user_id')
            ->whereDate('daily_care_records.care_date', $date)->select('patient_users.first_name as patient_first_name', 'patient_users.last_name as patient_last_name', 'caregiver_users.first_name as caregiver_first_name', 'caregiver_users.last_name as caregiver_last_name', 'doctor_users.first_name as doctor_first_name', 'doctor_users.last_name as doctor_last_name', 'appointments.appointment_time', 'appointments.status as appointment_status', 'activity_records.*')->orderBy('activity_records.scheduled_at')->get();
        return view('report', compact('rows', 'date'));
    }

    public function bill(Request $request)
    {
        $data = $request->validate(['patient_id' => ['required', 'exists:patients,id']]);
        $start = today()->startOfMonth(); $end = today()->endOfMonth(); $startDate = $start->toDateString(); $endDate = $end->toDateString();
        $invoice = DB::transaction(function () use ($data, $startDate, $endDate) {
            $existing = DB::table('billing_records')->where('patient_id', $data['patient_id'])->where('period_start', $startDate)->where('period_end', $endDate)->first();
            if ($existing) return ['record' => $existing, 'created' => false];
            $items = [];
            $care = DB::table('daily_care_records')->where('patient_id', $data['patient_id'])->whereBetween('care_date', [$startDate, $endDate])->where('status', 'Completed')->get();
            foreach ($care as $day) $items[] = ['Daily Care', 'Care received', $day->care_date, 1, 10, 10];
            $appointments = DB::table('appointments')->where('patient_id', $data['patient_id'])->whereBetween('appointment_date', [$startDate, $endDate])->where('status', 'Completed')->get();
            foreach ($appointments as $visit) $items[] = ['Doctor Appointment', 'Completed doctor appointment', $visit->appointment_date, 1, 50, 50];
            $medications = DB::table('prescription_medications')->join('prescriptions', 'prescriptions.id', '=', 'prescription_medications.prescription_id')->where('prescriptions.patient_id', $data['patient_id'])->where('prescriptions.status', 'Approved')->whereDate('prescription_medications.active_from', '<=', $endDate)->where(fn ($q) => $q->whereNull('prescription_medications.active_until')->orWhereDate('prescription_medications.active_until', '>=', $startDate))->count();
            if ($medications) $items[] = ['Medication', 'Active medications for '.Carbon::parse($startDate)->format('F Y'), $startDate, $medications, 5, $medications * 5];
            $total = array_sum(array_column($items, 5));
            $id = DB::table('billing_records')->insertGetId(['patient_id' => $data['patient_id'], 'period_start' => $startDate, 'period_end' => $endDate, 'total_due' => $total, 'amount_paid' => 0, 'status' => $total ? 'Unpaid' : 'Paid', 'generated_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            foreach ($items as [$type, $description, $date, $quantity, $price, $lineTotal]) DB::table('billing_line_items')->insert(['billing_id' => $id, 'charge_type' => $type, 'description' => $description, 'service_date' => $date, 'quantity' => $quantity, 'unit_price' => $price, 'line_total' => $lineTotal, 'created_at' => now(), 'updated_at' => now()]);
            return ['record' => DB::table('billing_records')->where('id', $id)->first(), 'created' => true];
        });
        return view('billing-result', ['invoice' => $invoice['record'], 'created' => $invoice['created']]);
    }

    public function payment(Request $request, int $billing)
    {
        $actor = $this->actor($request);
        $data = $request->validate(['amount' => ['required', 'numeric', 'gt:0'], 'notes' => ['nullable', 'string']]);
        DB::transaction(function () use ($billing, $data, $actor) {
            $invoice = DB::table('billing_records')->where('id', $billing)->lockForUpdate()->first(); abort_unless($invoice, 404);
            $balance = (float) $invoice->total_due - (float) $invoice->amount_paid; if ($data['amount'] > $balance) throw ValidationException::withMessages(['amount' => 'Payment exceeds the outstanding balance.']);
            DB::table('payments')->insert(['billing_id' => $billing, 'recorded_by_user_id' => $actor->id, 'amount' => $data['amount'], 'payment_date' => now(), 'method' => 'Cash', 'notes' => $data['notes'] ?? null, 'created_at' => now(), 'updated_at' => now()]);
            $paid = (float) $invoice->amount_paid + (float) $data['amount']; DB::table('billing_records')->where('id', $billing)->update(['amount_paid' => $paid, 'status' => $paid >= (float) $invoice->total_due ? 'Paid' : 'Partially Paid', 'updated_at' => now()]);
        });
        return back()->with('status', 'Cash payment recorded.');
    }

    private function markMissed(): void
    {
        DB::table('activity_records')->where('status', 'Pending')->where('scheduled_at', '<', now())->update(['status' => 'Missed', 'updated_at' => now()]);
    }

    private function search($query, Request $request, array $columns): void
    {
        if ($request->filled('q')) { $term = '%'.$request->string('q')->trim().'%'; $query->where(fn ($nested) => collect($columns)->each(fn ($column) => $nested->orWhere($column, 'like', $term))); }
    }

    private function usersInRole(string $role)
    {
        return DB::table('users')->join('roles', 'roles.id', '=', 'users.role_id')->where('roles.name', $role)->where('users.is_active', true)->where('users.is_approved', true)->select('users.id', 'users.first_name', 'users.last_name')->orderBy('users.last_name')->get();
    }

    private function actor(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
