# Elder Care Management (Laravel)

The Laravel application implements the confirmed project workflows: account approval and role checks, patient and employee search, group and roster management, date/time based appointment scheduling, doctor meeting and prescription approval, caregiver activity tracking, missed activity reporting, family access codes, and monthly cash billing.

## Local setup

Requirements: PHP 8.3+, Composer, and Node.js/npm if you want to build frontend assets. The default `.env.example` uses SQLite.

```sh
composer install
copy .env.example .env
php artisan key:generate
```

Create the SQLite database file before migrating if it does not already exist:

```sh
type nul > database/database.sqlite
```

In PowerShell, use `New-Item database/database.sqlite -ItemType File` instead.

Set `INITIAL_ADMIN_EMAIL` and `INITIAL_ADMIN_PASSWORD` in `.env` before the initial seed if you want a first Admin account. Then run:

```sh
php artisan migrate --seed
```

Clear the initial Admin password from `.env` after seeding. The seeder only creates that account if both values are set and the email does not already exist. Admin/Supervisor can create staff accounts and approve registrations. Create patient groups on the Roster page, assign patients to a group on the Patients page, then create that group's roster. Patients in the group are assigned across the selected caregivers.

To automatically mark activities missed after their deadline, keep Laravel's scheduler running locally:

```sh
php artisan schedule:work
```

The roster form includes suggested daily activity times that can be changed to match the project's final schedule. Appointment time availability uses the date and interval stored on that roster. Doctor appointment slots are treated as exclusive at an exact time.

## Confirmed billing rules

- Daily care: $10 for each daily care record marked completed.
- Doctor appointments: $50 for each completed appointment.
- Medication: $5 per medication whose active dates overlap the invoice month.
- Payments are cash only.
- An invoice is unique per patient and calendar month. Repeating Update returns the existing invoice and does not duplicate line items.

The project notes do not define partial-month medication proration. The current implementation charges $5 for a medication if its active dates overlap any part of that month. The project notes also do not define activity deadlines; the suggested times shown on the roster form are editable defaults.
