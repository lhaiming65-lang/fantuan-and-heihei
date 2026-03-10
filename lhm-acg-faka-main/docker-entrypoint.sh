#!/bin/bash
set -e
# Docker 环境：使用 Docker 数据库配置
if [ -f /var/www/html/config/database.docker.php ]; then
    cp /var/www/html/config/database.docker.php /var/www/html/config/database.php
fi
exec apache2-foreground
