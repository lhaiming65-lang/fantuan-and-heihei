FROM php:8.1-apache

# 安装依赖和 PHP 扩展
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libcurl4-openssl-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql zip gd \
    && a2enmod rewrite

# 设置工作目录
WORKDIR /var/www/html

# 复制项目文件
COPY . /var/www/html

# 创建上传与缓存目录并赋权（无服务器/Railway 等无法 SSH 时依赖镜像内权限）
RUN mkdir -p /var/www/html/assets/cache/general/image \
             /var/www/html/assets/cache/general/video \
             /var/www/html/assets/cache/general/doc \
             /var/www/html/assets/cache/general/other \
             /var/www/html/assets/cache/images \
             /var/www/html/runtime/view/cache \
             /var/www/html/runtime/view/compile \
    && chown -R www-data:www-data /var/www/html/assets/cache /var/www/html/runtime /var/www/html/config

# 设置 Apache 文档根目录
ENV APACHE_DOCUMENT_ROOT /var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 复制入口脚本并设置执行权限
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
