@echo off
chcp 65001 >nul
title Webman Horizon Preview
echo 启动 Webman Horizon 本地预览（无需 webman / Redis）...
start "Horizon Preview Server" /min php -S 127.0.0.1:8899 "%~dp0preview.php"
timeout /t 1 /nobreak >nul
start "" http://127.0.0.1:8899/app/horizon
echo.
echo 预览地址: http://127.0.0.1:8899/app/horizon
echo 关闭名为 "Horizon Preview Server" 的最小化窗口即可停止服务。
timeout /t 5 >nul
