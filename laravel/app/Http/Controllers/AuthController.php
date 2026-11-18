<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function loginForm() { return view('auth.login'); }
    public function registerForm() { return view('auth.register'); }

    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        if (! Auth::attempt($credentials)) return back()->withErrors(['email' => 'The email or password is incorrect.'])->onlyInput('email');
        $request->session()->regenerate();
        $user = $request->user();
        if (! $user->is_active || ! $user->is_approved) {
            Auth::logout();
            return back()->withErrors(['email' => 'This account is awaiting approval or has been disabled.']);
        }
        return redirect()->route('dashboard');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:60'], 'last_name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'], 'phone' => ['nullable', 'string', 'max:25'],
            'date_of_birth' => ['nullable', 'date', 'before:today'], 'password' => ['required', 'confirmed', 'min:8'],
            'role' => ['required', 'in:Patient,Family Member,Caregiver'],
            'admission_date' => ['required_if:role,Patient', 'nullable', 'date'], 'emergency_contact_name' => ['required_if:role,Patient', 'nullable', 'string', 'max:120'],
            'emergency_contact_phone' => ['required_if:role,Patient', 'nullable', 'string', 'max:25'], 'emergency_contact_relationship' => ['nullable', 'string', 'max:60'],
            'patient_id' => ['required_if:role,Family Member', 'nullable', 'exists:patients,id'], 'family_code' => ['required_if:role,Family Member', 'nullable', 'string'],
            'relationship_to_patient' => ['nullable', 'string', 'max:60'],
        ]);
        if ($data['role'] === 'Family Member') {
            $patient = DB::table('patients')->where('id', $data['patient_id'])->first();
            if (! $patient?->family_code_hash || ! Hash::check($data['family_code'], $patient->family_code_hash)) return back()->withErrors(['family_code' => 'Patient ID and family code do not match.'])->withInput($request->except('password', 'password_confirmation'));
        }
        $role = Role::where('name', $data['role'])->firstOrFail();
        DB::transaction(function () use ($data, $role) {
            $user = User::create(['role_id' => $role->id, 'first_name' => $data['first_name'], 'last_name' => $data['last_name'], 'email' => $data['email'], 'phone' => $data['phone'] ?? null, 'date_of_birth' => $data['date_of_birth'] ?? null, 'password' => Hash::make($data['password']), 'is_approved' => false, 'is_active' => true]);
            if ($role->name === 'Patient') $user->patient()->create(['admission_date' => $data['admission_date'], 'emergency_contact_name' => $data['emergency_contact_name'], 'emergency_contact_phone' => $data['emergency_contact_phone'], 'emergency_contact_relationship' => $data['emergency_contact_relationship'] ?? null]);
            if ($role->name === 'Family Member') DB::table('patient_family_members')->insert(['patient_id' => $data['patient_id'], 'family_user_id' => $user->id, 'family_code_hash' => Hash::make($data['family_code']), 'relationship_to_patient' => $data['relationship_to_patient'] ?? null, 'created_at' => now(), 'updated_at' => now()]);
        });
        return redirect()->route('login')->with('status', 'Registration received. An Admin or Supervisor must approve your account.');
    }

    public function logout(Request $request)
    {
        Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
