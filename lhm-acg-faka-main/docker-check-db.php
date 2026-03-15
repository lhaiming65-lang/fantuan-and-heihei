<?php
/**
 * 检查数据库是否已初始化（已有业务表）
 * 用于 Railway 等无持久化场景：每次部署容器无 Lock 文件，若直接跑 init 会清空数据。
 * 若表已存在则只创建 Lock，不执行 Install.sql。
 */
$lockFile = __DIR__ . '/kernel/Install/Lock';
if (file_exists($lockFile)) {
    exit(0);
}

$configFile = __DIR__ . '/config/database.php';
if (!file_exists($configFile)) {
    exit(1);
}

$config = (array)require $configFile;
$prefix = $config['prefix'] ?? 'acg_';
$table = $prefix . 'config';

$dsn = sprintf(
    'mysql:dbname=%s;host=%s;port=%s',
    $config['database'] ?? 'acg_faka',
    $config['host'] ?? 'mysql',
    $config['port'] ?? '3306'
);

try {
    $pdo = new PDO($dsn, $config['username'] ?? 'root', $config['password'] ?? 'root');
    $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table));
    if ($stmt && $stmt->rowCount() > 0) {
        file_put_contents($lockFile, '');
        echo "数据库已有数据，已创建 Lock，跳过初始化。\n";
        exit(0);
    }
} catch (Throwable $e) {
    // 连接失败或库不存在，需要执行初始化
}
exit(1);
