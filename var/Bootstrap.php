<?php
// var/Bootstrap.php

require_once __KANBAN_ROOT__ . '/var/Db.php';
require_once __KANBAN_ROOT__ . '/var/Theme.php';

spl_autoload_register(function ($class) {
    // 例如：Kanban_Widget_Board → var/Widget/Board.php
    if (str_starts_with($class, 'Kanban_')) {
        $path = str_replace('Kanban_', '', $class); // Widget_Board
        $path = str_replace('_', DIRECTORY_SEPARATOR, $path); // Widget/Board
        $file = __KANBAN_ROOT__ . '/var/' . $path . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});
