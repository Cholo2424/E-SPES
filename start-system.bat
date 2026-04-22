@echo off
setlocal

set "ROOT=%~dp0"

if not exist "%ROOT%plain-php-backend\router.php" (
  echo [ERROR] Cannot find plain-php-backend\router.php
  echo Run this file from the eSPES project root folder.
  pause
  exit /b 1
)

where php >nul 2>&1
if errorlevel 1 (
  echo [ERROR] PHP is not installed or not in PATH.
  echo Install PHP and make sure "php" works in terminal.
  pause
  exit /b 1
)

echo Starting eSPES backend on http://127.0.0.1:8001 ...
start "eSPES Backend :8001" cmd /k "cd /d ""%ROOT%plain-php-backend"" && php -S 127.0.0.1:8001 router.php"

echo Starting eSPES frontend on http://127.0.0.1:5500 ...
start "eSPES Frontend :5500" cmd /k "cd /d ""%ROOT%"" && php -S 127.0.0.1:5500 -t ."

timeout /t 2 >nul
start "" "http://127.0.0.1:5500/landingPage.html"

echo.
echo eSPES started. Keep both server windows open while using the system.
echo.
exit /b 0
