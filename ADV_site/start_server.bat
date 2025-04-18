@echo off
echo Starting local web server...
echo.

REM Проверяем наличие Python
python --version >nul 2>&1
if %errorlevel% equ 0 (
    echo Using Python HTTP server...
    python -m http.server 8000
) else (
    echo Python not found. Checking for Node.js...
    
    REM Проверяем наличие Node.js
    node --version >nul 2>&1
    if %errorlevel% equ 0 (
        echo Using Node.js http-server...
        npx http-server -p 8000
    ) else (
        echo Neither Python nor Node.js found.
        echo Please install one of the following:
        echo 1. Python from https://www.python.org/downloads/
        echo 2. Node.js from https://nodejs.org/
        echo.
        echo After installation, run this batch file again.
        pause
        exit
    )
) 