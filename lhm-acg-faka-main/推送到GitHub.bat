@echo off
chcp 65001 >nul
echo ========================================
echo   ACG 发卡 - 推送到 GitHub
echo ========================================
echo.

where git >nul 2>&1
if %errorlevel% neq 0 (
    echo [错误] 未检测到 Git，请先安装：
    echo   下载: https://git-scm.com/download/win
    echo   安装后重新打开此脚本
    pause
    exit /b 1
)

cd /d "%~dp0"

if not exist .git (
    echo 初始化 Git 仓库...
    git init
    echo.
)

echo 添加文件...
git add .

echo.
echo 提交更改...
git commit -m "ACG 发卡系统 - 初始提交"

echo.
echo ========================================
echo 下一步：添加 GitHub 远程仓库
echo ========================================
echo.
echo 1. 打开 https://github.com/new 创建新仓库
echo 2. 仓库名可填: lhm-acg-faka
echo 3. 不要勾选 "Add a README"
echo 4. 创建后，在仓库页面复制 HTTPS 地址
echo.
set /p REPO_URL="请输入你的 GitHub 仓库地址 (如 https://github.com/用户名/lhm-acg-faka.git): "

if "%REPO_URL%"=="" (
    echo 未输入地址，跳过推送。
    echo 手动推送命令: git remote add origin 你的地址
    echo              git branch -M main
    echo              git push -u origin main
    pause
    exit /b 0
)

git remote remove origin 2>nul
git remote add origin %REPO_URL%
git branch -M main

echo.
echo 推送到 GitHub...
git push -u origin main

if %errorlevel% equ 0 (
    echo.
    echo [成功] 已推送到 GitHub！
) else (
    echo.
    echo [提示] 推送失败，可能需要：
    echo   - 检查网络/代理
    echo   - 在 GitHub 登录认证
    echo   - 使用: git push -u origin main 重试
)

echo.
pause
