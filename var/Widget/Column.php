<?php
// var/Widget/Column.php

require_once __KANBAN_ROOT__ . '/var/Widget/Base.php';
require_once __KANBAN_ROOT__ . '/var/Widget/Card.php';

class Kanban_Widget_Column extends Kanban_Widget_Base
{
    public int $id;
    public int $boardId;
    public string $name;
    public int $position;
    /** @var Kanban_Widget_Card[] */
    public array $cards = [];

    public function __construct(int $columnId, ?array $data = null)
    {
        parent::__construct();

        if ($data === null) {
            $stmt = $this->db->prepare('SELECT * FROM columns WHERE id = :id');
            $stmt->execute(['id' => $columnId]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if (!$data) {
            throw new RuntimeException('Column not found');
        }

        $this->id = (int)$data['id'];
        $this->boardId = (int)$data['board_id'];
        $this->name = $data['name'];
        $this->position = (int)$data['position'];

        $this->loadCards();
    }

    protected function loadCards(): void
    {
        $stmt = $this->db->prepare('SELECT * FROM cards WHERE column_id = :cid ORDER BY position ASC, id ASC');
        $stmt->execute(['cid' => $this->id]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $this->cards[] = new Kanban_Widget_Card((int)$row['id'], $row);
        }
    }
    public static function reorderBoard(int $boardId, array $columnIds): void
    {
        $db = Kanban_Db::getInstance();
        $db->beginTransaction();

        try {
            $pos = 1;
            $stmt = $db->prepare('UPDATE columns SET position = :pos WHERE id = :id AND board_id = :bid');

            foreach ($columnIds as $id) {
                $stmt->execute([
                    'pos' => $pos++,
                    'id'  => $id,
                    'bid' => $boardId,
                ]);
            }

            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

}
