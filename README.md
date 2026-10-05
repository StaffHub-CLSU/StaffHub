# StaffHub

A Laravel-based HR and payroll management system for Philippine companies and institutions.

---

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP | ≥ 8.3 |
| Composer | ≥ 2.x |
| Node.js | ≥ 18.x (LTS) |
| npm | ≥ 9.x |
| MySQL | ≥ 8.0 (or MariaDB ≥ 10.6) |

---

## Local Setup

### 1. Clone the repository

```bash
git clone https://github.com/StaffHub-CLSU/StaffHub.git
cd StaffHub
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Install Node dependencies and build assets

```bash
npm install
npm run build
```

> **During development** run `npm run dev` instead of `npm run build` to start
> the Vite dev server with hot-module replacement.

### 4. Copy the environment file

```bash
cp .env.example .env
```

### 5. Generate the application key

```bash
php artisan key:generate
```

### 6. Configure the database

Open `.env` and set your MySQL credentials:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=staffhub_db   # create this database first
DB_USERNAME=root
DB_PASSWORD=               # your MySQL password
```

Create the database if it does not exist yet:

```sql
CREATE DATABASE staffhub_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 7. Run migrations and seed demo data

```bash
php artisan migrate:fresh --seed
```

This drops and recreates all tables, then runs the seeders in order:

1. **`RolesAndPermissionsSeeder`** — creates the `Admin` and `Employee` Spatie roles, all permissions, and the `admin@staffhub.test` user account.
2. **`DemoDataSeeder`** *(local / testing only)* — creates departments, positions, sample employees, attendance history, and payroll records (see [Demo Data](#demo-data) below).

### 8. Run the application

```bash
composer run dev
```

This starts the Laravel development server via `php artisan dev`. The app will
be available at the URL set in `APP_URL` (default: `http://localhost/StaffHub/public`
when served through a local web server such as Laragon or XAMPP).

If you are running `php artisan serve` directly, the app will be at
`http://localhost:8000`.

---

## Demo Data

After running `php artisan migrate:fresh --seed` in a `local` environment the
database will contain the following demonstration dataset:

| Category | Count | Detail |
|----------|-------|--------|
| Departments | 5 | Human Resources, Finance and Accounting, Information Technology, Operations, Administration |
| Positions | 10 | 2 per department — one managerial title + one rank-and-file title |
| Admin employee | 1 | Linked to `admin@staffhub.test`; placed in Administration |
| Managers | 5 | One per department (`manager()` factory state — higher pay, Employee role) |
| Regular employees | 14 | 9 Full-Time active, 3 On Leave, 2 Resigned/inactive |
| Attendance records | ~140 | 10 working days per active employee (7 Present, 1 Late, 1 Half-Day, 1 Absent) |
| Payroll records | ~19 | One record per active employee for the last completed semi-monthly period |

### Demo login credentials

| Field | Value |
|-------|-------|
| Email | `admin@staffhub.test` |
| Password | `Password123!` |

---

## Factories & Seeders Reference

> Intended for developers adding tests or new seed scripts.

### Factories (`database/factories/`)

#### `UserFactory`

Default: a verified, active user with a random username and email.

| State | Effect |
|-------|--------|
| `unverified()` | Sets `email_verified_at = null` |
| `inactive()` | Sets `is_active = false` |
| `admin()` | Assigns the `Admin` Spatie role after creation *(roles must exist)* |

#### `DepartmentFactory`

Default: picks one of 15 preset Philippine-company department names with an optional description.

| State | Effect |
|-------|--------|
| `withDescription()` | Forces a non-null description |

> **Note:** `department_name` has a database-level `UNIQUE` constraint. When
> seeding many departments use `firstOrCreate` with explicit names (as
> `DemoDataSeeder` does) rather than relying on the factory's random pick.

#### `PositionFactory`

Default: a random job title linked to a new `Department`.

| State | Effect |
|-------|--------|
| `managerial()` | Picks from managerial titles (e.g. `HR Manager`, `IT Manager`) |
| `supervisory()` | Picks from supervisory titles (e.g. `Team Leader`, `Section Head`) |
| `rank()` | Picks from rank-and-file titles (e.g. `Accounting Clerk`, `HR Officer`) |

> The unique constraint is `(position_name, department_id)`, so the same title
> may exist across different departments.

#### `EmployeeFactory`

Default: a Full-Time, active employee with Filipino-style name, PH mobile
number, and an address from Nueva Ecija / Metro Manila. `department_id` and
`position_id` are `null` by default — use a state or helper to wire them.

**Employment-status states**

| State | `employment_status` | `is_active` |
|-------|---------------------|-------------|
| `active()` | `Full-Time` | `true` |
| `inactive()` | `Resigned` | `false` |
| `onLeave()` | `On Leave` | `true` |
| `partTime()` | `Part-Time` | `true` |
| `contractual()` | `Contractual` | `true` |

**Role states** *(require roles to be seeded first)*

| State | Effect |
|-------|--------|
| `admin()` | Assigns `Admin` role to the linked `User` after creation |
| `manager()` | Sets a higher hourly rate (PHP 250–500/hr); assigns `Employee` role |

**Relationship helpers**

```php
// Wire to an existing department (and optionally a position)
Employee::factory()->forDepartment($dept, $position)->create();

// Auto-create a department + position and wire automatically
Employee::factory()->withDepartment()->create();
```

#### `AttendanceFactory`

Default: a `Present` record for today (8 AM–5 PM, 9 hours).

| State | `status` | `time_in` | `time_out` |
|-------|----------|-----------|------------|
| `present()` | `Present` | 08:00 | 17:00 |
| `absent()` | `Absent` | `null` | `null` |
| `late()` | `Late` | 09:01–10:59 | 17:00 |
| `halfDay()` | `Half-Day` | 08:00 | 12:00 |
| `incomplete()` | `Incomplete` | 08:00 | `null` |

Date helper:

```php
Attendance::factory()->forDate('2026-09-15')->present()->create([...]);
```

#### `PayrollFactory`

Default: a semi-monthly record for the most recently completed pay period.
`gross_salary = verified_hours × hourly_rate`.

| State | Effect |
|-------|--------|
| `firstHalf()` | Period = 1st–15th of the current month |
| `secondHalf()` | Period = 16th–end of the current month |
| `withBonus()` | Adds PHP 500–5,000 bonus; recomputes `net_salary` |
| `withDeductions()` | Applies SSS (4.5 %), PhilHealth (2.5 %), Pag-IBIG (PHP 100); recomputes `net_salary` |

---

### Seeders (`database/seeders/`)

| Seeder | Runs in | Purpose |
|--------|---------|---------|
| `RolesAndPermissionsSeeder` | all environments | Creates `Admin` / `Employee` roles, all permissions, and `admin@staffhub.test` |
| `DemoDataSeeder` | `local` and `testing` only | Populates departments, positions, employees, attendance, and payroll using the factories above |

**Seeding order is enforced in `DatabaseSeeder`:**

```
RolesAndPermissionsSeeder
└─ DemoDataSeeder (local/testing only)
     ├─ departments & positions
     ├─ admin employee record
     ├─ managers (one per department)
     ├─ regular employees (9 active / 3 on leave / 2 resigned)
     ├─ attendance (10 working days per active employee)
     └─ payroll (last completed semi-monthly period, with deductions)
```

---

## Running Tests

```bash
php artisan test
```

Or directly via PHPUnit:

```bash
vendor/bin/phpunit
```

---

## Code Style

PHP files are formatted with [Laravel Pint](https://laravel.com/docs/pint):

```bash
vendor/bin/pint
```

---

## License

The StaffHub application is open-sourced software licensed under the
[MIT license](https://opensource.org/licenses/MIT).

