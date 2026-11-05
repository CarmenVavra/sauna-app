@echo off
REM Saunabuch – lokalen Server (Port 8200) beenden
setlocal
rem Vite-Dev-Server (falls "npm run dev" laeuft): fester Port, siehe vite.config.js
set "VITE_PORT=6500"
for /f "tokens=5" %%v in ('netstat -ano ^| findstr /R /C:":%VITE_PORT%  *0\.0\.0\.0:0 " /C:":%VITE_PORT%  *\[::\]:0 "') do taskkill /PID %%v /T /F >nul 2>&1
if exist "%~dp0public\hot" del /q "%~dp0public\hot" >nul 2>&1
set PORT=8200
set FOUND=0
for /f "tokens=5" %%p in ('netstat -ano ^| findstr /R /C:"127\.0\.0\.1:%PORT%  *0\.0\.0\.0:0 "') do (
    taskkill /PID %%p /T /F >nul 2>&1
    set FOUND=1
)
taskkill /FI "WINDOWTITLE eq Saunabuch Server*" /T /F >nul 2>&1
if "%FOUND%"=="1" (
    echo Saunabuch-Server gestoppt.
) else (
    echo Kein laufender Saunabuch-Server auf Port %PORT% gefunden.
)
exit /b 0
