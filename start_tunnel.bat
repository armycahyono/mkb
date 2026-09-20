@echo off
REM ============================================
REM  MKB & SIPS - Cloudflare Tunnel Quick Start
REM  Domain: mkb.sudahonline.my.id & sips.sudahonline.my.id
REM ============================================
title Cloudflare Tunnel - sudahonline.my.id
echo.
echo ==========================================
echo   MKB ^& SIPS Online - sudahonline.my.id
echo ==========================================
echo.

echo [1/2] Mengecek Apache Laragon...
curl.exe -s -o nul http://127.0.0.1:80
if %errorlevel% neq 0 (
    echo [!] Web server Laragon belum aktif.
    echo [!] Pastikan Laragon sudah diklik "Start All".
    pause
    exit /b 1
)
echo       Web server aktif. OK!

echo.
echo [2/2] Memulai Cloudflare Tunnel...
echo ============================================
echo   Aplikasi Online:
echo   - https://mkb.sudahonline.my.id (MKB Keuangan)
echo   - https://sips.sudahonline.my.id (SIPS)
echo.
echo   Tekan Ctrl+C untuk menutup tunnel.
echo ============================================
echo.

"C:\Program Files (x86)\cloudflared\cloudflared.exe" tunnel run sips-tunnel
pause
