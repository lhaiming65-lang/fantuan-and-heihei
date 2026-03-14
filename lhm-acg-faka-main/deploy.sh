#!/bin/bash
# 一键部署脚本 - 在 VPS 上执行
# 用法: chmod +x deploy.sh && ./deploy.sh

set -e
echo "=== ACG 发卡系统 - 一键部署 ==="

# 检查 Docker
if ! command -v docker &> /dev/null; then
    echo "正在安装 Docker..."
    curl -fsSL https://get.docker.com | sh
    systemctl enable docker
    systemctl start docker
fi

# 使用生产配置启动
echo "启动服务..."
docker-compose -f docker-compose.prod.yml up -d --build

# 等待 MySQL 就绪
echo "等待数据库就绪..."
sleep 15

# 确保上传目录存在且可写（解决「上传目录不可写」）
echo "设置上传目录权限..."
docker exec acg-faka-web bash -c 'mkdir -p /var/www/html/assets/cache/general/image /var/www/html/assets/cache/general/video /var/www/html/assets/cache/general/doc /var/www/html/assets/cache/general/other /var/www/html/assets/cache/images && chown -R www-data:www-data /var/www/html/assets/cache' 2>/dev/null || true

# 初始化数据库（若未安装）
if [ ! -f kernel/Install/Lock ]; then
    echo "初始化数据库..."
    docker exec acg-faka-web php /var/www/html/docker-init-db.php
    echo "默认管理员: admin@admin.com / admin123"
    echo "请立即修改密码: docker exec acg-faka-web php /var/www/html/docker-change-admin.php 你的邮箱 新密码"
else
    echo "数据库已初始化，跳过"
fi

echo ""
echo "=== 部署完成 ==="
echo "前台: http://$(curl -s ifconfig.me 2>/dev/null || echo '你的服务器IP')"
echo "后台: http://$(curl -s ifconfig.me 2>/dev/null || echo '你的服务器IP')/admin"
echo ""
echo "安全建议:"
echo "1. 修改管理员密码"
echo "2. 在 Cloudflare 配置域名和 HTTPS"
echo "3. 修改 MySQL 密码（编辑 docker-compose.prod.yml 设置 MYSQL_ROOT_PASSWORD）"
