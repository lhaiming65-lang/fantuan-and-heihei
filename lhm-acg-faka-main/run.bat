@echo off
chcp 65001 >nul
echo ========================================
echo   ACG 发卡系统 - 本地运行
echo ========================================
echo.

:: 检查 PHP 是否可用
php -v >nul 2>&1
if errorlevel 1 (
    echo [错误] 未检测到 PHP，请先安装 PHP 8.0+ 并加入系统 PATH
    echo 推荐使用：小皮面板(phpStudy) 或 XAMPP
    pause
    exit /b 1
)

:: 切换到脚本所在目录
cd /d "%~dp0"

echo 启动 PHP 内置服务器...
echo 访问地址: http://localhost:8080
echo 安装页面: http://localhost:8080/install/step
echo 按 Ctrl+C 停止服务
echo.

php -S localhost:8080 router.php

pause
