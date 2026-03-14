#!/bin/bash
# 修复上传目录权限，解决「上传目录不可写」
# 用法：在服务器项目目录执行 chmod +x fix-upload-dirs.sh && ./fix-upload-dirs.sh

set -e
CONTAINER="${1:-acg-faka-web}"

echo "修复上传目录权限 (容器: $CONTAINER)..."
docker exec "$CONTAINER" bash -c 'mkdir -p /var/www/html/assets/cache/general/image /var/www/html/assets/cache/general/video /var/www/html/assets/cache/general/doc /var/www/html/assets/cache/general/other /var/www/html/assets/cache/images && chown -R www-data:www-data /var/www/html/assets/cache'
echo "完成。请重新尝试上传图片。"
