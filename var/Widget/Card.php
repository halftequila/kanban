<?php
// var/Widget/Card.php

require_once __KANBAN_ROOT__ . '/var/Widget/Base.php';

class Kanban_Widget_Card extends Kanban_Widget_Base
{
    public int $id;
    public int $columnId;
    public ?int $typeId;       // 新增
    public string $title;
    public ?string $description;
    public int $position;

    public function __construct(int $cardId, ?array $data = null)
    {
        parent::__construct();

        if ($data === null) {
            $stmt = $this->db->prepare('SELECT * FROM cards WHERE id = :id');
            $stmt->execute(['id' => $cardId]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if (!$data) {
            throw new RuntimeException('Card not found');
        }

        $this->id          = (int)$data['id'];
        $this->columnId    = (int)$data['column_id'];
        $this->typeId      = isset($data['type_id']) ? (int)$data['type_id'] : null; // 新增
        $this->title       = $data['title'];
        $this->description = $data['description'] ?? null;
        $this->position    = (int)$data['position'];
    }

    public static function create(int $columnId, string $title, string $description = '', ?int $typeId = null): int
    {
        $db = Kanban_Db::getInstance();

        // 取得目前欄位最大 position
        $stmt = $db->prepare('SELECT COALESCE(MAX(position), 0) AS max_pos FROM cards WHERE column_id = :cid');
        $stmt->execute(['cid' => $columnId]);
        $maxPos = (int)$stmt->fetchColumn();

        $stmt = $db->prepare('INSERT INTO cards (column_id, title, description, position, type_id)
                              VALUES (:cid, :title, :desc, :pos, :type_id)');
        $stmt->execute([
            'cid' => $columnId,
            'title' => $title,
            'desc' => $description,
            'pos' => $maxPos + 1,
            'type_id' => $typeId,
        ]);

        return (int)$db->lastInsertId();
    }

    public static function reorderColumn(int $columnId, array $cardIds): void
    {
        $db = Kanban_Db::getInstance();
        $db->beginTransaction();

        try {
            $pos = 1;
            $stmt = $db->prepare('UPDATE cards SET position = :pos WHERE id = :id AND column_id = :cid');

            foreach ($cardIds as $id) {
                $stmt->execute([
                    'pos' => $pos++,
                    'id'  => $id,
                    'cid' => $columnId,
                ]);
            }

            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
    * 跨欄位移動：更新該卡 column_id，然後對新欄位重新排序
    */
    public static function moveAndReorder(int $cardId, int $toColumnId, array $orderedIds): void
    {
        $db = Kanban_Db::getInstance();
        $db->beginTransaction();

        try {
            // 先把這張卡移到新欄位
            $stmt = $db->prepare('UPDATE cards SET column_id = :cid WHERE id = :id');
            $stmt->execute([
                'cid' => $toColumnId,
                'id'  => $cardId,
            ]);

            // 然後依照前端給的順序，重排 position
            $pos = 1;
            $stmt2 = $db->prepare('UPDATE cards SET position = :pos WHERE id = :id AND column_id = :cid');

            foreach ($orderedIds as $id) {
                $stmt2->execute([
                    'pos' => $pos++,
                    'id'  => $id,
                    'cid' => $toColumnId,
                ]);
            }

            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
    public static function fetchOne(int $cardId): array
    {
        $db = Kanban_Db::getInstance();
        $stmt = $db->prepare('SELECT * FROM cards WHERE id = :id');
        $stmt->execute(['id' => $cardId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new RuntimeException('Card not found');
        }
        return [
            'id'          => (int)$row['id'],
            'column_id'   => (int)$row['column_id'],
            'type_id'     => isset($row['type_id']) ? (int)$row['type_id'] : null,
            'title'       => $row['title'],
            'description' => $row['description'] ?? '',
        ];
    }


    public static function updateCard(
        int $cardId,
        string $title,
        string $description = '',
        ?int $typeId = null,
        ?int $columnId = null,
        ?array $checklist = null
    ): void
    {
        $db = Kanban_Db::getInstance();
        $stmt = $db->prepare('SELECT column_id FROM cards WHERE id = :id');
        $stmt->execute(['id' => $cardId]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$current) {
            throw new RuntimeException('Card not found');
        }

        $currentColumn = (int)$current['column_id'];
        $targetColumn  = $columnId ?? $currentColumn;

        $db->beginTransaction();

        try {
            if ($targetColumn !== $currentColumn) {
                $stmtPos = $db->prepare('SELECT COALESCE(MAX(position), 0) FROM cards WHERE column_id = :cid');
                $stmtPos->execute(['cid' => $targetColumn]);
                $newPos = ((int)$stmtPos->fetchColumn()) + 1;

                $stmtUpdate = $db->prepare(
                    'UPDATE cards
                     SET column_id = :column_id,
                         position = :position,
                         title = :title,
                         description = :description,
                         type_id = :type_id
                     WHERE id = :id'
                );
                $stmtUpdate->execute([
                    'column_id'   => $targetColumn,
                    'position'    => $newPos,
                    'title'       => $title,
                    'description' => $description,
                    'type_id'     => $typeId,
                    'id'          => $cardId,
                ]);
            } else {
                $stmtUpdate = $db->prepare(
                    'UPDATE cards
                     SET title = :title,
                         description = :description,
                         type_id = :type_id
                     WHERE id = :id'
                );
                $stmtUpdate->execute([
                    'title'       => $title,
                    'description' => $description,
                    'type_id'     => $typeId,
                    'id'          => $cardId,
                ]);
            }

            if (is_array($checklist)) {
                self::replaceChecklist($cardId, $checklist);
            }

            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * 建立 card_checklist_items 表（若不存在）。
     */
    protected static function ensureChecklistTable(): void
    {
        $db = Kanban_Db::getInstance();
        $db->exec(
            'CREATE TABLE IF NOT EXISTS card_checklist_items (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                card_id INT NOT NULL,
                title TEXT NOT NULL,
                is_done TINYINT(1) NOT NULL DEFAULT 0,
                position INT NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_card (card_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    public static function fetchChecklist(int $cardId): array
    {
        self::ensureChecklistTable();

        $db = Kanban_Db::getInstance();
        $stmt = $db->prepare('SELECT id, title, is_done, position FROM card_checklist_items WHERE card_id = :cid ORDER BY position ASC, id ASC');
        $stmt->execute(['cid' => $cardId]);

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(static function ($row) {
            return [
                'id'     => (int)$row['id'],
                'title'  => $row['title'],
                'done'   => (bool)$row['is_done'],
                'position' => (int)$row['position'],
            ];
        }, $items);
    }

    /**
     * 以傳入資料覆寫該卡片的 checklist。
     * 陣列格式：[['id' => ?, 'title' => '文字', 'done' => bool], ...]
     */
    public static function replaceChecklist(int $cardId, array $items): void
    {
        self::ensureChecklistTable();

        $db = Kanban_Db::getInstance();
        $hasOuterTx = $db->inTransaction();
        if (!$hasOuterTx) {
            $db->beginTransaction();
        }

        try {
            $db->prepare('DELETE FROM card_checklist_items WHERE card_id = :cid')->execute(['cid' => $cardId]);

            if (!empty($items)) {
                $stmtInsert = $db->prepare(
                    'INSERT INTO card_checklist_items (card_id, title, is_done, position)
                     VALUES (:cid, :title, :done, :pos)'
                );

                $pos = 1;
                foreach ($items as $item) {
                    $title = trim((string)($item['title'] ?? ''));
                    if ($title === '') {
                        continue;
                    }
                    $stmtInsert->execute([
                        'cid'   => $cardId,
                        'title' => $title,
                        'done'  => !empty($item['done']) ? 1 : 0,
                        'pos'   => $pos++,
                    ]);
                }
            }

            if (!$hasOuterTx) {
                $db->commit();
            }
        } catch (Throwable $e) {
            if (!$hasOuterTx) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * 追加模板項目到現有 checklist，並回傳最新列表。
     */
    public static function appendChecklistItems(int $cardId, array $titles): array
    {
        self::ensureChecklistTable();

        if (empty($titles)) {
            return self::fetchChecklist($cardId);
        }

        $db = Kanban_Db::getInstance();
        $hasOuterTx = $db->inTransaction();
        if (!$hasOuterTx) {
            $db->beginTransaction();
        }

        try {
            $stmtPos = $db->prepare('SELECT COALESCE(MAX(position), 0) FROM card_checklist_items WHERE card_id = :cid');
            $stmtPos->execute(['cid' => $cardId]);
            $pos = ((int)$stmtPos->fetchColumn()) + 1;

            $stmtInsert = $db->prepare(
                'INSERT INTO card_checklist_items (card_id, title, is_done, position)
                 VALUES (:cid, :title, 0, :pos)'
            );

            foreach ($titles as $title) {
                $title = trim((string)$title);
                if ($title === '') {
                    continue;
                }
                $stmtInsert->execute([
                    'cid'   => $cardId,
                    'title' => $title,
                    'pos'   => $pos++,
                ]);
            }

            if (!$hasOuterTx) {
                $db->commit();
            }
        } catch (Throwable $e) {
            if (!$hasOuterTx) {
                $db->rollBack();
            }
            throw $e;
        }

        return self::fetchChecklist($cardId);
    }


}
