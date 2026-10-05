@echo off
REM Saunabuch – lokalen Server (Port 8200) beenden
setlocal
set PORT=8200
set FOUND=0
for /f "tokens=5" %%p in ('netstat -ano ^| findstr /r /c:"127.0.0.1:%PORT% .*LISTENING"') do (
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
