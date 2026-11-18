<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CareController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : redirect()->route('login'));
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'role:Admin,Supervisor,Doctor,Caregiver,Patient,Family Member'])->group(function () {
    Route::get('/dashboard', [CareController::class, 'dashboard'])->name('dashboard');
    Route::get('/rosters', [CareController::class, 'rosters'])->name('rosters.index');
    Route::post('/rosters', [CareController::class, 'createRoster'])->middleware('role:Admin,Supervisor')->name('rosters.store');
    Route::get('/my-care', [CareController::class, 'myCare'])->middleware('role:Patient,Family Member')->name('my-care');
    Route::post('/my-care', [CareController::class, 'myCare'])->middleware('role:Patient,Family Member')->name('my-care.lookup');
    Route::get('/patients', [CareController::class, 'patients'])->middleware('role:Admin,Supervisor,Doctor,Caregiver')->name('patients.index');
    Route::put('/patients/{patient}/group', [CareController::class, 'assignPatientGroup'])->middleware('role:Admin,Supervisor')->name('patients.group');
    Route::post('/patient-groups', [CareController::class, 'createGroup'])->middleware('role:Admin,Supervisor')->name('groups.store');
    Route::get('/appointments', [CareController::class, 'appointments'])->middleware('role:Admin,Supervisor,Doctor')->name('appointments.index');
    Route::get('/employees', [CareController::class, 'employees'])->middleware('role:Admin,Supervisor')->name('employees.index');
    Route::post('/employees', [CareController::class, 'createEmployee'])->middleware('role:Admin,Supervisor')->name('employees.store');
    Route::put('/employees/{employee}/salary', [CareController::class, 'updateSalary'])->middleware('role:Admin')->name('employees.salary');
    Route::get('/approvals', [CareController::class, 'approvals'])->middleware('role:Admin,Supervisor')->name('approvals.index');
    Route::post('/approvals/{user}', [CareController::class, 'approve'])->middleware('role:Admin,Supervisor')->name('approvals.update');
    Route::get('/appointments/doctors', [CareController::class, 'doctors'])->middleware('role:Admin,Supervisor')->name('appointments.doctors');
    Route::post('/appointments', [CareController::class, 'scheduleAppointment'])->middleware('role:Admin,Supervisor')->name('appointments.store');
    Route::post('/appointments/{appointment}/meeting', [CareController::class, 'markMeeting'])->middleware('role:Doctor')->name('appointments.meeting');
    Route::post('/prescriptions', [CareController::class, 'createPrescription'])->middleware('role:Doctor')->name('prescriptions.store');
    Route::post('/prescriptions/{prescription}/approve', [CareController::class, 'approvePrescription'])->middleware('role:Doctor')->name('prescriptions.approve');
    Route::post('/medications/{medication}/discontinue', [CareController::class, 'discontinueMedication'])->middleware('role:Doctor')->name('medications.discontinue');
    Route::get('/caregiver', [CareController::class, 'caregiver'])->middleware('role:Caregiver')->name('caregiver.home');
    Route::post('/activities/{activity}/complete', [CareController::class, 'completeActivity'])->middleware('role:Caregiver')->name('activities.complete');
    Route::get('/reports/activities', [CareController::class, 'report'])->middleware('role:Admin,Supervisor')->name('reports.activities');
    Route::post('/billing', [CareController::class, 'bill'])->middleware('role:Admin')->name('billing.generate');
    Route::post('/billing/{billing}/payments', [CareController::class, 'payment'])->middleware('role:Admin')->name('billing.payment');
});
