# Vehicle Showroom Inventory System

A secure PHP/MySQL backend application for managing an automobile dealership inventory.

## Features
- Secure admin authentication using `password_hash()` and `password_verify()`
- Session-based access control on all CRUD pages
- Synchronous search via standard GET form submission
- Procedural prepared statements (mysqli) on every query — protects against SQL injection
- Image upload with validation, secure storage, and old-image cleanup via `unlink()`
- XSS protection via `htmlspecialchars()`
- Sticky forms that retain user input on validation errors
- Responsive design with Bootstrap 5 (mobile, tablet, desktop)

## Setup

1. **Start XAMPP** — make sure Apache and MySQL are running.
2. **Copy** the `vehicle_showroom` folder into `C:\xampp\htdocs\`.
3. **Open** your browser and go to:
   ```
   http://localhost/vehicle_showroom/login.php
   ```
4. The `db.php` file will **automatically** create the `showroom_db` database, both tables, and the default admin account on first run.

   *(Alternatively, you can manually import `showroom_db.sql` via phpMyAdmin first.)*

## Default Admin Login
- **Username:** `admin`
- **Password:** `admin123`

## File Structure
```
vehicle_showroom/
├── db.php              # DB connection + auto-create DB/tables/admin
├── login.php           # Secure login page
├── logout.php          # Destroys session
├── index.php           # Dashboard with synchronous GET search
├── form.php            # Unified Add/Edit form
├── delete.php          # Secure delete (unlinks image + record)
├── showroom_db.sql     # SQL backup file
├── README.md           # This file
└── uploads/            # Vehicle images stored here
```

## Phases Implemented

| Phase | Description | Status |
|-------|-------------|--------|
| 1 | Environment & DB initialization | ✓ |
| 2 | Secure authentication & sessions | ✓ |
| 3 | Dashboard & synchronous search | ✓ |
| 4 | Form processing, file handling, CRUD | ✓ |
| 7 | Responsive design & deployment | ✓ |

## Security Highlights
- All passwords hashed with `password_hash(PASSWORD_DEFAULT)`
- All queries use procedural prepared statements (`mysqli_prepare` + `mysqli_stmt_bind_param`)
- All user input sanitized via `htmlspecialchars()` (XSS mitigation)
- Image MIME type validated using `mime_content_type()`
- File size limit enforced (5MB)
- Session check at top of every protected page
- Old images physically removed from server using `unlink()` on update/delete
