Local verification steps for SmartRT02

Prerequisites
- XAMPP installed (Apache + MySQL)
- Place this project at: `C:\xampp\htdocs\SmartRT02`
- Start Apache and MySQL via XAMPP Control Panel

One-time DB seed password update
1. Open in browser: http://localhost/SmartRT02/update_seed.php
2. You should see confirmation that passwords for `admin` and `peserta` changed to `123`.

Quick manual checks (browser)
- Open: http://localhost/SmartRT02/
- Login as `admin` / `123` and `peserta` / `123` to check role-specific views.
- Go to Manajemen Iuran: click Periode (calendar) and pick month — table should update.
- As warga account, pay and click "Kwitansi PDF" — browser should download the PDF.

API curl examples (PowerShell) — run from the project root or any folder

# Login (PowerShell)
$body = '{"action":"login","payload":{"username":"admin","password":"123"}}'
Invoke-RestMethod -Uri "http://localhost/SmartRT02/api.php" -Method POST -Body $body -ContentType "application/json"

# Get Iuran
$body = '{"action":"getIuran","payload":{}}'
Invoke-RestMethod -Uri "http://localhost/SmartRT02/api.php" -Method POST -Body $body -ContentType "application/json"

# Add Iuran (example)
$body = '{"action":"addIuran","payload":{"user":"peserta","nama":"Peserta","bulan":"05-2026","nominal":76000,"status":"Belum Lunas"}}'
Invoke-RestMethod -Uri "http://localhost/SmartRT02/api.php" -Method POST -Body $body -ContentType "application/json"

# Mark iuran Lunas (updateIuran)
$body = '{"action":"updateIuran","payload":{"id":123}}'
Invoke-RestMethod -Uri "http://localhost/SmartRT02/api.php" -Method POST -Body $body -ContentType "application/json"

Troubleshooting
- If responses show HTML, open `api.php` in browser to see fatal errors or misconfig. Enable display_errors temporarily in `api.php` (set `ini_set('display_errors', 1)`) to debug then revert.
- Ensure `GAS_URL` in `index.html` points to `./api.php` (default).

If you want, I can run additional code fixes or integrate a custom-styled Flatpickr theme. If you run the steps above and share any failing response or screenshot, I will fix them promptly.