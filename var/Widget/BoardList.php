<?php
// var/Widget/BoardList.php

require_once __KANBAN_ROOT__ . '/var/Widget/Base.php';

class Kanban_Widget_BoardList extends Kanban_Widget_Base
{
    /** @var array<int, array> */
    public array $boards = [];

    public function __construct()
    {
        parent::__construct();

        $stmt = $this->db->query('SELECT * FROM boards ORDER BY id ASC');
        $this->boards = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
