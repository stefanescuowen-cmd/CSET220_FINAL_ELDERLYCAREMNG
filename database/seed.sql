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

-- 2. INITIAL BILLING RATES

INSERT INTO billing_rates (charge_type, rate_amount, effective_from)
SELECT new_rates.charge_type, new_rates.rate_amount, new_rates.effective_from
FROM (
    SELECT 'Daily Care' AS charge_type, 10.00 AS rate_amount, '2026-01-01' AS effective_from
    UNION ALL
    SELECT 'Doctor Appointment', 50.00, '2026-01-01'
    UNION ALL
    SELECT 'Medication', 5.00, '2026-01-01'
) AS new_rates
WHERE NOT EXISTS (
    SELECT 1
    FROM billing_rates existing_rates
    WHERE existing_rates.charge_type = new_rates.charge_type
      AND existing_rates.effective_from = new_rates.effective_from
);