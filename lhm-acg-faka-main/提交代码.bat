@echo off
chcp 65001 >nul
cd /d "%~dp0"

where git >nul 2>&1
if %errorlevel% neq 0 (
    echo [错误] 未检测到 Git
    echo 请先安装: https://git-scm.com/download/win
    echo 或使用 Cursor 左侧「源代码管理」面板提交
    pause
    exit /b 1
)

if not exist .git (
    echo 初始化 Git 仓库...
    git init
)

echo 添加所有文件...
git add .

echo 提交...
git commit -m "ACG 发卡系统 - 更新"

echo.
echo [完成] 代码已提交到本地
echo 推送到 GitHub: 运行 推送到GitHub.bat 或执行 git push
pause
