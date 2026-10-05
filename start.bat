@echo off
REM Saunabuch – lokalen Server starten (http://127.0.0.1:8200)
cd /d "%~dp0"
setlocal
set PORT=8200

if not exist vendor (
    echo Installiere PHP-Abhaengigkeiten ...
    call composer install --no-interaction || goto :error
)
if not exist node_modules (
    echo Installiere Node-Abhaengigkeiten ...
    call npm install || goto :error
)
if not exist .env (
    copy .env.example .env >nul
    call php artisan key:generate --no-interaction || goto :error
)
if not exist database\database.sqlite (
    type nul > database\database.sqlite
    echo Lege Datenbank mit Demo-Daten an ...
    call php artisan migrate --seed --force || goto :error
) else (
    call php artisan migrate --force || goto :error
)
if not exist public\build\manifest.json (
    echo Baue Frontend ...
    call npm run build || goto :error
)

netstat -ano | findstr /r /c:"127.0.0.1:%PORT% .*LISTENING" >nul
if not errorlevel 1 (
    echo Port %PORT% ist bereits belegt - laeuft Saunabuch schon? Sonst zuerst stop.bat ausfuehren.
    start "" http://127.0.0.1:%PORT%
    exit /b 0
)

echo Starte Saunabuch auf http://127.0.0.1:%PORT% ...
start "Saunabuch Server" /min php artisan serve --host=127.0.0.1 --port=%PORT%
timeout /t 2 /nobreak >nul
start "" http://127.0.0.1:%PORT%
echo Server laeuft. Zum Beenden stop.bat ausfuehren.
exit /b 0

:error
echo Start fehlgeschlagen.
exit /b 1
