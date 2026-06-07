@echo off
title Dental ERP - Dev Server
cd /d "%~dp0"

echo Starting Dental ERP Development Server...
echo.

:: Start Laravel backend in a new window
start "Laravel Server" cmd /k "php artisan serve --host=0.0.0.0 --port=8000"

:: Wait a moment then start Vite
timeout /t 2 /nobreak >nul

:: Start Vite frontend in a new window
start "Vite Dev" cmd /k "npm run dev"

echo.
echo [OK] Servers starting...
echo   Laravel: http://localhost:8000
echo   Vite:    http://localhost:5173
echo.
echo Press any key to close this window.
pause >nul
