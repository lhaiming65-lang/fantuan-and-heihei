@echo off
chcp 65001 >nul
echo ========================================
echo   安装 WSL（Docker Desktop 依赖）
echo ========================================
echo.
echo 本脚本需以【管理员身份】运行！
echo 右键本文件 -> 以管理员身份运行
echo.
pause

echo 正在安装 WSL...
wsl --install --no-distribution

echo.
echo ========================================
echo   安装完成！
echo ========================================
echo.
echo 请【重启电脑】后：
echo 1. 打开 Docker Desktop
echo 2. 等待完全启动
echo 3. 双击运行「一键配置并启动.bat」
echo.
pause
