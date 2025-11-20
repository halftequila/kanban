<?php
define('__KANBAN_ROOT__', __DIR__);
require_once __KANBAN_ROOT__ . '/config.inc.php';
require_once __KANBAN_ROOT__ . '/var/Db.php';

try {
    $pdo = Kanban_Db::getInstance();
    $stmt = $pdo->query('SELECT COUNT(*) FROM boards');
    $count = $stmt->fetchColumn();
    echo "連線成功，boards 目前有 {$count} 筆資料";
} catch (Throwable $e) {
    echo "連線失敗： " . $e->getMessage();
}