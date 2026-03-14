<?php
/**
 * 部署诊断页面 - 排查空白页问题（部署完成后请删除此文件）
 */
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>部署诊断</title></head>
<body>
<h1>ACG 发卡 - 部署诊断</h1>

<h2>1. PHP 环境</h2>
<p>PHP 版本: <?= phpversion() ?></p>
<p>扩展: gd=<?= extension_loaded('gd') ? '✓' : '✗' ?>, pdo_mysql=<?= extension_loaded('pdo_mysql') ? '✓' : '✗' ?>, zip=<?= extension_loaded('zip') ? '✓' : '✗' ?></p>

<h2>2. Railway 数据库环境变量</h2>
<p>MYSQLHOST: <?= getenv('MYSQLHOST') ?: '<span style="color:red">未设置</span>' ?></p>
<p>MYSQLUSER: <?= getenv('MYSQLUSER') ?: '(未设置)' ?></p>
<p>MYSQLDATABASE: <?= getenv('MYSQLDATABASE') ?: '(未设置)' ?></p>
<p>MYSQLPORT: <?= getenv('MYSQLPORT') ?: '(未设置，默认3306)' ?></p>
<p>MYSQL_URL: <?= getenv('MYSQL_URL') ? '已设置' : '(未设置)' ?></p>

<h2>3. 数据库配置</h2>
<?php
$dbFile = __DIR__ . '/config/database.php';
if (file_exists($dbFile)) {
    $db = require $dbFile;
    echo '<p>host: ' . htmlspecialchars($db['host'] ?? '') . '</p>';
    echo '<p>port: ' . htmlspecialchars($db['port'] ?? '3306') . '</p>';
    echo '<p>database: ' . htmlspecialchars($db['database'] ?? '') . '</p>';
    echo '<p>username: ' . htmlspecialchars($db['username'] ?? '') . '</p>';
} else {
    echo '<p style="color:red">config/database.php 不存在</p>';
}
?>

<h2>4. 数据库连接测试</h2>
<?php
if (file_exists($dbFile)) {
    $db = require $dbFile;
    $port = $db['port'] ?? '3306';
    try {
        $dsn = "mysql:host={$db['host']};port={$port};dbname={$db['database']};charset=utf8mb4";
        new PDO($dsn, $db['username'], $db['password'] ?? '');
        echo '<p style="color:green">✓ 数据库连接成功</p>';
    } catch (PDOException $e) {
        echo '<p style="color:red">✗ 连接失败: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
}
?>

<h2>5. 安装状态</h2>
<p>Lock 文件: <?= file_exists(__DIR__ . '/kernel/Install/Lock') ? '✓ 已安装' : '<span style="color:orange">未安装（需访问 /install/step 完成安装）</span>' ?></p>

<h2>6. 建议</h2>
<?php if (!getenv('MYSQLHOST') && !getenv('MYSQL_URL')): ?>
<p style="color:red;font-weight:bold">⚠ 未检测到 MySQL 环境变量。请在 Railway 中：</p>
<ol>
<li>添加 MySQL 服务（New → Database → MySQL）</li>
<li>在 Web 服务的 Variables 中引用 MySQL 变量（或使用 MYSQL_URL）</li>
<li>重新部署</li>
</ol>
<?php elseif (!file_exists(__DIR__ . '/kernel/Install/Lock')): ?>
<p>数据库已配置。请访问 <a href="/install/step">/install/step</a> 完成安装。</p>
<?php else: ?>
<p style="color:green">配置正常。</p>
<?php endif; ?>

<h2>7. 首页响应测试</h2>
<?php
$homeUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/';
$ctx = stream_context_create(['http' => ['timeout' => 5]]);
$homeContent = @file_get_contents($homeUrl, false, $ctx);
$len = $homeContent !== false ? strlen($homeContent) : 0;
echo "<p>首页 URL: <code>" . htmlspecialchars($homeUrl) . "</code></p>";
echo "<p>响应长度: " . $len . " 字节</p>";
if ($len > 0) {
    echo "<p style='color:green'>✓ 首页有返回内容</p>";
    echo "<details><summary>前 500 字符预览</summary><pre style='background:#f5f5f5;padding:10px;overflow:auto;max-height:200px;'>" . htmlspecialchars(substr($homeContent, 0, 500)) . "</pre></details>";
} else {
    echo "<p style='color:red'>✗ 首页返回为空或请求失败</p>";
}
?>

<hr><p><small>诊断完成后请删除 debug.php</small></p>
</body>
</html>
