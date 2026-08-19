@echo off
REM Starts the Green Marketplace locally.
REM Opens Vite in its own window, then serves the app on port 9090.

cd /d "%~dp0"

echo Starting Vite (asset builder) in a separate window...
start "vite" cmd /k npm run dev

echo.
echo Green Marketplace  ->  http://localhost:9090
echo Admin panel        ->  http://localhost:9090/admin
echo                        admin@greenmarketplace.tn / password
echo.
echo Press Ctrl+C to stop the server.
echo.

php -S 127.0.0.1:9090 -t public server.php
