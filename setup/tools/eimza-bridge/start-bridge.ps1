$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot
$php = "c:\xampp\php\php.exe"
$router = Join-Path $PSScriptRoot "bridge.php"

if (!(Test-Path $php)) {
    Write-Host "PHP bulunamadi: $php" -ForegroundColor Red
    exit 1
}
if (!(Test-Path $router)) {
    Write-Host "Router bulunamadi: $router" -ForegroundColor Red
    exit 1
}

Write-Host "E-imza bridge baslatiliyor: http://127.0.0.1:15873" -ForegroundColor Green
& $php -S 127.0.0.1:15873 $router
