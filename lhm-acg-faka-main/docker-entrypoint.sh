#!/bin/bash
set -e

# Railway 环境：从环境变量生成数据库配置
if [ -n "$MYSQLHOST" ] || [ -n "$MYSQL_URL" ]; then
    if [ -n "$MYSQLHOST" ]; then
        DB_HOST="$MYSQLHOST"
        DB_NAME="${MYSQLDATABASE:-acg_faka}"
        DB_USER="${MYSQLUSER:-root}"
        DB_PASS="${MYSQLPASSWORD:-root}"
    else
        # 解析 MYSQL_URL (简单解析，密码勿含特殊字符)
        DB_HOST=$(echo "$MYSQL_URL" | sed -n 's|.*@\([^:/]*\).*|\1|p')
        DB_NAME=$(echo "$MYSQL_URL" | sed -n 's|.*/\([^?]*\).*|\1|p')
        DB_USER=$(echo "$MYSQL_URL" | sed -n 's|mysql://\([^:]*\):.*|\1|p')
        DB_PASS=$(echo "$MYSQL_URL" | sed -n 's|mysql://[^:]*:\([^@]*\)@.*|\1|p')
    fi
    cat > /var/www/html/config/database.php << EOF
<?php
declare (strict_types=1);
return [
    'driver' => 'mysql',
    'host' => '${DB_HOST}',
    'database' => '${DB_NAME}',
    'username' => '${DB_USER}',
    'password' => '${DB_PASS}',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => 'acg_',
];
EOF
# Docker Compose 环境
elif [ -f /var/www/html/config/database.docker.php ]; then
    cp /var/www/html/config/database.docker.php /var/www/html/config/database.php
fi

# Railway：首次部署时自动初始化数据库
if [ ! -f /var/www/html/kernel/Install/Lock ] && [ -n "$MYSQLHOST" ]; then
    echo "Railway: 首次部署，初始化数据库..."
    php /var/www/html/docker-init-db.php || true
fi

exec apache2-foreground
