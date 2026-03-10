@echo off
chcp 65001 >nul
echo ========================================
echo   ACG 发卡系统 - Docker 一键运行
echo ========================================
echo.

docker --version >nul 2>&1
if errorlevel 1 (
    echo [错误] 未检测到 Docker，请先安装 Docker Desktop
    echo 下载地址: https://www.docker.com/products/docker-desktop/
    pause
    exit /b 1
)

cd /d "%~dp0"

echo 正在启动容器...
docker compose up -d --build

if errorlevel 1 (
    echo.
    echo [错误] 启动失败，请检查 Docker 是否正常运行
    pause
    exit /b 1
)

echo.
echo ========================================
echo   启动成功！
echo ========================================
echo   访问地址: http://localhost:8080
echo   安装页面: http://localhost:8080/install/step
echo   后台管理: http://localhost:8080/admin
echo ========================================
echo.
echo 数据库信息（安装时使用）:
echo   主机: mysql  （网页安装时填 127.0.0.1 或 mysql）
echo   数据库: acg_faka
echo   用户: root
echo   密码: root
echo.
pause
