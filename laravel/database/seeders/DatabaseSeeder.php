<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach ([['Admin', 100], ['Supervisor', 80], ['Doctor', 60], ['Caregiver', 50], ['Patient', 20], ['Family Member', 10]] as [$name, $level]) {
            DB::table('roles')->updateOrInsert(['name' => $name], ['access_level' => $level, 'updated_at' => now(), 'created_at' => now()]);
        }
        foreach ([['Daily Care', 10], ['Doctor Appointment', 50], ['Medication', 5]] as [$type, $amount]) {
            DB::table('billing_rates')->updateOrInsert(['charge_type' => $type, 'effective_from' => '2026-01-01'], ['rate_amount' => $amount, 'updated_at' => now(), 'created_at' => now()]);
        }

        $email = env('INITIAL_ADMIN_EMAIL');
        $password = env('INITIAL_ADMIN_PASSWORD');
        if ($email && $password && ! DB::table('users')->where('email', $email)->exists()) {
            $adminId = DB::table('users')->insertGetId([
                'role_id' => DB::table('roles')->where('name', 'Admin')->value('id'),
                'first_name' => 'System', 'last_name' => 'Admin', 'email' => $email,
                'password' => Hash::make($password), 'is_approved' => true, 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('employees')->insert(['user_id' => $adminId, 'hire_date' => today(), 'created_at' => now(), 'updated_at' => now()]);
        }
    }
}
