<?php
// var/Widget/Base.php

abstract class Kanban_Widget_Base
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Kanban_Db::getInstance();
    }

    public static function instance(...$args)
    {
        return new static(...$args);
    }
}
