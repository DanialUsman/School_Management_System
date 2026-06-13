# School Management System (SMS Pro)

A modern, premium School Management System built with PHP, Oracle Database, and Vanilla CSS/JS.

## Features

* **Modern UI:** Light theme with glassmorphism effects and Inter typography.
* **Role-Based Access:** Specialized dashboards and permissions for Admins, Teachers, and Students.
* **Oracle Integration:** Fully connected to your `schema.sql`.
* **Responsive Design:** Works across different screen sizes.

## Setup Instructions

### Database

* Ensure your Oracle Database is running.
* Run the provided `schema.sql` (`@C:\path\to\schema.sql`) to create the tables.
* Update `config/db.php` with your connection details (user, password, host).

### PHP Extension

* Ensure `extension=oci8` is enabled in your `php.ini`.

### Web Server

* Host the files on a server such as Apache or Nginx.
* Access the application via:

```text
http://localhost/SMS
```

### Default Login

The default admin user is:

```sql
INSERT INTO USERS (USER_ID, USERNAME, PASSWORD, ROLE)
VALUES (1, 'admin', 'admin123', 'admin');

COMMIT;
```

**Username:** `admin`
**Password:** `admin123`

> Note: The login script also supports hashed passwords via `password_verify` for higher security.

## Directory Structure

```text
/admin      - Administrator specific pages
/teacher    - Teacher specific pages
/student    - Student specific pages
/auth       - Login/Logout logic
/config     - Database connection
/assets     - CSS and JavaScript files
/includes   - Shared UI components (header/footer)
```
