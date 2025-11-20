<?php /** @var array $boards */ ?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>Kanban - Boards</title>
    <style>
        :root {
            --accent: #006CFF;
        }
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #f5f5f7;
            margin: 0;
            padding: 40px 24px;
        }
        .wrap {
            max-width: 960px;
            margin: 0 auto;
        }
        h1 {
            font-size: 24px;
            font-weight: 600;
            margin: 0 0 24px;
        }
        .boards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 16px;
        }
        .board-card {
            background: #fff;
            border-radius: 12px;
            padding: 16px;
            text-decoration: none;
            color: #111;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
            border: 1px solid transparent;
            transition: border-color .15s ease, box-shadow .15s ease, transform .1s;
        }
        .board-card:hover {
            border-color: var(--accent);
            box-shadow: 0 4px 10px rgba(0,0,0,.08);
            transform: translateY(-1px);
        }
        .board-name {
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 4px;
        }
        .board-meta {
            font-size: 12px;
            color: #777;
        }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Boards</h1>
    <div class="boards">
        <?php foreach ($boards as $b): ?>
            <a class="board-card" href="?board_id=<?= (int)$b['id'] ?>">
                <div class="board-name"><?= htmlspecialchars($b['name']) ?></div>
                <div class="board-meta">
                    #<?= (int)$b['id'] ?> · 建立於 <?= htmlspecialchars($b['created_at']) ?>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
