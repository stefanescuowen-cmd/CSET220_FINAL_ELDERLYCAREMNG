USE elderly_care_management;

-- 1. INITIAL USER ROLES

INSERT INTO roles (role_name, access_level)
VALUES
    ('Admin', 100),
    ('Supervisor', 80),
    ('Doctor', 60),
    ('Caregiver', 50),
    ('Patient', 20),
    ('Family Member', 10)
ON DUPLICATE KEY UPDATE
    access_level = VALUES(access_level);

-- 2. INITIAL BILLING RATES

INSERT INTO billing_rates (charge_type, rate_amount, effective_from)
VALUES
    ('Daily Care', 10.00, '2026-01-01'),
    ('Doctor Appointment', 50.00, '2026-01-01'),
    ('Medication', 5.00, '2026-01-01');