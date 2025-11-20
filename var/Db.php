<?php
// var/Db.php

class Kanban_Db
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        global $db;

        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $db['host'],
                $db['port'],
                $db['dbname'],
                $db['charset']
            );

            self::$instance = new PDO($dsn, $db['user'], $db['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        }

        return self::$instance;
    }
}
