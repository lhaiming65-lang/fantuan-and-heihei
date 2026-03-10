<?php
/**
 * Docker 环境数据库初始化脚本
 * 用法: docker exec acg-faka-web php /var/www/html/docker-init-db.php
 */
$sqlFile = __DIR__ . '/kernel/Install/Install.sql';
$salt = 'a1b2c3d4e5f6g7h8'; // 16 chars
$pw = sha1(md5(md5('admin123') . md5($salt)));

$sql = file_get_contents($sqlFile);
$sql = str_replace('__PREFIX__', 'acg_', $sql);
$sql = str_replace('__MANAGE_EMAIL__', 'admin@admin.com', $sql);
$sql = str_replace('__MANAGE_PASSWORD__', $pw, $sql);
$sql = str_replace('__MANAGE_NICKNAME__', '管理员', $sql);
$sql = str_replace('__MANAGE_SALT__', $salt, $sql);

$tmpFile = __DIR__ . '/runtime/install_tmp.sql';
if (!is_dir(__DIR__ . '/runtime')) mkdir(__DIR__ . '/runtime', 0777, true);
file_put_contents($tmpFile, $sql);

// 优先使用 config/database.php（Railway 等环境由 entrypoint 生成）
if (file_exists(__DIR__ . '/config/database.php')) {
    $loaded = (array)require __DIR__ . '/config/database.php';
    $config = [
        'host' => $loaded['host'] ?? 'mysql',
        'database' => $loaded['database'] ?? 'acg_faka',
        'username' => $loaded['username'] ?? 'root',
        'password' => $loaded['password'] ?? 'root',
    ];
} else {
    $config = [
        'host' => 'mysql',
        'database' => 'acg_faka',
        'username' => 'root',
        'password' => 'root',
    ];
}

$dsn = "mysql:dbname={$config['database']};host={$config['host']}";
try {
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->exec($sql);
    echo "数据库初始化成功！\n";
    echo "管理员: admin@admin.com / admin123\n";
    file_put_contents(__DIR__ . '/kernel/Install/Lock', '');
    // 保持现有 config，不覆盖（Railway 已由 entrypoint 生成）
    if (!file_exists(__DIR__ . '/config/database.php')) {
        $dbConfig = '<?php
declare(strict_types=1);
return [
    "driver" => "mysql",
    "host" => "' . $config['host'] . '",
    "database" => "' . $config['database'] . '",
    "username" => "' . $config['username'] . '",
    "password" => "' . addslashes($config['password']) . '",
    "charset" => "utf8mb4",
    "collation" => "utf8mb4_unicode_ci",
    "prefix" => "acg_",
];';
        file_put_contents(__DIR__ . '/config/database.php', $dbConfig);
    }
    unlink($tmpFile);
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    exit(1);
}
