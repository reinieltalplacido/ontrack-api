# OnTrack API

PHP 8+ REST backend for the **OnTrack OJT Monitoring** Ionic/Android application, backed by MySQL/MariaDB via PDO.

## Live Deployment

- **Base API URL (`apiUrl`):** `https://ontrack-api.x10.network/ontrack-api/api`
- **Health Check:** https://ontrack-api.x10.network/ontrack-api/
- **Coordinators JSON:** https://ontrack-api.x10.network/ontrack-api/api/coordinators.php
- **Students JSON:** https://ontrack-api.x10.network/ontrack-api/api/students.php

## Features

- Student authentication, registration (with automatic section/campus coordinator matching), profile updates, and password management
- GPS-validated clock-in/clock-out attendance tracking, shift history, and hour accumulation
- OJT task management with proof image/file uploads and coordinator verification
- Weekly/final report submissions with attachments and adviser review workflow
- Coordinator/Professor dashboard endpoints scoped by assigned section and student roster
- Read-only public JSON endpoints (`/api/coordinators.php`, `/api/students.php`) and CORS headers preconfigured for mobile WebView clients

## Project Structure

```text
ontrack-api/
├── index.php                  # Health check endpoint
├── ontrack_db.sql             # Database schema & clean seed data
├── uploads/                   # Uploaded attendance photos, task proofs, and report files
└── api/
    ├── config.php             # PDO DB connection (env vars + local XAMPP fallback)
    ├── common.php             # CORS headers, JSON response helpers, section resolver, upload helper
    ├── coordinators.php       # GET coordinators list or section/campus match
    ├── students.php           # GET students list or single student lookup
    ├── auth/                  # Student login, register, profile update, password change
    ├── dashboard/             # Student dashboard stats
    ├── attendance/            # Clock in, clock out, attendance history
    ├── tasks/                 # Task CRUD & proof uploads
    ├── reports/               # Report submission & listing
    └── admin/                 # Coordinator/Admin auth, dashboard, students, attendance, tasks, reports
```

## Requirements

- **PHP** `8.0+` (uses `str_starts_with()` and `str_contains()`) with `pdo` and `pdo_mysql` extensions
- **MySQL** `5.7+` / `8.0+` or **MariaDB** `10.4+`

## Local Setup (XAMPP)

1. Place this folder at `C:\xampp\htdocs\ontrack-api` (or clone into your web root).
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open **phpMyAdmin** (`http://localhost/phpmyadmin`), create a database named `ontrack_db`, and import `ontrack_db.sql`.
4. By default, `api/config.php` connects to `127.0.0.1:3306` (`ontrack_db`, user `root`, empty password) unless environment variables override it.
5. Verify the API in your browser:
   - `http://localhost/ontrack-api/`
   - `http://localhost/ontrack-api/api/coordinators.php`
   - `http://localhost/ontrack-api/api/students.php`

## Environment Configuration

`api/config.php` checks these environment variables before falling back to local XAMPP defaults:

- `MYSQL_URL` / `MYSQL_PUBLIC_URL` / `DATABASE_URL`
- Or individual keys: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` (also supports `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`, `MYSQLPASSWORD`)

Session time zone is automatically set to `+08:00` (`Asia/Manila`).

## Deploying to Shared Hosting (e.g., x10Hosting)

1. Import `ontrack_db.sql` into your hosting MySQL/MariaDB database via **phpMyAdmin**.
2. Upload only **`index.php`**, **`api/`**, and **`uploads/`** into your web folder (for example, `public_html/ontrack-api/`). Do **not** deploy `ontrack_db.sql` or `.git/` publicly.
3. Update the database defaults in the server's `api/config.php` (use `localhost` as host when connecting over a Unix socket) or configure environment variables on the host.
4. Test `https://<your-domain>/ontrack-api/` and `https://<your-domain>/ontrack-api/api/coordinators.php`.

## API Endpoints

- **Base URL:** `https://ontrack-api.x10.network/ontrack-api/api`
- **Health Check:** `GET` `https://ontrack-api.x10.network/ontrack-api/`

### Directory & Lookup
| Method | Endpoint URL | Description |
| --- | --- | --- |
| `GET` | `https://ontrack-api.x10.network/ontrack-api/api/coordinators.php` | List all professors/coordinators (`?section=`, `?course=`, `?campus=`, `?year_level=`) |
| `GET` | `https://ontrack-api.x10.network/ontrack-api/api/students.php` | List all students (`?student_no=`, `?id=`, `?section=`, `?professor_id=`, `?search=`) |

### Student Endpoints
| Method | Endpoint URL | Description |
| --- | --- | --- |
| `POST` | `https://ontrack-api.x10.network/ontrack-api/api/auth/login.php` | Student or coordinator login |
| `POST` | `https://ontrack-api.x10.network/ontrack-api/api/auth/register.php` | Student registration with automatic coordinator assignment |
| `POST` | `https://ontrack-api.x10.network/ontrack-api/api/auth/update_profile.php` | Update student profile fields |
| `POST` | `https://ontrack-api.x10.network/ontrack-api/api/auth/change_password.php` | Change student password |
| `GET` | `https://ontrack-api.x10.network/ontrack-api/api/dashboard/stats.php` | Student OJT progress & hours summary |
| `POST` | `https://ontrack-api.x10.network/ontrack-api/api/attendance/clock_in.php` | Clock in with GPS coordinates, notes, and optional photo |
| `POST` | `https://ontrack-api.x10.network/ontrack-api/api/attendance/clock_out.php` | Clock out and compute rendered hours |
| `GET` | `https://ontrack-api.x10.network/ontrack-api/api/attendance/history.php` | Fetch student attendance logs |
| `GET` | `https://ontrack-api.x10.network/ontrack-api/api/tasks/list.php` | List tasks for a student |
| `POST` | `https://ontrack-api.x10.network/ontrack-api/api/tasks/create.php` | Create a new task |
| `POST` | `https://ontrack-api.x10.network/ontrack-api/api/tasks/update.php` | Update task status or upload proof |
| `POST` | `https://ontrack-api.x10.network/ontrack-api/api/tasks/delete.php` | Delete a task |
| `GET` | `https://ontrack-api.x10.network/ontrack-api/api/reports/list.php` | List student submitted reports |
| `POST` | `https://ontrack-api.x10.network/ontrack-api/api/reports/submit.php` | Submit an OJT report with optional attachment |

### Coordinator / Admin Endpoints
| Method | Endpoint URL | Description |
| --- | --- | --- |
| `POST` | `https://ontrack-api.x10.network/ontrack-api/api/admin/auth/login.php` | Coordinator/Professor login (`employee_id` or `email`) |
| `POST` | `https://ontrack-api.x10.network/ontrack-api/api/admin/auth/register.php` | Register a new coordinator/professor account |
| `GET` | `https://ontrack-api.x10.network/ontrack-api/api/admin/dashboard/stats.php` | Coordinator overview stats |
| `GET` | `https://ontrack-api.x10.network/ontrack-api/api/admin/students/list.php` | Filterable student roster with duty status and hours |
| `GET` | `https://ontrack-api.x10.network/ontrack-api/api/admin/students/detail.php` | Individual student detail view |
| `GET` | `https://ontrack-api.x10.network/ontrack-api/api/admin/attendance/list.php` | Monitor student attendance logs |
| `GET` | `https://ontrack-api.x10.network/ontrack-api/api/admin/tasks/list.php` | Review student tasks |
| `POST` | `https://ontrack-api.x10.network/ontrack-api/api/admin/tasks/assign.php` | Assign task to student(s) |
| `POST` | `https://ontrack-api.x10.network/ontrack-api/api/admin/tasks/verify.php` | Verify submitted task proof |
| `GET` | `https://ontrack-api.x10.network/ontrack-api/api/admin/reports/list.php` | List student report submissions |
| `POST` | `https://ontrack-api.x10.network/ontrack-api/api/admin/reports/review.php` | Mark report as reviewed/approved |

## Seeded Demo Accounts (`ontrack_db.sql`)

`ontrack_db.sql` seeds 25 section coordinators (`professors`) and 1 default test student (`students`), with empty `attendance_logs`, `tasks`, and `submitted_reports`:

- **Default Student:** Student No. `2026-0001` (`BSIT 4-A`) / Password `Password123!`
- **Lead Admin (`BSIT 4-A`):** `admin@neust.edu.ph` (`PROF-2026-001`) / Password `AdminPassword123!`
- **BSIT 4-E Coordinator:** `angelito.cunanan@neust.edu.ph` (`PROF-2026-005`) / Password `AdminPassword123!`

