<?php
/**
 * PHP 内置服务器路由文件
 * 用于 php -S localhost:8080 时模拟 .htaccess 伪静态
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if ($uri !== '/' && $uri !== '' && file_exists(__DIR__ . $uri)) {
    return false; // 静态文件直接返回
}
$_GET['s'] = $uri && $uri !== '/' ? $uri : '/user/index/index';
require __DIR__ . '/index.php';
