@echo off
chcp 65001 >nul
cd /d "%~dp0"

echo ========================================
echo   创建新仓库并推送到 GitHub
echo ========================================
echo.
echo 第一步：在浏览器创建新仓库
echo ----------------------------------------
echo 1. 打开 https://github.com/new
echo 2. 仓库名填写: lhm-acg-faka （或你喜欢的名字）
echo 3. 选择 Public
echo 4. 不要勾选 "Add a README"、"Add .gitignore"
echo 5. 点击 Create repository
echo.
echo 第二步：复制创建后的仓库地址
echo ----------------------------------------
echo 创建成功后会显示仓库地址，类似：
echo   https://github.com/你的用户名/lhm-acg-faka.git
echo.
pause

set /p REPO_URL="请粘贴你的新仓库地址: "

if "%REPO_URL%"=="" (
    echo 未输入地址
    pause
    exit /b 1
)

echo.
echo 配置远程仓库并推送...
git remote remove origin 2>nul
git remote add origin %REPO_URL%
git branch -M main
git push -u origin main

if %errorlevel% equ 0 (
    echo.
    echo [成功] 已推送到新仓库！
    echo 地址: %REPO_URL%
) else (
    echo.
    echo [提示] 推送失败，请检查：
    echo   - 网络连接
    echo   - GitHub 登录（可能需要输入用户名和 Token）
    echo   - 地址是否正确
)

echo.
pause
