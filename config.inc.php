<?php
// config.inc.php
if (!defined('__KANBAN_ROOT__')) {
    define('__KANBAN_ROOT__', __DIR__);
}

$db = [
    'host' => '127.0.0.1',
    'port' => '3306',
    'user' => 'kanban_user',
    'password' => 'kanban_password',
    'dbname' => 'kanban_db',
    'charset' => 'utf8mb4',
];

$kanbanTheme = 'default';