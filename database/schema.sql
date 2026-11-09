CREATE DATABASE IF NOT EXISTS elderly_care_management
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE elderly_care_management;

-- 1. ROLES
-- Stores the roles available in the system.

CREATE TABLE roles (
    role_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(40) NOT NULL UNIQUE,
    access_level INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);

-- 2. USERS
-- Stores login and basic personal information for all users.

CREATE TABLE users (
    user_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id BIGINT UNSIGNED NOT NULL,

    first_name VARCHAR(60) NOT NULL,
    last_name VARCHAR(60) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(25),
    date_of_birth DATE,

    password_hash VARCHAR(255) NOT NULL,

    is_approved BOOLEAN NOT NULL DEFAULT FALSE,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_users_role
        FOREIGN KEY (role_id)
        REFERENCES roles(role_id),

    INDEX idx_users_role (role_id),
    INDEX idx_users_name (last_name, first_name),
    INDEX idx_users_active (is_active)
);

-- 3. EMPLOYEES
-- Additional information for employees, such as doctors,
-- caregivers, supervisors, and administrators.

CREATE TABLE employees (
    employee_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,

    employee_number VARCHAR(30) UNIQUE,
    salary DECIMAL(10,2),
    hire_date DATE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_employees_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    CONSTRAINT chk_employee_salary
        CHECK (salary IS NULL OR salary >= 0)
);

-- 4. PATIENT GROUPS
-- Stores the groups used to organize patients and rosters.

CREATE TABLE patient_groups (
    group_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_name VARCHAR(60) NOT NULL UNIQUE,
    description TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. PATIENTS
-- Stores information specific to patients.
-- The patient's basic name and date of birth are stored in users.

CREATE TABLE patients (
    patient_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,

    group_id BIGINT UNSIGNED,
    admission_date DATE NOT NULL,

    emergency_contact_name VARCHAR(120) NOT NULL,
    emergency_contact_relationship VARCHAR(60),
    emergency_contact_phone VARCHAR(25) NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_patients_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_patients_group
        FOREIGN KEY (group_id)
        REFERENCES patient_groups(group_id)
        ON DELETE SET NULL,

    INDEX idx_patients_group (group_id),
    INDEX idx_patients_admission (admission_date)
);

-- 6. PATIENT FAMILY MEMBERS
-- Connects family member accounts to patients.
-- Family codes are stored as hashes, never plain text.

CREATE TABLE patient_family_members (
    patient_family_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    patient_id BIGINT UNSIGNED NOT NULL,
    family_user_id BIGINT UNSIGNED NOT NULL,

    family_code_hash VARCHAR(255) NOT NULL,
    relationship_to_patient VARCHAR(60),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_family_patient
        FOREIGN KEY (patient_id)
        REFERENCES patients(patient_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_family_user
        FOREIGN KEY (family_user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    CONSTRAINT uq_patient_family
        UNIQUE (patient_id, family_user_id),

    INDEX idx_family_user (family_user_id)
);

-- 7. ROSTERS
-- One roster is created for a patient group on a given date.

CREATE TABLE rosters (
    roster_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    group_id BIGINT UNSIGNED NOT NULL,
    supervisor_user_id BIGINT UNSIGNED NOT NULL,

    roster_date DATE NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_rosters_group
        FOREIGN KEY (group_id)
        REFERENCES patient_groups(group_id),

    CONSTRAINT fk_rosters_supervisor
        FOREIGN KEY (supervisor_user_id)
        REFERENCES users(user_id),

    CONSTRAINT uq_roster_group_date
        UNIQUE (group_id, roster_date),

    INDEX idx_rosters_date (roster_date)
);

-- 8. ROSTER DOCTORS
-- Allows multiple doctors to be assigned to a roster.

CREATE TABLE roster_doctors (
    roster_doctor_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    roster_id BIGINT UNSIGNED NOT NULL,
    doctor_user_id BIGINT UNSIGNED NOT NULL,

    CONSTRAINT fk_roster_doctors_roster
        FOREIGN KEY (roster_id)
        REFERENCES rosters(roster_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_roster_doctors_user
        FOREIGN KEY (doctor_user_id)
        REFERENCES users(user_id),

    CONSTRAINT uq_roster_doctor
        UNIQUE (roster_id, doctor_user_id)
);

-- 9. ROSTER CAREGIVERS
-- Supports up to four caregiver positions per roster.

CREATE TABLE roster_caregivers (
    roster_caregiver_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    roster_id BIGINT UNSIGNED NOT NULL,
    caregiver_user_id BIGINT UNSIGNED NOT NULL,

    caregiver_slot TINYINT UNSIGNED NOT NULL,

    CONSTRAINT fk_roster_caregivers_roster
        FOREIGN KEY (roster_id)
        REFERENCES rosters(roster_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_roster_caregivers_user
        FOREIGN KEY (caregiver_user_id)
        REFERENCES users(user_id),

    CONSTRAINT uq_roster_caregiver_slot
        UNIQUE (roster_id, caregiver_slot),

    CONSTRAINT uq_roster_caregiver
        UNIQUE (roster_id, caregiver_user_id),

    CONSTRAINT chk_caregiver_slot
        CHECK (caregiver_slot BETWEEN 1 AND 4)
);

-- 10. ROSTER PATIENT ASSIGNMENTS
-- Connects patients to their assigned roster.

CREATE TABLE roster_patient_assignments (
    assignment_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    roster_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,

    CONSTRAINT fk_assignments_roster
        FOREIGN KEY (roster_id)
        REFERENCES rosters(roster_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_assignments_patient
        FOREIGN KEY (patient_id)
        REFERENCES patients(patient_id)
        ON DELETE CASCADE,

    CONSTRAINT uq_roster_patient
        UNIQUE (roster_id, patient_id),

    INDEX idx_assignments_patient (patient_id)
);

-- 11. APPOINTMENTS
-- Stores doctor appointments for patients.
-- A patient can only have one appointment per day.

CREATE TABLE appointments (
    appointment_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    patient_id BIGINT UNSIGNED NOT NULL,
    doctor_user_id BIGINT UNSIGNED NOT NULL,

    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,

    appointment_status ENUM(
        'Scheduled',
        'Completed',
        'Cancelled',
        'No Show'
    ) NOT NULL DEFAULT 'Scheduled',

    notes TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_appointments_patient
        FOREIGN KEY (patient_id)
        REFERENCES patients(patient_id),

    CONSTRAINT fk_appointments_doctor
        FOREIGN KEY (doctor_user_id)
        REFERENCES users(user_id),

    CONSTRAINT uq_patient_appointment_date
        UNIQUE (patient_id, appointment_date),

    INDEX idx_appointments_doctor_date
        (doctor_user_id, appointment_date),

    INDEX idx_appointments_date (appointment_date)
);

-- 12. PRESCRIPTIONS
-- Stores prescriptions created by doctors.
-- A prescription must be approved before its medications
-- become active.

CREATE TABLE prescriptions (
    prescription_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    appointment_id BIGINT UNSIGNED NOT NULL UNIQUE,
    patient_id BIGINT UNSIGNED NOT NULL,
    prescribed_by_user_id BIGINT UNSIGNED NOT NULL,

    prescription_status ENUM(
        'Draft',
        'Approved',
        'Rejected'
    ) NOT NULL DEFAULT 'Draft',

    doctor_comments TEXT,

    approved_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_prescriptions_appointment
        FOREIGN KEY (appointment_id)
        REFERENCES appointments(appointment_id),

    CONSTRAINT fk_prescriptions_patient
        FOREIGN KEY (patient_id)
        REFERENCES patients(patient_id),

    CONSTRAINT fk_prescriptions_doctor
        FOREIGN KEY (prescribed_by_user_id)
        REFERENCES users(user_id),

    INDEX idx_prescriptions_patient (patient_id),
    INDEX idx_prescriptions_status (prescription_status)
);

-- 13. PRESCRIPTION MEDICATIONS
-- Each prescription can contain one medication per time slot.

CREATE TABLE prescription_medications (
    medication_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    prescription_id BIGINT UNSIGNED NOT NULL,

    medication_name VARCHAR(120) NOT NULL,
    dosage VARCHAR(120) NOT NULL,
    instructions TEXT,

    time_slot ENUM(
        'Morning',
        'Afternoon',
        'Night'
    ) NOT NULL,

    active_from DATE NOT NULL,
    active_until DATE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_medications_prescription
        FOREIGN KEY (prescription_id)
        REFERENCES prescriptions(prescription_id)
        ON DELETE CASCADE,

    CONSTRAINT uq_prescription_time_slot
        UNIQUE (prescription_id, time_slot),

    CONSTRAINT chk_medication_dates
        CHECK (active_until IS NULL OR active_until >= active_from),

    INDEX idx_medication_active_dates (active_from, active_until)
);

-- 14. DAILY CARE RECORDS
-- Tracks whether a patient received care on a particular day.
-- Completed records qualify for the daily care charge.

CREATE TABLE daily_care_records (
    care_record_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    patient_id BIGINT UNSIGNED NOT NULL,
    care_date DATE NOT NULL,

    care_status ENUM(
        'Scheduled',
        'Completed',
        'Missed',
        'Cancelled'
    ) NOT NULL DEFAULT 'Scheduled',

    completed_by_user_id BIGINT UNSIGNED,
    completed_at DATETIME,

    notes TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_care_patient
        FOREIGN KEY (patient_id)
        REFERENCES patients(patient_id),

    CONSTRAINT fk_care_completed_by
        FOREIGN KEY (completed_by_user_id)
        REFERENCES users(user_id),

    CONSTRAINT uq_patient_care_date
        UNIQUE (patient_id, care_date),

    INDEX idx_care_date_status (care_date, care_status)
);

-- 15. ACTIVITY RECORDS
-- Tracks medication and meal activities for each patient.
-- Missed activities should not be changed back to Completed
-- through the application.

CREATE TABLE activity_records (
    activity_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    care_record_id BIGINT UNSIGNED NOT NULL,

    activity_type ENUM(
        'Morning Medication',
        'Afternoon Medication',
        'Night Medication',
        'Breakfast',
        'Lunch',
        'Dinner'
    ) NOT NULL,

    activity_status ENUM(
        'Pending',
        'Completed',
        'Missed',
        'Cancelled'
    ) NOT NULL DEFAULT 'Pending',

    scheduled_at DATETIME NOT NULL,
    completed_at DATETIME,

    completed_by_user_id BIGINT UNSIGNED,

    notes TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_activities_care_record
        FOREIGN KEY (care_record_id)
        REFERENCES daily_care_records(care_record_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_activities_completed_by
        FOREIGN KEY (completed_by_user_id)
        REFERENCES users(user_id),

    CONSTRAINT uq_care_activity
        UNIQUE (care_record_id, activity_type),

    INDEX idx_activities_status_time
        (activity_status, scheduled_at)
);

-- 16. BILLING RECORDS
-- Stores invoices generated for individual patients.

CREATE TABLE billing_records (
    billing_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    patient_id BIGINT UNSIGNED NOT NULL,

    billing_period_start DATE NOT NULL,
    billing_period_end DATE NOT NULL,

    total_due DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,

    billing_status ENUM(
        'Unpaid',
        'Partially Paid',
        'Paid'
    ) NOT NULL DEFAULT 'Unpaid',

    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_billing_patient
        FOREIGN KEY (patient_id)
        REFERENCES patients(patient_id),

    CONSTRAINT chk_billing_period
        CHECK (billing_period_end >= billing_period_start),

    CONSTRAINT chk_billing_amounts
        CHECK (
            total_due >= 0
            AND amount_paid >= 0
            AND amount_paid <= total_due
        ),

    INDEX idx_billing_patient (patient_id),
    INDEX idx_billing_status (billing_status),
    INDEX idx_billing_period
        (billing_period_start, billing_period_end)
);

-- 17. BILLING LINE ITEMS
-- Breaks down individual charges on a billing record.
-- Stores the amount charged at the time of billing.

CREATE TABLE billing_line_items (
    billing_item_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    billing_id BIGINT UNSIGNED NOT NULL,

    charge_type ENUM(
        'Daily Care',
        'Doctor Appointment',
        'Medication'
    ) NOT NULL,

    description VARCHAR(255) NOT NULL,
    service_date DATE,

    quantity DECIMAL(10,2) NOT NULL DEFAULT 1.00,
    unit_price DECIMAL(10,2) NOT NULL,
    line_total DECIMAL(10,2) NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_billing_items_record
        FOREIGN KEY (billing_id)
        REFERENCES billing_records(billing_id)
        ON DELETE CASCADE,

    CONSTRAINT chk_billing_item_amounts
        CHECK (
            quantity > 0
            AND unit_price >= 0
            AND line_total >= 0
        ),

    INDEX idx_billing_items_type (charge_type)
);

-- 18. PAYMENTS
-- Stores payments made toward patient billing records.
-- Cash is the only supported payment method initially.

CREATE TABLE payments (
    payment_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    billing_id BIGINT UNSIGNED NOT NULL,
    recorded_by_user_id BIGINT UNSIGNED NOT NULL,

    payment_amount DECIMAL(10,2) NOT NULL,
    payment_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    payment_method ENUM('Cash') NOT NULL DEFAULT 'Cash',

    notes TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_payments_billing
        FOREIGN KEY (billing_id)
        REFERENCES billing_records(billing_id),

    CONSTRAINT fk_payments_recorded_by
        FOREIGN KEY (recorded_by_user_id)
        REFERENCES users(user_id),

    CONSTRAINT chk_payment_amount
        CHECK (payment_amount > 0),

    INDEX idx_payments_date (payment_date)
);

-- 19. BILLING RATES
-- Stores the system's charge rates.
-- Rates can be updated without changing historical invoices.

CREATE TABLE billing_rates (
    rate_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    charge_type ENUM(
        'Daily Care',
        'Doctor Appointment',
        'Medication'
    ) NOT NULL,

    rate_amount DECIMAL(10,2) NOT NULL,

    effective_from DATE NOT NULL,
    effective_until DATE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT chk_billing_rate_amount
        CHECK (rate_amount >= 0),

    CONSTRAINT chk_billing_rate_dates
        CHECK (
            effective_until IS NULL
            OR effective_until >= effective_from
        ),

    INDEX idx_billing_rates_type_dates
        (charge_type, effective_from, effective_until)
);