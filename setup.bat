@echo off
REM One-time setup for Green Marketplace. Run this FIRST, once.
REM After it finishes, use run.bat every time you want to start the app.

cd /d "%~dp0"

echo ============================================
echo  Green Marketplace - first-time setup
echo ============================================
echo.

where php >nul 2>nul
if errorlevel 1 (
    echo ERROR: "php" was not found on your PATH.
    echo Install PHP from https://windows.php.net/download and try again.
    pause
    exit /b 1
)

where composer >nul 2>nul
if errorlevel 1 (
    echo ERROR: "composer" was not found on your PATH.
    echo Install it from https://getcomposer.org/download and try again.
    pause
    exit /b 1
)

where npm >nul 2>nul
if errorlevel 1 (
    echo ERROR: "npm" was not found on your PATH.
    echo Install Node.js from https://nodejs.org and try again.
    pause
    exit /b 1
)

echo [1/5] Installing PHP dependencies (this can take a few minutes)...
call composer install --no-interaction
if errorlevel 1 goto :fail

echo.
echo [2/6] Installing JS dependencies...
call npm install
if errorlevel 1 goto :fail

echo.
echo [3/6] Building front-end assets...
call npm run build
if errorlevel 1 goto :fail

echo.
echo [4/6] Generating application key...
call php artisan key:generate --ansi

echo.
echo [5/6] Creating database and running migrations + demo data...
if not exist database\database.sqlite type nul > database\database.sqlite
call php artisan migrate --seed --force
if errorlevel 1 goto :fail

echo.
echo [6/6] Linking storage...
call php artisan storage:link

echo.
echo ============================================
echo  Setup complete! Now double-click run.bat
echo ============================================
pause
exit /b 0

:fail
echo.
echo Something failed above - scroll up to see the error message.
pause
exit /b 1
