#!/bin/bash
set -e

# 修复 Apache 多 MPM 冲突（Railway 环境常见问题）
a2dismod mpm_event mpm_worker 2>/dev/null || true
rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* 2>/dev/null || true
a2enmod mpm_prefork 2>/dev/null || true

# Railway 环境：从环境变量生成数据库配置
if [ -n "$MYSQLHOST" ] || [ -n "$MYSQL_URL" ]; then
    if [ -n "$MYSQLHOST" ]; then
        DB_HOST="$MYSQLHOST"
        DB_PORT="${MYSQLPORT:-3306}"
        DB_NAME="${MYSQLDATABASE:-acg_faka}"
        DB_USER="${MYSQLUSER:-root}"
        DB_PASS="${MYSQLPASSWORD:-root}"
    else
        # 解析 MYSQL_URL (简单解析，密码勿含特殊字符)
        DB_HOST=$(echo "$MYSQL_URL" | sed -n 's|.*@\([^:/]*\).*|\1|p')
        DB_PORT=$(echo "$MYSQL_URL" | sed -n 's|.*:\([0-9]*\)/.*|\1|p')
        DB_PORT="${DB_PORT:-3306}"
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
    'port' => '${DB_PORT}',
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

# 确保 favicon.ico 存在且可写（LOGO 上传会覆盖此文件）
touch /var/www/html/favicon.ico
chown www-data:www-data /var/www/html/favicon.ico

# 确保 Smarty 模板引擎所需的 runtime 目录存在且可写
mkdir -p /var/www/html/runtime/view/cache /var/www/html/runtime/view/compile
chown -R www-data:www-data /var/www/html/runtime

# 确保上传缓存目录存在且可写（后台/用户图片等）
mkdir -p /var/www/html/assets/cache/general/image \
         /var/www/html/assets/cache/general/video \
         /var/www/html/assets/cache/general/doc \
         /var/www/html/assets/cache/general/other \
         /var/www/html/assets/cache/images
chown -R www-data:www-data /var/www/html/assets/cache

# 确保插件目录可写（应用商店安装的支付插件 → app/Pay，通用插件 → app/Plugin，否则安装后不显示）
mkdir -p /var/www/html/app/Pay /var/www/html/app/Plugin
chown -R www-data:www-data /var/www/html/app/Pay /var/www/html/app/Plugin

# 应用商店下载的安装包临时目录
mkdir -p /var/www/html/kernel/Install/OS
chown -R www-data:www-data /var/www/html/kernel/Install

# 插件缓存目录（store.cache 等，缺失会导致插件列表异常）
mkdir -p /var/www/html/runtime/plugin
chown -R www-data:www-data /var/www/html/runtime

# 反向代理环境：使用 X-Forwarded-For 作为客户端 IP，避免“登录会话过期”
# 0=REMOTE_ADDR 1=X-Real-IP 2=X-Forwarded-For（Railway/Cloudflare 用此传真实 IP）
echo "2" > /var/www/html/runtime/mode
chown www-data:www-data /var/www/html/runtime/mode

# 若 config/store.php 不存在则创建默认配置（Kernel.php 启动时必需，否则 PHP 报错被静默）
if [ ! -f /var/www/html/config/store.php ]; then
    cat > /var/www/html/config/store.php << 'STOREEOF'
<?php
declare (strict_types=1);
return [
    'server' => 0,
    'app_id' => '',
    'app_key' => 'acg_faka_default_k',
];
STOREEOF
fi

# 确保 config 目录可写，否则应用商店/支付插件注册与登录无法写入 store.php（会报「没有文件写入插件权限」）
chown -R www-data:www-data /var/www/html/config

# Railway/无持久化：无 Lock 时先检查数据库是否已有表，避免每次部署都执行 init 清空数据
if [ ! -f /var/www/html/kernel/Install/Lock ] && { [ -n "$MYSQLHOST" ] || [ -n "$MYSQL_URL" ]; }; then
    if php /var/www/html/docker-check-db.php 2>/dev/null; then
        echo "Railway: 数据库已存在，已恢复 Lock，未执行初始化。"
    else
        echo "Railway: 首次部署或数据库为空，执行初始化..."
        php /var/www/html/docker-init-db.php || true
    fi
fi

# Railway：监听 PORT 环境变量（默认 80）
if [ -n "$PORT" ]; then
    sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf
    sed -i "s/:80/:$PORT/g" /etc/apache2/sites-available/*.conf
fi

exec apache2-foreground
