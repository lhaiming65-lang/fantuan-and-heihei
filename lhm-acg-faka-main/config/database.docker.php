<?php
declare (strict_types=1);

/**
 * Docker 环境数据库配置（容器内 MySQL 服务名为 mysql）
 */
return [
    'driver' => 'mysql',
    'host' => 'mysql',
    'database' => 'acg_faka',
    'username' => 'root',
    'password' => 'root',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => 'acg_',
];
