# ACG 发卡系统 - 本地环境一键配置脚本
# 用法: 以管理员身份运行 PowerShell，执行 .\setup.ps1

$ErrorActionPreference = "Stop"
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  ACG 发卡系统 - 环境配置" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# 1. 检查 PHP
$phpPath = $null
if (Get-Command php -ErrorAction SilentlyContinue) {
    $phpPath = (Get-Command php).Source
    Write-Host "[√] 已找到 PHP: $phpPath" -ForegroundColor Green
} else {
    Write-Host "[!] 未找到 PHP，尝试通过 winget 安装..." -ForegroundColor Yellow
    try {
        winget install PHP.PHP.8.2 --source winget --accept-package-agreements --accept-source-agreements
        Write-Host "[√] PHP 安装完成，请关闭并重新打开终端后运行 run.bat" -ForegroundColor Green
    } catch {
        Write-Host "[×] 安装失败。请手动安装：" -ForegroundColor Red
        Write-Host "    1. 下载小皮面板 https://www.xp.cn/" -ForegroundColor White
        Write-Host "    2. 或 XAMPP https://www.apachefriends.org/" -ForegroundColor White
    }
    exit 1
}

# 2. 检查 MySQL
$mysqlOk = $false
if (Get-Command mysql -ErrorAction SilentlyContinue) {
    $mysqlOk = $true
    Write-Host "[√] 已找到 MySQL" -ForegroundColor Green
}
# 检查常见路径
$mysqlPaths = @(
    "C:\phpstudy_pro\Extensions\MySQL*\bin\mysql.exe",
    "C:\xampp\mysql\bin\mysql.exe"
)
foreach ($p in $mysqlPaths) {
    if (Get-Item $p -ErrorAction SilentlyContinue) {
        $mysqlOk = $true
        Write-Host "[√] 已找到 MySQL" -ForegroundColor Green
        break
    }
}
if (-not $mysqlOk) {
    Write-Host "[!] 未找到 MySQL，请确保已安装并启动 MySQL 服务" -ForegroundColor Yellow
}

# 3. 创建数据库
Write-Host ""
Write-Host "创建数据库 acg_faka..." -ForegroundColor Cyan
try {
    mysql -u root -e "CREATE DATABASE IF NOT EXISTS acg_faka CHARACTER SET utf8mb4;" 2>$null
    Write-Host "[√] 数据库已就绪" -ForegroundColor Green
} catch {
    Write-Host "[!] 无法自动创建数据库。请手动执行：" -ForegroundColor Yellow
    Write-Host "    CREATE DATABASE acg_faka CHARACTER SET utf8mb4;" -ForegroundColor White
}

# 4. 运行 Composer
if (Get-Command composer -ErrorAction SilentlyContinue) {
    Write-Host ""
    Write-Host "安装 Composer 依赖..." -ForegroundColor Cyan
    composer install --no-interaction
    Write-Host "[√] 依赖安装完成" -ForegroundColor Green
} else {
    Write-Host "[!] 未找到 Composer，如 vendor 目录已存在可忽略" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "  配置完成！请运行 run.bat 启动服务" -ForegroundColor Green
Write-Host "  访问 http://localhost:8080/install/step 完成安装" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
