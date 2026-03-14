<?php
// Railway 健康检查 - 不依赖数据库，直接返回 200
header('Content-Type: text/plain');
http_response_code(200);
echo 'OK';
