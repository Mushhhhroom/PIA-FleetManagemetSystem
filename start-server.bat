@echo off
title PIA-AFMS Fleet Management System (Port 8090)
cd /d "%~dp0"
echo ============================================================
echo   PIA-AFMS - Philippine Information Agency
echo   Automated Fleet Management Information System
echo   Framework: CodeIgniter 4 (PHP 8.5.12)
echo   Database: MySQL (fleet_db)
echo ============================================================
echo.
echo Starting application server on http://localhost:8090 ...
start "" "http://localhost:8090/login"
php -S 0.0.0.0:8090 -t public vendor\codeigniter4\framework\system\rewrite.php
pause
