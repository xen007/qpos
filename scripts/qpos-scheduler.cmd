@echo off
cd /d "%~dp0.."
if not exist storage\logs mkdir storage\logs
C:\xampp\php\php.exe artisan schedule:run >> storage\logs\qpos-scheduler.log 2>&1
