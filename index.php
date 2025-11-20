<?php
// index.php
declare(strict_types=1);

define('__KANBAN_ROOT__', __DIR__);

require_once __KANBAN_ROOT__ . '/config.inc.php';
require_once __KANBAN_ROOT__ . '/var/Bootstrap.php';

function parseChecklistPayload($raw): array
{
    if ($raw === null) {
        return [];
    }

    $decoded = json_decode((string)$raw, true);
    if (!is_array($decoded)) {
        return [];
    }

    $items = [];
    foreach ($decoded as $item) {
        $title = isset($item['title']) ? trim((string)$item['title']) : '';
        if ($title === '') {
            continue;
        }
        $items[] = [
            'id'    => isset($item['id']) ? (int)$item['id'] : null,
            'title' => $title,
            'done'  => !empty($item['done']),
        ];
    }

    return $items;
}

// 處理 AJAX / POST 動作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    header('Content-Type: application/json; charset=utf-8');

    try {
        switch ($action) {
            case 'add_card':
                $columnId    = (int)($_POST['column_id'] ?? 0);
                $title       = trim($_POST['title'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $typeId      = isset($_POST['type_id']) && $_POST['type_id'] !== '' ? (int)$_POST['type_id'] : null;
                $checklist   = parseChecklistPayload($_POST['checklist'] ?? null);

                if (!$columnId || $title === '') {
                    throw new RuntimeException('缺少欄位或標題');
                }

                $cardId = Kanban_Widget_Card::create($columnId, $title, $description, $typeId);
                if (!empty($checklist)) {
                    Kanban_Widget_Card::replaceChecklist($cardId, $checklist);
                }
                echo json_encode(['ok' => true]);
                break;

            case 'reorder_cards':
                // 期望收到 column_id + cards[]=ID...
                $columnId  = (int)($_POST['column_id'] ?? 0);
                $cardIds   = $_POST['cards'] ?? [];

                if (!$columnId || !is_array($cardIds)) {
                    throw new RuntimeException('資料不正確');
                }

                $cardIds = array_map('intval', $cardIds);
                Kanban_Widget_Card::reorderColumn($columnId, $cardIds);
                echo json_encode(['ok' => true]);
                break;

            case 'move_card':
                // 跨欄位拖拉：card_id + to_column_id + order[]
                $cardId      = (int)($_POST['card_id'] ?? 0);
                $toColumnId  = (int)($_POST['to_column_id'] ?? 0);
                $order       = $_POST['cards'] ?? [];

                if (!$cardId || !$toColumnId || !is_array($order)) {
                    throw new RuntimeException('資料不正確');
                }

                $order = array_map('intval', $order);
                Kanban_Widget_Card::moveAndReorder($cardId, $toColumnId, $order);
                echo json_encode(['ok' => true]);
                break;

            case 'reorder_columns':
                // 拖拉欄位：board_id + columns[]=ID...
                $boardId  = (int)($_POST['board_id'] ?? 0);
                $columns  = $_POST['columns'] ?? [];

                if (!$boardId || !is_array($columns)) {
                    throw new RuntimeException('資料不正確');
                }

                $columns = array_map('intval', $columns);
                Kanban_Widget_Column::reorderBoard($boardId, $columns);
                echo json_encode(['ok' => true]);
                break;

            case 'get_card':
                $cardId = (int)($_POST['card_id'] ?? 0);
                if (!$cardId) {
                    throw new RuntimeException('缺少 card_id');
                }
                $card = Kanban_Widget_Card::fetchOne($cardId);
                echo json_encode([
                    'ok'   => true,
                    'card' => [
                        'id'          => (int)$card['id'],
                        'column_id'   => (int)$card['column_id'],
                        'title'       => $card['title'],
                        'description' => $card['description'] ?? '',
                        'type_id'     => $card['type_id'] ?? null,
                        'checklist'   => Kanban_Widget_Card::fetchChecklist($cardId),
                    ],
                ]);
                break;

            case 'update_card':
                $cardId      = (int)($_POST['card_id'] ?? 0);
                $columnId    = isset($_POST['column_id']) ? (int)$_POST['column_id'] : null;
                $title       = trim($_POST['title'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $typeId      = isset($_POST['type_id']) && $_POST['type_id'] !== '' ? (int)$_POST['type_id'] : null;
                $checklist   = parseChecklistPayload($_POST['checklist'] ?? null);

                if (!$cardId || $title === '') {
                    throw new RuntimeException('缺少 card_id 或標題');
                }

                Kanban_Widget_Card::updateCard($cardId, $title, $description, $typeId, $columnId, $checklist);
                echo json_encode(['ok' => true]);
                break;
                
            case 'get_board_settings':
                $boardId = (int)($_POST['board_id'] ?? 0);
                if (!$boardId) throw new RuntimeException('缺少 board_id');

                $db  = Kanban_Db::getInstance();

                // 看板名稱
                $stmt = $db->prepare('SELECT id, name FROM boards WHERE id = :id');
                $stmt->execute(['id' => $boardId]);
                $boardRow = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$boardRow) throw new RuntimeException('Board not found');

                // Card Types
                $stmt = $db->prepare('SELECT id, name, color FROM card_types WHERE board_id = :bid ORDER BY id ASC');
                $stmt->execute(['bid' => $boardId]);
                $types = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // To-do Templates
                $stmt = $db->prepare('SELECT id, name, items FROM todo_templates WHERE board_id = :bid ORDER BY id ASC');
                $stmt->execute(['bid' => $boardId]);
                $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode([
                    'ok' => true,
                    'board' => $boardRow,
                    'types' => $types,
                    'templates' => $templates,
                ]);
                break;

            case 'save_board_name':
                $boardId = (int)($_POST['board_id'] ?? 0);
                $name    = trim($_POST['name'] ?? '');
                if (!$boardId || $name === '') throw new RuntimeException('缺少資料');

                $db = Kanban_Db::getInstance();
                $stmt = $db->prepare('UPDATE boards SET name = :name WHERE id = :id');
                $stmt->execute(['name' => $name, 'id' => $boardId]);
                echo json_encode(['ok' => true]);
                break;

            case 'save_card_type':
                $boardId = (int)($_POST['board_id'] ?? 0);
                $id      = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
                $name    = trim($_POST['name'] ?? '');
                $color   = trim($_POST['color'] ?? '#D1D5DB');

                if (!$boardId || $name === '') throw new RuntimeException('缺少資料');

                $db = Kanban_Db::getInstance();
                if ($id) {
                    $stmt = $db->prepare('UPDATE card_types SET name = :name, color = :color WHERE id = :id AND board_id = :bid');
                    $stmt->execute(['name' => $name, 'color' => $color, 'id' => $id, 'bid' => $boardId]);
                } else {
                    $stmt = $db->prepare('INSERT INTO card_types (board_id, name, color) VALUES (:bid, :name, :color)');
                    $stmt->execute(['bid' => $boardId, 'name' => $name, 'color' => $color]);
                    $id = (int)$db->lastInsertId();
                }
                echo json_encode(['ok' => true, 'id' => $id]);
                break;

            case 'delete_card_type':
                $id = (int)($_POST['id'] ?? 0);
                if (!$id) throw new RuntimeException('缺少 id');

                $db = Kanban_Db::getInstance();
                $stmt = $db->prepare('DELETE FROM card_types WHERE id = :id');
                $stmt->execute(['id' => $id]);
                echo json_encode(['ok' => true]);
                break;

            case 'save_todo_template':
                $boardId = (int)($_POST['board_id'] ?? 0);
                $id      = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
                $name    = trim($_POST['name'] ?? '');
                $items   = $_POST['items'] ?? '';

                if (!$boardId || $name === '') throw new RuntimeException('缺少資料');

                // 前端會傳「一行一個 item」的字串，後端轉成 JSON array
                $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $items)));
                $itemsJson = json_encode(array_values($lines), JSON_UNESCAPED_UNICODE);

                $db = Kanban_Db::getInstance();
                if ($id) {
                    $stmt = $db->prepare('UPDATE todo_templates SET name = :name, items = :items WHERE id = :id AND board_id = :bid');
                    $stmt->execute(['name' => $name, 'items' => $itemsJson, 'id' => $id, 'bid' => $boardId]);
                } else {
                    $stmt = $db->prepare('INSERT INTO todo_templates (board_id, name, items) VALUES (:bid, :name, :items)');
                    $stmt->execute(['bid' => $boardId, 'name' => $name, 'items' => $itemsJson]);
                    $id = (int)$db->lastInsertId();
                }
                echo json_encode(['ok' => true, 'id' => $id]);
                break;

            case 'delete_todo_template':
                $id = (int)($_POST['id'] ?? 0);
                if (!$id) throw new RuntimeException('缺少 id');

                $db = Kanban_Db::getInstance();
                $stmt = $db->prepare('DELETE FROM todo_templates WHERE id = :id');
                $stmt->execute(['id' => $id]);
                echo json_encode(['ok' => true]);
                break;

            case 'apply_todo_template':
                $cardId     = (int)($_POST['card_id'] ?? 0);
                $templateId = (int)($_POST['template_id'] ?? 0);
                if (!$cardId || !$templateId) throw new RuntimeException('缺少資料');

                $db = Kanban_Db::getInstance();

                // 撈模板
                $stmt = $db->prepare('SELECT items FROM todo_templates WHERE id = :id');
                $stmt->execute(['id' => $templateId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$row) throw new RuntimeException('Template not found');

                $items = json_decode($row['items'], true) ?: [];
                $checklist = Kanban_Widget_Card::appendChecklistItems($cardId, $items);

                echo json_encode(['ok' => true, 'checklist' => $checklist]);
                break;

            default:
                throw new RuntimeException('未知動作');
        }
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode([
            'ok'    => false,
            'error' => $e->getMessage(),
        ]);
    }

    exit;
}

$theme = new Kanban_Theme($kanbanTheme);

// 有帶 board_id → 顯示某個看板
$boardId = isset($_GET['board_id']) ? (int)$_GET['board_id'] : 0;

if ($boardId > 0) {
    $board = Kanban_Widget_Board::instance($boardId);
    $theme->render('board.php', [
        'board' => $board,
    ]);
    exit;
}

// 否則顯示看板列表
$boardList = Kanban_Widget_BoardList::instance();
$theme->render('index.php', [
    'boards' => $boardList->boards,
]);
