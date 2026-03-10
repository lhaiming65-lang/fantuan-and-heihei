<?php
/**
 * 修改后台管理员账号和密码
 * 用法: docker exec acg-faka-web php /var/www/html/docker-change-admin.php 新邮箱 新密码
 * 示例: docker exec acg-faka-web php /var/www/html/docker-change-admin.php admin@my.com mypassword123
 */
if ($argc < 3) {
    echo "用法: php docker-change-admin.php <新邮箱> <新密码>\n";
    echo "示例: php docker-change-admin.php admin@my.com mypassword123\n";
    exit(1);
}

$newEmail = trim($argv[1]);
$newPassword = trim($argv[2]);

if (strlen($newPassword) < 6) {
    echo "错误: 密码至少6位\n";
    exit(1);
}

if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
    echo "错误: 邮箱格式不正确\n";
    exit(1);
}

$salt = bin2hex(random_bytes(16)); // 32 chars
$pw = sha1(md5(md5($newPassword) . md5($salt)));

$config = require __DIR__ . '/config/database.php';
$dsn = "mysql:dbname={$config['database']};host={$config['host']}";

try {
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $prefix = $config['prefix'] ?? 'acg_';
    $stmt = $pdo->prepare("UPDATE {$prefix}manage SET email = ?, password = ?, salt = ? WHERE id = 1");
    $stmt->execute([$newEmail, $pw, $salt]);
    if ($stmt->rowCount() > 0) {
        echo "修改成功！\n";
        echo "新账号: {$newEmail}\n";
        echo "新密码: {$newPassword}\n";
    } else {
        echo "未找到管理员记录\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    exit(1);
}
