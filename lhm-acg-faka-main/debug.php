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
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$ctx = stream_context_create(['http' => ['timeout' => 5]]);

// 测试1: 直接访问 index.php
$url1 = $baseUrl . '/index.php?s=/user/index/index';
$c1 = @file_get_contents($url1, false, $ctx);
$len1 = $c1 !== false ? strlen($c1) : 0;
echo "<p><b>直接 index.php:</b> <code>" . htmlspecialchars($url1) . "</code> → " . $len1 . " 字节</p>";

// 测试2: 访问根路径 /
$url2 = $baseUrl . '/';
$c2 = @file_get_contents($url2, false, $ctx);
$len2 = $c2 !== false ? strlen($c2) : 0;
echo "<p><b>根路径 /:</b> " . $len2 . " 字节</p>";

if ($len1 > 0 || $len2 > 0) {
    $content = $len1 > 0 ? $c1 : $c2;
    echo "<p style='color:green'>✓ 有返回内容</p>";
    echo "<details><summary>前 800 字符</summary><pre style='background:#f5f5f5;padding:10px;overflow:auto;max-height:250px;font-size:11px;'>" . htmlspecialchars(substr($content, 0, 800)) . "</pre></details>";
} else {
    echo "<p style='color:red'>✗ 两者均返回空，可能是 PHP 致命错误被静默</p>";
}
?>

<h2>8. 应用引导测试（捕获错误）</h2>
<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
$_GET['s'] = '/user/index/index';
ob_start();
try {
    require __DIR__ . '/index.php';
    $out = ob_get_clean();
    echo "<p style='color:green'>✓ 引导成功，输出 " . strlen($out) . " 字节</p>";
    echo "<details><summary>输出预览</summary><pre style='background:#f5f5f5;padding:10px;overflow:auto;max-height:200px;font-size:11px;'>" . htmlspecialchars(substr($out, 0, 600)) . "</pre></details>";
} catch (Throwable $e) {
    ob_end_clean();
    echo "<p style='color:red'>✗ 错误: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre style='background:#ffe0e0;padding:10px;font-size:11px;'>" . htmlspecialchars($e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString()) . "</pre>";
}
?>

<hr><p><small>诊断完成后请删除 debug.php</small></p>
</body>
</html>
