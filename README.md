# StaffHub — Workforce Time-Tracking and Salary Processing Portal

A complete Employee Management System built with **PHP (OOP) + MySQL (PDO) + AJAX + Bootstrap 5**.

## ✨ Features

- **Employee Management (CRUD)** — full employee records with linked login accounts, profile pictures, and soft activate/deactivate.
- **Smart Timekeeping** — Clock In / Clock Out with automatic hour computation, duplicate-session prevention, incomplete-record detection, and searchable/filterable attendance history.
- **Salary Processing Manager** — computes `Net Salary = (Verified Hours × Hourly Rate) + Bonuses − Deductions` from **verified** attendance only, with a live AJAX preview before committing, single-employee or batch processing, and printable payslips.
- **Admin Dashboard** — live stat cards + Chart.js widgets (daily attendance, monthly payroll expense, department distribution) that auto-refresh every 30 seconds.
- **Employee Dashboard** — real-time punch clock, today's status, recent history, and profile summary.
- **Role-based Access Control** — Administrator vs Employee, enforced server-side on every page and AJAX endpoint.
- **Reports** — printable Attendance Report, Payroll Report, and Employee List, each with filters.
- **AJAX everywhere it matters** — employee search/CRUD, clock in/out, payroll preview & processing, attendance verification, department filtering, dashboard refresh — all without full page reloads.

## 🗂 Folder Structure

```
StaffHub/
├── assets/{css,js,images}
├── classes/            # Database, User, Authentication, Employee, Attendance,
│                        # Payroll, Department, Position, Dashboard, Validator, Logger
├── config/database.php # DB connection constants — EDIT THIS FIRST
├── ajax/                # attendance.php, employee.php, payroll.php, department.php, search.php
├── admin/                # dashboard, employees, departments, attendance, payroll
├── employee/             # dashboard, attendance_history, payroll_history, profile
├── reports/              # attendance_report, payroll_report, employee_report
├── includes/              # bootstrap.php, header.php, footer.php
├── uploads/profile_pictures/
├── index.php              # login page
├── logout.php
└── database.sql           # full schema + seed data
```

## 🚀 Setup

1. **Create the database.** Import `database.sql` into MySQL:
   ```bash
   mysql -u root -p < database.sql
   ```
   This creates `staffhub_db` with all tables, foreign keys, sample departments/positions, and two ready-to-use accounts.

2. **Configure the connection.** Edit `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'staffhub_db');
   define('DB_USER', 'root');
   define('DB_PASS', '');          // your MySQL password
   define('APP_URL', 'http://localhost/StaffHub'); // update if deployed elsewhere
   ```

3. **Serve the app.** Place the `StaffHub/` folder in your web server root (e.g. `htdocs/` for XAMPP, or `www/` for WAMP/MAMP), then visit:
   ```
   http://localhost/StaffHub/
   ```

4. **Make `uploads/profile_pictures/` writable** by the web server (e.g. `chmod 755 uploads/profile_pictures`).

## 🔑 Demo Accounts

| Role       | Username    | Password      |
|------------|-------------|---------------|
| Admin      | `admin`     | `Admin@123`   |
| Employee   | `jdelacruz` | `Employee@123`|
| Employee   | `mgarcia`   | `Employee@123`|

**Change these credentials (or delete the seed accounts) before using this system beyond a local demo.**

## 🧱 OOP Design Notes (for Q&A / presentation)

- **`Database`** — Singleton PDO wrapper; every other class receives its connection through `Database::getInstance()`, so the whole app shares one connection and every query goes through prepared statements.
- **`User`** — owns password hashing (`password_hash`/`password_verify`) and the `users` table; nothing else in the app touches a password directly.
- **`Authentication`** — composes a `User` (favor composition over inheritance) to provide login/logout, session lifecycle, and the `requireLogin()` guard used by every protected page and AJAX endpoint for role-based access control.
- **`Employee`** — the central entity; encapsulates every employee field as a typed property, hydrates itself from a DB row in its constructor, and wraps a `User` account creation inside a DB transaction (`create()`), so an employee and their login account are never left inconsistent.
- **`Attendance`** — the "Smart Timekeeping" engine: `clockIn()`/`clockOut()` enforce one open session per employee per day, auto-compute `total_hours`, and only `verify()`/`verifyRange()` (admin-only) promote a record to `Verified`, the only status Payroll will read.
- **`Payroll`** — reads only `Verified` hours, exposes a `preview()` method used by the AJAX computation-preview feature, and `process()`/`processBatch()` which upsert into the `payroll` table (`ON DUPLICATE KEY UPDATE`) so re-running a period updates rather than duplicates.
- **`Dashboard`** — a thin aggregator composing `Employee`, `Attendance`, `Payroll`, `Department`, and `Logger` to build both the admin and employee dashboard data in one call each, keeping page scripts free of business logic.
- **`Validator`** — a small fluent/chainable rule engine (`required()->email()->numeric()...`) reused by every create/update form handler in `ajax/*.php`, returning field-keyed errors ready to serialize as JSON.
- **`Logger`** — writes to `activity_logs` and powers the "Recent Activity" dashboard widget; called from `Authentication`, and every mutating AJAX action.

## 🔒 Security

- All database access goes through PDO prepared statements (no string-concatenated SQL).
- Passwords are hashed with `password_hash()` / verified with `password_verify()` — never stored or compared in plaintext.
- Every AJAX endpoint calls `Authentication::requireLogin()` (with a role check where relevant) before touching data.
- Uploaded profile pictures are validated by MIME type and size, and renamed on save to prevent path traversal / overwrite attacks.
- Session ID is regenerated on login to mitigate session fixation.
