<?php
// var/Widget/Board.php

require_once __KANBAN_ROOT__ . '/var/Widget/Base.php';
require_once __KANBAN_ROOT__ . '/var/Widget/Column.php';

class Kanban_Widget_Board extends Kanban_Widget_Base
{
    public int $id;
    public string $name;
    /** @var Kanban_Widget_Column[] */
    public array $columns = [];

    public function __construct(int $boardId)
    {
        parent::__construct();

        $stmt = $this->db->prepare('SELECT * FROM boards WHERE id = :id');
        $stmt->execute(['id' => $boardId]);
        $board = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$board) {
            throw new RuntimeException('Board not found');
        }

        $this->id = (int)$board['id'];
        $this->name = $board['name'];

        $this->loadColumns();
    }

    protected function loadColumns(): void
    {
        $stmt = $this->db->prepare('SELECT * FROM columns WHERE board_id = :bid ORDER BY position ASC, id ASC');
        $stmt->execute(['bid' => $this->id]);

        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            $this->columns[] = new Kanban_Widget_Column((int)$col['id'], $col);
        }
    }
}
