@echo off
chcp 65001 >nul
echo ========================================
echo   ACG 发卡系统 - 一键配置并启动
echo ========================================
echo.

cd /d "%~dp0"

:: 0. 若镜像拉取失败，请打开 Docker Desktop -> 设置 -> Docker Engine，删除 "registry-mirrors" 整行后应用
::
:: 1. 检查 Docker
docker info >nul 2>&1
if errorlevel 1 (
    echo [1/4] Docker 未就绪，正在尝试启动 Docker Desktop...
    start "" "C:\Program Files\Docker\Docker\Docker Desktop.exe"
    echo       请等待 60 秒让 Docker 完全启动...
    timeout /t 60 /nobreak >nul
)

:: 2. 再次检查 Docker
docker info >nul 2>&1
if errorlevel 1 (
    echo.
    echo [×] Docker 无法启动。请先完成以下步骤：
    echo.
    echo    1. 以管理员身份打开 PowerShell
    echo    2. 执行: wsl --install
    echo    3. 重启电脑
    echo    4. 再次运行本脚本
    echo.
    echo    若已安装 WSL 仍失败，请手动打开 Docker Desktop 并等待完全启动。
    echo.
    pause
    exit /b 1
)

echo [√] Docker 已就绪
echo.

:: 3. 启动项目
echo [2/4] 正在构建并启动容器...
docker compose up -d --build

if errorlevel 1 (
    echo.
    echo [×] 启动失败，请检查上方错误信息
    pause
    exit /b 1
)

echo.
echo [3/4] 等待 MySQL 就绪...
timeout /t 15 /nobreak >nul

echo.
echo ========================================
echo   [√] 启动成功！
echo ========================================
echo.
echo   访问地址: http://localhost:8080
echo   安装页面: http://localhost:8080/install/step
echo   后台管理: http://localhost:8080/admin
echo.
echo   安装时数据库信息（已自动配置）:
echo   - 数据库: acg_faka
echo   - 用户: root
echo   - 密码: root
echo   - 主机: mysql（若网页安装失败可填 127.0.0.1）
echo.
echo [4/4] 正在打开浏览器...
start http://localhost:8080/install/step
echo.
pause
