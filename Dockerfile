# Railway 部署 - 项目在 lhm-acg-faka-main 子目录
FROM php:8.1-apache

RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libcurl4-openssl-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql zip gd \
    && a2dismod mpm_event mpm_worker 2>/dev/null || true \
    && a2enmod rewrite

# 安装 Composer（用于构建时安装依赖）
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY lhm-acg-faka-main/ /var/www/html/

# 构建时安装 PHP 依赖（vendor 被 .gitignore 排除，需在镜像中生成）
RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && test -f vendor/autoload.php || (echo "ERROR: composer install failed - vendor/autoload.php not found" && exit 1)

# 创建 Smarty 模板引擎所需的 runtime 目录（.gitignore 排除了 runtime/）
RUN mkdir -p /var/www/html/runtime/view/cache \
             /var/www/html/runtime/view/compile \
    && chown -R www-data:www-data /var/www/html/runtime

# Apache：启用 AllowOverride 以支持 .htaccess 重写规则
RUN sed -ri -e 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# 确保 www-data 有写入权限（config, Install, 以及上传缓存目录）
RUN mkdir -p /var/www/html/assets/cache/general/image \
             /var/www/html/assets/cache/general/video \
             /var/www/html/assets/cache/general/doc \
             /var/www/html/assets/cache/general/other \
             /var/www/html/assets/cache/images \
    && chown -R www-data:www-data /var/www/html/config /var/www/html/kernel/Install /var/www/html/assets/cache

COPY lhm-acg-faka-main/docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
