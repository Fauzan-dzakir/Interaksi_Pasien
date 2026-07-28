@echo off
REM Menyalakan lingkungan pengembangan SIM Alat CSSD di Windows.
REM PHP 8.4 dipasang terpisah di C:\php84 karena PHP bawaan XAMPP (8.0) terlalu lama untuk Laravel 13.

set PATH=C:\php84;%PATH%

echo [1/2] Memastikan MariaDB (XAMPP) berjalan...
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I "mysqld.exe" >NUL
if errorlevel 1 (
    echo      MariaDB belum jalan, menyalakan...
    start "" /B "C:\xampp\mysql\bin\mysqld.exe" --defaults-file=C:\xampp\mysql\bin\my.ini --standalone
    timeout /T 5 /NOBREAK >NUL
) else (
    echo      MariaDB sudah jalan.
)

echo [2/2] Menjalankan server Laravel di http://127.0.0.1:8000
echo.
php artisan serve --host=127.0.0.1 --port=8000
