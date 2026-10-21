@echo off
title Stop PIA-AFMS Fleet Management System
echo Stopping PIA-AFMS processes on port 8090...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":8090" ^| findstr "LISTENING"') do (
    echo Terminating PID %%a...
    taskkill /F /PID %%a >nul 2>&1
)
echo PIA-AFMS Server stopped.
timeout /t 2 >nul
