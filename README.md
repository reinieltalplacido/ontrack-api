# Ionic + PHP Procedural Students REST API

A small learning/demo REST API for an Ionic Angular standalone app, using PHP and MySQL.

## Requirements
- PHP 7.4+ (PHP 8.x recommended)
- MySQL or MariaDB
- A PHP web server such as XAMPP or MAMP
- phpMyAdmin (optional, for importing the SQL file)

## Install
1. Download or clone this repository into your PHP server's document root.
2. Import `sql/students_api.sql` through phpMyAdmin. It creates the `ionic_students_api` database and `students` table with sample records.
3. Update database credentials in `api/config.php`. The default values in this demo are host `localhost`, database `ionic_students_api`, user `root`, and an empty password. MAMP commonly uses `root` as the password; use the credentials configured on your machine.
4. Start the web server and database service.
5. Open `/api/students.php` in a browser to test the GET endpoint.

## Endpoints
All endpoints use `api/students.php`.

| Method | URL | Purpose |
| --- | --- | --- |
| GET | `/api/students.php` | List all students |
| GET | `/api/students.php?id=1` | Get one student by database ID |
| POST | `/api/students.php` | Create a student |
| PUT | `/api/students.php?id=1` | Update a student |
| DELETE | `/api/students.php?id=1` | Delete a student |

The JSON fields are `student_no`, `full_name`, `course`, and `year_level`. The database `id` is auto-incremented. Student numbers must be unique.

## Example POST body
Send `Content-Type: application/json`:

```json
{
  "student_no": "2026-0100",
  "full_name": "Ana Example",
  "course": "BSIT",
  "year_level": 1
}
```

## HTTP status codes
- `200`: successful GET, PUT, or DELETE
- `201`: student created
- `400`: missing or invalid request input
- `404`: student not found
- `409`: duplicate student number
- `422`: validation failed
- `500`: server/database error

## CORS
The API includes a small development allow-list for common Ionic/Capacitor origins. Add your exact frontend origin to the allow-list in `api/students.php` when necessary. Avoid wildcard origins when credentials are used.

## Security note
This is a learning/demo API with no authentication. Do not expose it publicly with real student data until authentication, authorization, HTTPS, and appropriate security/privacy controls are added.
