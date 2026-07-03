@echo off
setlocal
title ESH esh_app tam duzeltme

net session >nul 2>&1
if %errorlevel% neq 0 (
    echo Yonetici hakki gerekli. UAC penceresi aciliyor...
    powershell -NoProfile -Command "Start-Process -FilePath '%~f0' -Verb RunAs"
    exit /b
)

echo.
echo [1/4] Karma mod + esh_app login (PHP)...
"C:\xampp\php\php.exe" "C:\xampp\htdocs\tools\enable_mssql_mixed_mode.php"
if %errorlevel% neq 0 (
    echo HATA: enable_mssql_mixed_mode basarisiz.
    pause
    exit /b 1
)

echo [2/4] SQL Server (SQLEXPRESS) yeniden baslatiliyor...
net stop "MSSQL$SQLEXPRESS" /y
net start "MSSQL$SQLEXPRESS"
timeout /t 4 /nobreak >nul

echo [3/4] Kimlik dogrulama modu kontrolu...
"C:\xampp\php\php.exe" "C:\xampp\htdocs\tools\check_mssql_security_mode.php"

echo [4/4] esh_app baglanti testi...
"C:\xampp\php\php.exe" "C:\xampp\htdocs\tools\test_mssql_auth.php"
echo.
echo IsIntegratedSecurityOnly = 0 ve esh_app OK olmali.
pause
