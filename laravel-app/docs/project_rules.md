Elderly Care Management System
Sophomore Project 2026 — Complete Instruction & Development Guide
1. Project Overview
The Elderly Care Management System is designed to manage the daily care, medical appointments, medication schedules, caregivers, employees, patient information, family access, missed activities, and payments associated with an elderly care facility.
The system will contain multiple user roles, each with different permissions and responsibilities.
Primary Objectives
Maintain patient records and personal information.
Manage employee information and assignments.
Organize doctor appointments and doctor availability.
Track patient medication and daily activities.
Allow family members to view patient information.
Allow administrators and supervisors to monitor missed activities.
Calculate and maintain patient payments.
Maintain historical records of appointments, prescriptions, activities, and payments.

2. Technology and Development Environment
The project will primarily use Laravel, PHP, and SQL.
Technology
Purpose
Laravel
PHP framework for application development
PHP
Backend programming language
SQL
Database management and queries
VS Code
Possible development environment
HTML/CSS
Frontend structure and styling
JavaScript
Interactive frontend functionality

Development Considerations
Laravel will handle routing, controllers, models, authentication, and database interactions.
SQL will store patient, employee, appointment, medication, and payment information.
VS Code can be used to write and organize the project's code.
Reusable components should be considered for recurring elements such as dropdowns, search bars, tables, and filtering options.

3. User Roles and Access Permissions
The system has six primary user roles.
Role
Main Responsibilities
Admin
Full system management, employee salaries, registration approvals, payments, rosters, and reports
Supervisor
Manage and monitor patients, appointments, rosters, and registration approvals
Doctor
View assigned patients, appointments, medical history, and prescribe medication
Caregiver
View patients assigned to them and manage daily care activities
Patient
View their own care schedule and information
Family Member
View patient information using their Patient ID and Family Code

Access Control Rules
Each user must log in using their registered email and password.
Users should only access pages permitted by their role.
Patient accounts should automatically display information belonging to their own Patient ID.
Family members must enter both the Patient ID and Family Code to access patient information.
Admin-only functions must not be available to other roles.
Permissions must be enforced through the backend, not just by hiding buttons or pages.

4. Page-by-Page Instructions
A. Home Page
Purpose: Main entry point for the application.
Required options:
Log In
Register
B. Login Page
Required fields:
Email
Password
Buttons:
OK
Cancel
Requirements:
Authenticate the user's credentials.
Identify the user's assigned role.
Redirect the user to the appropriate homepage based on their role.
C. Registration Page
Required fields:
Phone
First Name
Last Name
Email ID
Date of Birth
Family Code (for Patient Family Members)
Role
Relation to Emergency Contact
Emergency Contact
Password
Buttons:
OK
Cancel
Requirements:
Additional Patient Information only appears when the selected role is Patient.
Patient registrations require additional information.
Registration must be approved by an Admin or Supervisor before the account is fully authorized.
D. Additional Patient Information
Required fields:
Group
Patient ID
Admission Date
Patient Name
Requirements:
Patient Name automatically appears after entering the Patient ID.
Patient Name cannot be manually edited.
This page is accessed by Admin and Supervisor after registration approval.
Note: The presentation uses the word "student" when describing registration approval. Confirm whether this is intended to mean patient.
E. Doctor's Appointment
Required fields:
Doctor
Date
Appointment Time
Patient ID
Patient Name
Buttons:
OK
Cancel
Requirements:
Admin and Supervisor can access this page.
Selecting a date determines which doctors appear in the Doctor dropdown.
Only doctors assigned to the roster for the selected date should appear.
Patient Name automatically appears after entering the Patient ID.
Patient Name cannot be edited manually.
A doctor can have multiple patients scheduled on the same day.
A patient can only have one doctor per day.
Appointment availability must be checked using the selected date and time.
The system must prevent conflicting or invalid appointments.
Important restriction: A patient cannot schedule appointments with different doctors on the same day. They may see a different doctor on another day.
F. Role Management
Required fields:
Role
Access Level
New Role
Buttons:
OK
Cancel
Requirements:
Only Admin can access this page.
Admin can view existing roles and their access levels.
Admin can add new roles.
Access levels determine which pages and functions each role can use.
G. Patient's Home
Access: Patient only
Displays:
Patient ID
Patient Name
Doctor's Name
Doctor's Appointment
Caregiver's Name
Morning Medicine
Afternoon Medicine
Night Medicine
Breakfast
Lunch
Dinner
Date
Requirements:
Automatically displays the logged-in patient's own information.
Today's date appears by default.
Patients can change the date to view information for other days.
Information updates based on the selected date.
H. Employee Page
Access: Admin and Supervisor
Displays:
ID
Name
Role
Salary
Additional fields:
Employee ID
New Salary
Requirements:
Display the current employee list.
Include search and filtering options for employee attributes.
Admin and Supervisor can view employee information.
Only Admin can change employee salaries.
Supervisor cannot modify salary information.
Search and filtering examples:
Search by Employee ID.
Search by Name.
Filter by Role.
Filter by Salary, if appropriate.
I. Patients Page
Access: Admin, Supervisor, Doctors, and Caregivers
Displays:
ID
Name
Age
Emergency Contact
Emergency Contact Name
Admission Date
Requirements:
Display registered patients.
Include search options for each attribute.
Restrict access according to the user's role.
J. Registration Approval
Access: Admin and Supervisor
Displays:
Name
Role
Buttons:
OK
Cancel
Requirements:
Display registrations awaiting approval.
Allow Admin and Supervisor to review registrations.
Approved registrations should proceed to the appropriate account setup process.
K. Roster Page
Access: Everyone
Displays:
Supervisor
Doctor
Caregiver 1
Caregiver 2
Caregiver 3
Caregiver 4
Patient Group
Date
Requirements:
Display employee assignments for a selected date.
Show which caregivers are responsible for which patient groups.
Display the assigned Supervisor and Doctor.
Allow everyone to view the roster.
L. New Roster
Access: Admin and Supervisor
Required fields:
Supervisor
Date
Doctor
Caregiver 1
Caregiver 2
Caregiver 3
Caregiver 4
Buttons:
OK
Cancel
Requirements:
Create a roster for a selected date.
Assign employees to the appropriate positions.
Assign caregivers to patient groups.
Make the roster available for viewing on the Roster Page.
M. Doctor's Home
Access: The specific logged-in Doctor
Contains two primary sections.
Upcoming Appointments
Displays:
Patient
Date
Requirements:
Display appointments from today until the specified future date.
Include search options for each attribute.
Previous Appointments
Displays:
Patient Name
Date
Comment
Morning Medicine
Afternoon Medicine
Night Medicine
Requirements:
Display previous appointments for the doctor's patients.
Include a button beside each patient's name.
Clicking the button opens the Patient of Doctor page.
Include a Submit button where appropriate.
N. Patient of Doctor
Access: The specific logged-in Doctor
Displays previous information:
Date
Comment
Morning Medicine
Afternoon Medicine
Night Medicine
New Prescription section:
Comment
Morning Medicine
Afternoon Medicine
Night Medicine
Buttons:
OK
Cancel
Requirements:
The doctor can view previous prescriptions.
New prescriptions are only available on the actual appointment date.
The doctor must meet with the patient before approving a new prescription.
The doctor must approve the prescription before it becomes active.
A prescription must not become active simply because a doctor entered information in advance.
Only one medication can be assigned to each time-of-day category.
Medication restrictions:
Morning: One medication maximum.
Afternoon: One medication maximum.
Night: One medication maximum.
O. Caregiver's Home
Access: The specific logged-in Caregiver
Displays:
List of patients assigned to the caregiver that day.
Patient Name
Morning Medicine
Afternoon Medicine
Night Medicine
Breakfast
Lunch
Dinner
Buttons:
OK
Cancel
Requirements:
Caregivers should only see patients assigned to them for that day.
Daily activities must be associated with the correct patient and date.
Caregivers record completed medication and meal activities.
Activities that are not completed by their scheduled time are automatically marked as missed.
Caregivers cannot go back and correct missed activities after the fact.
Missed activities remain recorded for Admin and Supervisor reporting.
Suggested activity statuses:
Pending
Completed
Missed
Important: The caregiver's page should not function as an editable historical activity log.
P. Family Member's Home
Access: Family Members
Required fields:
Date
Patient ID
Family Code
Displays:
Doctor's Name
Doctor's Appointment
Caregiver's Name
Morning Medicine
Afternoon Medicine
Night Medicine
Breakfast
Lunch
Dinner
Requirements:
Family members must enter both Patient ID and Family Code.
The system must verify that the Family Code corresponds to the entered Patient ID.
Family members can view the patient's information.
Information should update according to the selected date.
Q. Admin's Report
Access: Admin and Supervisor
Required field:
Date
Displays:
Patient's Name
Doctor's Name
Doctor's Appointment
Caregiver's Name
Morning Medicine
Afternoon Medicine
Night Medicine
Breakfast
Lunch
Dinner
Critical requirements:
The report should display missed patient activities.
Examples:
Missed morning medication.
Missed afternoon medication.
Missed night medication.
Missed breakfast.
Missed lunch.
Missed dinner.
The report should help Admin and Supervisor identify which activities were not completed.
R. Payment Page
Access: Admin only
Required fields:
Total Due
New Payment
Patient ID
Buttons:
OK
Cancel
Update
Pricing structure:
Service
Cost
Daily care
$10 per day of care
Doctor's appointment
$50 per appointment
Medication
$5 per active medication per month

Requirements:
Admin enters or selects the Patient ID.
Admin presses Update.
The system checks the current system date.
The system compares it against the previous payment update date.
If the dates differ, the system calculates the charges accumulated between those dates.
Calculated charges are added to the patient's existing balance.
The previous update date is updated to prevent duplicate billing.
Daily Care Charges
Patients are charged $10 only for days they actually receive care.
Days without care should not generate daily care charges.
Actual care records should be used when calculating charges.
Appointment Charges
Each appointment costs $50.
Each patient's individual appointment is charged separately.
Medication Charges
Each active medication costs $5 per month.
Morning, afternoon, and night medications are counted separately.
Inactive or discontinued medications should not generate future monthly charges.
Payment Method
Cash only for the initial project.
Credit/debit cards and online payments are optional future additions.
Additional payment methods are not required for this project.

5. Important System-Wide Requirements
A. Search Bars and Filtering
Search and filtering are recurring requirements throughout the project.
Pages that explicitly require search functionality:
Employee Page.
Patients Page.
Doctor's Home (appointments and previous records).
Other pages where filtering may be useful:
Roster Page.
Admin's Report.
Payment records.
Examples:
Search by name.
Search by ID.
Filter by role.
Filter by date.
Filter by appointment status.
Development recommendation: Create reusable search and filtering functionality instead of writing completely separate implementations for every page.
B. Dropdown Menus
Dropdowns are important throughout the project.
Examples:
Role selection during registration.
Doctor selection when scheduling appointments.
Caregiver selection when creating rosters.
Supervisor selection when creating rosters.
Patient selection where applicable.
Some dropdowns must be dynamic.
For example, the Doctor dropdown must update based on the selected appointment date and display only eligible doctors.
C. Date and Time Handling
Dates and times play a significant role in the system.
The application must account for:
Patient daily schedules.
Doctor availability.
Doctor appointments.
Caregiver assignments.
Medication schedules.
Missed activities.
Payment calculations.
Date selection should determine which records are retrieved from the database, not simply change what appears on the screen.
D. User Permissions
Every page must check the user's role before allowing access.
Examples:
A Supervisor can view employee salaries but cannot change them.
A Doctor can manage prescriptions but cannot access Admin-only payment functions.
A Caregiver can view assigned patients but cannot manage doctor appointments.
A Patient can only view their own information.
A Family Member must verify their access using the Patient ID and Family Code.

6. Database Planning and Structure
Database planning should happen before implementing too many pages.
The following are suggested database tables based on the project requirements. They are recommendations, not a finalized database design.
Suggested Table
Information Stored
Users
Login credentials and account information
Roles
User roles and access levels
Patients
Patient information and admission dates
Employees
Employee information and salaries
FamilyMembers
Family codes and patient relationships
Rosters
Daily employee assignments
Appointments
Doctor, patient, date, and time
Prescriptions
Medication and doctor comments
Medications
Medication details and active status
DailyActivities
Scheduled meals and medication activities
ActivityRecords
Completion and missed activity status
Payments
Patient balances and payment history
BillingRecords
Individual charges and billing periods

Suggested Relationships
One patient can have multiple appointments.
One doctor can have multiple appointments.
One doctor can have multiple patients.
One patient can have multiple prescriptions.
One caregiver can be assigned to multiple patients.
One patient can have multiple daily activity records.
One patient can have multiple payment records.
One employee has one assigned role, assuming multiple roles are not required.
Important Database Considerations
1. Avoid Unnecessary Duplication
Instead of repeatedly storing a patient's name in every appointment record, store the Patient ID and retrieve the name through the relationship.
2. Use Foreign Keys
Foreign keys help ensure appointments, prescriptions, and activities belong to valid patients and employees.
3. Store Dates and Times Appropriately
Doctor appointments need both a date and a time. Daily activities should also be associated with their intended date and scheduled time.
4. Preserve Historical Records
Previous appointments, prescriptions, payment transactions, and missed activities should remain available when viewing historical information.
5. Consider Activity Statuses
Daily activities could have statuses:
Pending
Completed
Missed
6. Track Medication Status
Medication records should distinguish between active and inactive medications to support monthly billing.
7. Prevent Duplicate Appointments
Consider database constraints and backend validation to enforce the one-doctor-per-patient-per-day rule.
8. Maintain Billing History
Individual charges should be recorded so the system can avoid billing the same period twice.

7. Important System Workflows
These workflows connect multiple pages together.
Workflow 1: Registration and Patient Setup
User opens Registration Page.
User enters personal information and selects a role.
If the role is Patient, additional patient information is collected.
Registration is submitted for approval.
Admin or Supervisor reviews the registration.
Once approved, the appropriate account setup process continues.
Workflow 2: Creating a Roster
Admin or Supervisor opens New Roster.
Selects the date.
Assigns the Supervisor and Doctor.
Assigns up to four Caregivers.
Assigns patient groups to caregivers.
Saves the roster.
Employees and other users can view the roster.
Workflow 3: Scheduling a Doctor's Appointment
Admin or Supervisor opens Doctor's Appointment.
Selects the appointment date and time.
System checks the roster and doctor's availability.
Eligible doctors appear in the dropdown.
Admin or Supervisor selects the doctor.
Enters the Patient ID.
Patient Name automatically appears.
System checks whether the patient already has an appointment with another doctor that day.
Appointment is saved if all restrictions are satisfied.
Workflow 4: Prescribing Medication
Doctor opens Doctor's Home.
Selects a patient.
Views previous prescriptions and medical comments.
Opens Patient of Doctor.
On the actual appointment date, new prescription functionality becomes available.
Doctor meets with the patient and enters the new prescription.
Doctor approves the prescription.
The approved prescription becomes active.
Workflow 5: Recording Daily Patient Activities
Caregiver logs in.
System identifies the caregiver's assigned patients for that day.
Caregiver views medication and meal schedules.
Caregiver records completed activities.
Activities that are not completed by their scheduled times are automatically marked as missed.
Historical missed activities remain available for Admin and Supervisor reporting.
Workflow 6: Calculating Patient Payments
Admin opens Payment Page.
Selects or enters the Patient ID.
Presses Update.
System checks the previous update date and current system date.
Calculates daily care, appointment, and medication charges accumulated since the previous update.
Adds calculated charges to the patient's existing balance.
Updates the previous update date.
Stores billing records to prevent duplicate charges.

8. Development Priorities and Potential Challenges
Recommended development order:
Priority
Development Area
Importance
1
Database design
Foundation for everything else
2
Registration and login
Establishes users and roles
3
Role-based permissions
Controls access throughout the system
4
Employee, patient, and roster management
Establishes assignments
5
Appointment scheduling
Requires roster and availability information
6
Prescriptions
Depends on appointments and doctor approval
7
Caregiver activities
Depends on rosters and prescriptions
8
Patient and family pages
Displays stored information
9
Admin reports
Depends on activity tracking
10
Payments
Requires historical records and calculations

Features That Deserve Extra Attention
Doctor Scheduling
The system must check the selected date AND time, not just whether a doctor appears on the roster.
Prescription Activation
A prescription cannot become active until the patient has met with the doctor and the doctor has approved it.
Missed Activities
Missed activities must be automatically detected and preserved for reporting. Caregivers cannot retroactively correct them.
Payment Calculations
Ensure the system does not accidentally charge patients twice when Admin presses Update more than once.
Search and Filtering
Reusable search and filtering features should be developed early because they appear throughout the application.
Medication Restrictions
Only one medication can be assigned to each time-of-day category. Medication records must also maintain their active or inactive status for billing purposes.

9. Additional Development Recommendations
These are optional implementation suggestions, not additional confirmed project requirements.
Laravel Migrations: Make database tables and relationships easier to create and modify.
Laravel Eloquent: Simplify database queries and relationships.
Laravel Validation: Prevent missing, invalid, or incorrectly formatted information.
Reusable Blade Components: Avoid rewriting the same dropdowns, tables, search bars, and navigation elements.
Database Transactions: Particularly useful when approving prescriptions or processing payment updates.
Role-Based Middleware: Prevent unauthorized access to sensitive pages.
Laravel Scheduler: Automatically identify activities whose scheduled times have passed and mark them as missed.
Database Constraints: Help enforce appointment and medication restrictions.
Historical Records: Preserve previous prescriptions, activities, appointments, and payment transactions.
Testing: Test date restrictions, user permissions, and billing calculations before finalizing the application.

10. Confirmed Project Rules
The following requirements have been clarified and should be treated as established project rules.
Feature
Confirmed Rule
Missed activities
Automatically marked missed after scheduled time
Caregiver corrections
Cannot go back and fix missed activities
Doctor appointments
One doctor per patient per day
Doctor availability
Based on roster, date, and time
Multiple patients
A doctor can have multiple patients per day
Medication schedule
One medication per time slot
Prescription activation
Requires appointment, meeting, and doctor approval
Daily care billing
Only days the patient receives care
Appointment billing
$50 per appointment
Medication billing
$5 per active medication per month
Payment method
Cash only


11. Questions Still Worth Clarifying
Although the major project rules have been established, a few implementation details remain unspecified.
Missed activity deadlines: What exact time should breakfast, lunch, dinner, and each medication become missed?
Doctor scheduling: Can a doctor have two appointments at exactly the same time, or must appointment times be exclusive?
Medication billing: How should partial months be charged when medication is started or discontinued halfway through a month?
Daily care: How will the system determine whether a patient actually received care on a particular day?
Prescription changes: Can an approved prescription be discontinued or replaced before the end of a month?
These details can be decided with the project team before implementing the affected functionality.

12. Final Development Checklist
Use this checklist to track the application's progress.
Plan SQL database and relationships.
Create registration and login functionality.
Implement user roles and permissions.
Create patient and employee management pages.
Implement search bars and filtering.
Create roster management.
Implement doctor scheduling and availability checks.
Enforce one doctor per patient per day.
Create appointment history.
Implement prescription approval and activation.
Enforce one medication per time slot.
Create caregiver daily activity tracking.
Implement automatic missed-activity detection.
Create patient and family member pages.
Implement missed activity reporting.
Implement payment calculations.
Prevent duplicate billing.
Test date and time restrictions.
Test permissions for every user role.
Test the application using sample patient and employee records.

Final Recommendation
Treat this document as the primary reference for the entire development process.
The most important thing is understanding how the pages interact with each other.
For example:
Doctor's Appointment depends on the Roster.
Patient of Doctor depends on the Appointment.
Caregiver's Home depends on the Roster and approved prescriptions.
Admin's Report depends on the Caregiver's activity records.
Payment Page depends on historical care, appointment, and medication records.
Getting those relationships right will make the actual Laravel and SQL development considerably easier.
Recommended next step: Design the SQL database before building the individual pages. This will establish the relationships needed by the entire application and reduce the amount of restructuring required later.

