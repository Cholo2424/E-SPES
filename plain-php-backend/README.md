# Plain PHP Backend (No Laravel, MySQL)

This backend keeps the same API URLs used by the existing frontend pages:

- `POST /api/login`
- `POST /api/forgot-password/send-code`
- `POST /api/forgot-password/verify-code`
- `POST /api/forgot-password/reset`
- `POST /api/add-applicant`
- `GET /api/spes-management/applicants`

## Run

1. Copy `.env.example` to `.env` and set MySQL + MAIL credentials.
2. Ensure MySQL server is running.
3. Install PHPMailer dependencies:

```powershell
Set-Location "c:\Users\micro_17\Downloads\eSPES-main\eSPES-main\plain-php-backend"
composer install
```

4. Start the API server:

From project root:

```powershell
Set-Location "c:\Users\micro_17\Downloads\eSPES-main\eSPES-main"
php -S 127.0.0.1:8000 plain-php-backend/router.php
```

Do not run `php artisan serve` while this plain PHP backend is running on port 8000.

## Storage

- Uses MySQL only (no SQLite fallback).
- Database and tables are auto-created on first request when credentials have permission.
- Uploaded PDFs are saved to `plain-php-backend/uploads/spes-documents/`.
- Forgot password verification emails are sent using PHPMailer (SMTP).

## MySQL Config

Set these values in `plain-php-backend/.env`:

- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `DB_CHARSET`

## Mail Config (PHPMailer)

Set these values in `plain-php-backend/.env`:

- `MAIL_HOST`
- `MAIL_PORT`
- `MAIL_USERNAME`
- `MAIL_PASSWORD`
- `MAIL_ENCRYPTION`
- `MAIL_FROM_ADDRESS`
- `MAIL_FROM_NAME`

## Default Credentials

- Admin: `admin@ccc.edu.ph` / `Admin@12345`
- Employee: `employee@ccc.edu.ph` / `Employee@12345`
- Student: `student@ccc.edu.ph` / `Student@12345`

## Remove Laravel Folder (Optional)

If you want to fully remove Laravel from the project after confirming plain PHP works, delete the `backend` folder.
