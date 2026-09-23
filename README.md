# StaffHub — Workforce Time-Tracking and Salary Processing Portal

A complete Employee Management System built with **PHP 8.1+ (OOP MVC) + MySQL (PDO) + AJAX + Bootstrap 5**.

## Features

- **Employee Management (CRUD)** — full employee records with linked login accounts, profile pictures, and soft activate/deactivate.
- **Smart Timekeeping** — Clock In / Clock Out with automatic hour computation, duplicate-session prevention, incomplete-record detection, and searchable/filterable attendance history.
- **Salary Processing Manager** — computes `Net Salary = (Verified Hours × Hourly Rate) + Bonuses − Deductions` from **verified** attendance only, with a live AJAX preview before committing, single-employee or batch processing, and printable payslips.
- **Admin Dashboard** — live stat cards + Chart.js widgets (daily attendance, monthly payroll expense, department distribution) that auto-refresh every 30 seconds.
- **Employee Dashboard** — real-time punch clock, today's status, recent history, and profile summary.
- **Role-based Access Control** — Administrator vs Employee, enforced server-side middleware on every page and JSON API route.
- **Reports** — printable Attendance Report, Payroll Report, and Employee List, each with filters.
- **AJAX everywhere it matters** — employee search/CRUD, clock in/out, payroll preview & processing, attendance verification, department filtering, dashboard refresh — all against `/api/*` without full page reloads.

## 🗂 Folder Structure

```
StaffHub/
├── bootstrap.php         # autoload, config, DI container wiring, session, router
├── composer.json         # PSR-4: StaffHub\ → src/
├── config/
│   └── database.php      # DB credentials + APP_URL (auto-derived) + upload paths
├── public/               # ← preferred web document root
│   ├── index.php         # front controller (only PHP entry the server runs)
│   ├── .htaccess         # rewrite everything → index.php
│   ├── assets/{css,js}
│   ├── uploads/profile_pictures/
│   └── logo wo text.png
├── src/
│   ├── Auth/             # AuthService, AuthMiddleware
│   ├── Controller/       # Auth, Admin, Employee, Report, Api controllers
│   ├── Core/             # Application, Container, Database, Request, Response, Router, Controller
│   ├── Entity/           # User, Employee, Attendance, Payroll, Department, Position
│   ├── Repository/       # concrete repos + Contract/ interfaces
│   ├── Route/routes.php  # full route table
│   ├── Service/          # EmployeeService, AttendanceService, PayrollService, DashboardService, …
│   └── Support/          # Validator, helpers (e, money, url)
├── views/
│   ├── layouts/          # shell, header, footer
│   ├── auth/ admin/ employee/ reports/
├── database.sql          # schema + seed data
├── seed_data.sql
└── README.md
```

## Setup

1. **Create the database.** Import `database.sql` (and optionally `seed_data.sql`) into MySQL:
   ```bash
   mysql -u root -p < database.sql
   ```
   This creates `staffhub_db` with all tables, foreign keys, sample departments/positions, and demo accounts.

2. **Configure the connection.** Edit `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'staffhub_db');
   define('DB_USER', 'root');
   define('DB_PASS', '');          // your MySQL password
   ```
   `APP_URL` is derived automatically from the request — no need to hardcode it.

3. **Serve the app.** Point the web server document root at **`public/`** (preferred):
   ```
   http://localhost/StaffHub/     # if docroot = .../StaffHub/public
   ```
   Alternatively, keep the docroot as the project folder — the root `.htaccess` forwards missing paths into `public/`.

4. **Make uploads writable:**
   ```
   chmod 755 public/uploads/profile_pictures
   ```

5. **Optional (Composer):** if Composer is available:
   ```bash
   composer install
   ```
   Without Composer, `bootstrap.php` falls back to a built-in PSR-4 autoloader.

## Demo Accounts

| Role       | Username    | Password      |
|------------|-------------|---------------|
| Admin      | `admin`     | `Admin@123`   |
| Employee   | `jdelacruz` | `Employee@123`|
| Employee   | `mgarcia`   | `Employee@123`|

**Change these credentials (or delete the seed accounts) before using this system beyond a local demo.**

## Architecture (OOP MVC)

- **Front controller + router** — `public/index.php` boots `bootstrap.php`, which builds the DI `Container` and `Router` (route table in `src/Route/routes.php`). `Application::run()` does match → middleware → controller → response.
- **DI container** — `Container` supports shared `set()` bindings (interfaces → concrete classes) plus reflection-based constructor autowiring for everything else.
- **Entity** — plain typed value objects (`fromRow` / `toArray`); `Payroll::compute()` holds the net-salary formula.
- **Repository** — one interface + one PDO implementation per table (`Contract\*RepositoryInterface` → concrete class). All SQL lives here, always via prepared statements.
- **Service** — business logic (`EmployeeService`, `AttendanceService`, `PayrollService`, `DashboardService`, `ReportService`, `OrganizationService`, `ProfileService`, `Logger`). Controllers stay thin.
- **Auth** — `AuthService` owns login/logout/session; `AuthMiddleware::requireLogin($role)` guards routes (returns JSON 403/401 for `/api/*`, HTML redirect for pages).
- **Views** — plain PHP templates under `views/`, composed by `Controller::render()` → `layouts/shell.php` → header + content + footer.
- **JSON API** — `/api/employees`, `/api/attendance`, `/api/payroll`, `/api/departments`, `/api/search` keep the same `?action=` contract the front-end JS already uses.

## Security

- All database access goes through PDO prepared statements (emulation off).
- Passwords are hashed with `password_hash()` / verified with `password_verify()`.
- Every protected route is gated by middleware before the controller runs.
- Uploaded profile pictures are validated by MIME type and size, and renamed on save.
- Session ID is regenerated on login to mitigate session fixation.
- Direct web access to `src/`, `views/`, `config/`, and `bootstrap.php` is denied via `.htaccess`.
