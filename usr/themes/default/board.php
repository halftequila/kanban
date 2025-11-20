<?php
/** @var Kanban_Widget_Board $board */
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($board->name) ?> - Kanban</title>
    <style>
        :root {
            --accent: #006CFF;
            --bg: #f5f5f7;
            --card-bg: #ffffff;
            --column-bg: #fafafa;
            --border-subtle: #e5e5ea;
            --text-main: #111111;
            --text-sub: #666666;
        }
        * { box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            margin: 0;
            padding: 24px;
            background: var(--bg);
            color: var(--text-main);
        }
        .topbar {
            max-width: 1200px;
            margin: 0 auto 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .board-title {
            font-size: 20px;
            font-weight: 600;
        }
        .top-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .primary-btn {
            font-size: 13px;
            padding: 6px 12px;
            border-radius: 999px;
            border: none;
            background: var(--accent);
            color: #fff;
            cursor: pointer;
        }
        .primary-btn:hover { filter: brightness(1.05); }
        .back-link {
            font-size: 13px;
            color: var(--text-sub);
            text-decoration: none;
            padding: 4px 10px;
            border-radius: 999px;
            border: 1px solid transparent;
        }
        .back-link:hover {
            border-color: var(--accent);
            color: var(--accent);
        }
        .board-wrap {
            max-width: 1200px;
            margin: 0 auto;
        }
        .columns {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            overflow-x: auto;
            padding-bottom: 8px;
        }
        .column {
            background: var(--column-bg);
            border-radius: 10px;
            padding: 10px;
            width: 280px;
            min-width: 240px;
            border: 1px solid var(--border-subtle);
            box-shadow: 0 1px 2px rgba(0,0,0,.03);
            display: flex;
            flex-direction: column;
        }
        .column-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 500;
        }
        .column-name { padding: 2px 0; }
        .column-meta {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            color: var(--text-sub);
        }
        .column-badge {
            padding: 1px 6px;
            border-radius: 999px;
            background: #e5f0ff;
            color: var(--accent);
        }
        .column-add-btn {
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 16px;
            line-height: 1;
            padding: 2px 4px;
            color: var(--text-sub);
            border-radius: 999px;
        }
        .column-add-btn:hover {
            background: rgba(0,108,255,.08);
            color: var(--accent);
        }
        .column.drag-over {
            border-color: var(--accent);
            box-shadow: 0 0 0 1px rgba(0,108,255,.2);
        }
        .cards {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-height: 20px;
        }
        .card {
            background: var(--card-bg);
            border-radius: 8px;
            padding: 8px;
            border: 1px solid var(--border-subtle);
            font-size: 13px;
            cursor: grab;
            user-select: none;
            transition: box-shadow .1s ease, border-color .1s ease, transform .1s;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-main {
            display: flex;
            align-items: center;
            gap: 6px;
            overflow: hidden;
        }
        .card-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #d1d5db; /* 預設灰色，會被 JS 覆蓋 */
            flex-shrink: 0;
        }
        .card-title {
            font-weight: 500;
            white-space: nowrap;
            text-overflow: ellipsis;
            overflow: hidden;
        }
        .card:hover {
            border-color: #d0d0d7;
            box-shadow: 0 2px 6px rgba(0,0,0,.06);
        }
        .card.dragging {
            opacity: .85;
            border-color: var(--accent);
            box-shadow: 0 4px 10px rgba(0,0,0,.12);
            transform: scale(1.02);
            cursor: grabbing;
        }
        .toast {
            position: fixed;
            right: 16px;
            bottom: 16px;
            background: #111827;
            color: #fff;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 12px;
            opacity: 0;
            transform: translateY(10px);
            pointer-events: none;
            transition: opacity .15s ease, transform .15s ease;
            z-index: 60;
        }
        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }
        /* 共用 modal 外層 */
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15,23,42,.35);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
            opacity: 0;
            pointer-events: none;
            transition: opacity .15s ease;
        }
        .modal-backdrop.show {
            opacity: 1;
            pointer-events: auto;
        }
        .modal {
            width: 420px;
            max-width: 90vw;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 24px 60px rgba(0,0,0,.35);
            padding: 16px 18px 14px;
        }
        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .modal-title {
            font-size: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .modal-pill {
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 999px;
            background: #e5f0ff;
            color: var(--accent);
        }
        .modal-close {
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 16px;
            line-height: 1;
            padding: 4px;
            border-radius: 999px;
        }
        .modal-close:hover {
            background: rgba(0,0,0,.04);
        }
        .modal-body {
            font-size: 13px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 10px;
        }
        .field-label {
            font-size: 12px;
            color: var(--text-sub);
            margin-bottom: 2px;
        }
        .field-input,
        .field-textarea,
        .field-select {
            width: 100%;
            border-radius: 8px;
            border: 1px solid var(--border-subtle);
            padding: 7px 9px;
            font-size: 13px;
            outline: none;
        }
        .field-input:focus,
        .field-textarea:focus,
        .field-select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 1px rgba(0,108,255,.12);
        }
        .field-textarea {
            resize: vertical;
            min-height: 70px;
        }
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 6px;
        }
        .btn {
            font-size: 13px;
            padding: 6px 12px;
            border-radius: 999px;
            border: 1px solid transparent;
            cursor: pointer;
        }
        .btn-secondary {
            background: transparent;
            border-color: var(--border-subtle);
        }
        .btn-secondary:hover { border-color: #c4c4cf; }
        .btn-primary {
            background: var(--accent);
            color: #fff;
            border: none;
        }
        .btn-primary:hover { filter: brightness(1.05); }
        hr {
            border: none;
            border-top: 1px solid #eee;
            margin: 8px 0;
        }
    </style>
</head>
<body>
<div class="topbar">
    <div class="board-title" id="board-title-text">
        <?= htmlspecialchars($board->name) ?>
    </div>
    <div class="top-actions">
        <button class="primary-btn" type="button" id="board-settings-btn">設定</button>
        <a href="./" class="back-link">所有看板</a>
    </div>
</div>

<div class="board-wrap">
    <div class="columns" data-board-id="<?= (int)$board->id ?>">
        <?php foreach ($board->columns as $column): ?>
            <div class="column" data-column-id="<?= $column->id ?>">
                <div class="column-header">
                    <div class="column-name"><?= htmlspecialchars($column->name) ?></div>
                    <div class="column-meta">
                        <span class="column-badge"><?= count($column->cards) ?></span>
                        <button class="column-add-btn"
                                type="button"
                                data-add-column-id="<?= $column->id ?>"
                                title="在此欄新增卡片">＋</button>
                    </div>
                </div>

                <div class="cards" data-column-id="<?= $column->id ?>">
                    <?php foreach ($column->cards as $card): ?>
                        <div class="card"
                             draggable="true"
                             data-card-id="<?= $card->id ?>"
                             data-column-id="<?= $column->id ?>"
                             data-type-id="<?= $card->typeId ?? '' ?>">
                            <div class="card-main">
                                <div class="card-dot"></div>
                                <div class="card-title">
                                    <?= htmlspecialchars($card->title) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="toast" id="toast"></div>

<!-- 卡片窗中窗 modal -->
<div class="modal-backdrop" id="card-modal-backdrop">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">
                <span id="modal-title-text">卡片</span>
                <span class="modal-pill" id="modal-column-name"></span>
            </div>
            <button class="modal-close" type="button" id="modal-close-btn">×</button>
        </div>
        <form id="card-form">
            <div class="modal-body">
                <input type="hidden" name="card_id" id="card-id-input">
                <div>
                    <div class="field-label">欄位</div>
                    <select class="field-select" name="column_id" id="column-select">
                        <?php foreach ($board->columns as $column): ?>
                            <option value="<?= $column->id ?>">
                                <?= htmlspecialchars($column->name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <div class="field-label">Card Type</div>
                    <select class="field-select" name="type_id" id="type-select">
                        <!-- JS 動態填充 -->
                    </select>
                </div>

                <div>
                    <div class="field-label">To-do 模板</div>
                    <div style="display:flex; gap:6px; align-items:center;">
                        <select class="field-select" id="template-select">
                            <option value="">選擇模板…</option>
                        </select>
                        <button type="button"
                                class="btn btn-secondary"
                                id="template-apply-btn"
                                style="white-space:nowrap;">
                            套用
                        </button>
                    </div>
                </div>

                <div>
                    <div class="field-label">標題</div>
                    <input class="field-input" type="text" name="title" id="title-input" required>
                </div>
                <div>
                    <div class="field-label">內容</div>
                    <textarea class="field-textarea"
                              name="description"
                              id="desc-input"
                              placeholder="在這裡輸入卡片的內容…（套用 To-do 模板時會自動插入勾選清單）"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="modal-cancel-btn">取消</button>
                <button type="submit" class="btn btn-primary" id="modal-submit-btn">儲存</button>
            </div>
        </form>
    </div>
</div>

<!-- 看板設定 modal -->
<div class="modal-backdrop" id="settings-modal-backdrop">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">看板設定</div>
            <button class="modal-close" type="button" id="settings-close-btn">×</button>
        </div>
        <div class="modal-body" style="gap:12px;">
            <div>
                <div class="field-label">看板名稱</div>
                <input class="field-input" type="text" id="board-name-input">
            </div>

            <hr>

            <div>
                <div class="field-label">Card Types</div>
                <div id="card-types-list" style="margin-bottom:6px;"></div>
                <div style="display:flex; gap:6px; margin-top:4px;">
                    <input class="field-input" type="text" id="new-type-name" placeholder="類別名稱">
                    <input class="field-input" type="color" id="new-type-color" value="#006CFF"
                           style="padding:0;width:60px;">
                    <button type="button" class="btn btn-primary" id="add-type-btn">新增類別</button>
                </div>
            </div>

            <hr>

            <div>
                <div class="field-label">To-do 模板</div>
                <div id="templates-list" style="margin-bottom:6px;"></div>
                <div style="margin-top:6px;">
                    <input class="field-input" type="text" id="tpl-name-input" placeholder="模板名稱">
                    <div class="field-label" style="margin-top:4px;">項目（每行一個）</div>
                    <textarea class="field-textarea" id="tpl-items-input" rows="4"
                              placeholder="例如：&#10;設計稿確認&#10;開發&#10;測試&#10;上線"></textarea>
                    <div style="margin-top:6px; display:flex; justify-content:flex-end; gap:6px;">
                        <button type="button" class="btn btn-secondary" id="tpl-clear-btn">清空</button>
                        <button type="button" class="btn btn-primary" id="tpl-save-btn">儲存模板</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const boardId = <?= (int)$board->id ?>;

    const toastEl          = document.getElementById('toast');
    const boardTitleText   = document.getElementById('board-title-text');

    // Card modal elements
    const cardModalBackdrop = document.getElementById('card-modal-backdrop');
    const modalTitleText    = document.getElementById('modal-title-text');
    const modalColumnName   = document.getElementById('modal-column-name');
    const modalCloseBtn     = document.getElementById('modal-close-btn');
    const modalCancelBtn    = document.getElementById('modal-cancel-btn');
    const cardForm          = document.getElementById('card-form');
    const cardIdInput       = document.getElementById('card-id-input');
    const columnSelect      = document.getElementById('column-select');
    const typeSelect        = document.getElementById('type-select');
    const templateSelect    = document.getElementById('template-select');
    const templateApplyBtn  = document.getElementById('template-apply-btn');
    const titleInput        = document.getElementById('title-input');
    const descInput         = document.getElementById('desc-input');

    // Settings modal elements
    const settingsBackdrop  = document.getElementById('settings-modal-backdrop');
    const settingsBtn       = document.getElementById('board-settings-btn');
    const settingsCloseBtn  = document.getElementById('settings-close-btn');
    const boardNameInput    = document.getElementById('board-name-input');
    const cardTypesListEl   = document.getElementById('card-types-list');
    const newTypeNameInput  = document.getElementById('new-type-name');
    const newTypeColorInput = document.getElementById('new-type-color');
    const addTypeBtn        = document.getElementById('add-type-btn');
    const templatesListEl   = document.getElementById('templates-list');
    const tplNameInput      = document.getElementById('tpl-name-input');
    const tplItemsInput     = document.getElementById('tpl-items-input');
    const tplClearBtn       = document.getElementById('tpl-clear-btn');
    const tplSaveBtn        = document.getElementById('tpl-save-btn');

    let isDragging = false;

    const currentSettings = {
        board: null,
        types: [],
        templates: []
    };

    function showToast(msg) {
        if (!toastEl) return;
        toastEl.textContent = msg;
        toastEl.classList.add('show');
        setTimeout(() => toastEl.classList.remove('show'), 1500);
    }

    // Column id -> name map
    const columnNameMap = {};
    document.querySelectorAll('.column').forEach(col => {
        const id = col.dataset.columnId;
        const name = col.querySelector('.column-name')?.textContent?.trim() || '';
        columnNameMap[id] = name;
    });

    /* --------- Card Modal ---------- */

    function openCardModal(mode, opts) {
        const {columnId, columnName, cardId, title, description, typeId} = opts;
        cardIdInput.value = cardId || '';
        columnSelect.value = columnId || (columnSelect.options[0] && columnSelect.options[0].value) || '';
        modalColumnName.textContent = columnName || '';
        titleInput.value = title || '';
        descInput.value  = description || '';
        if (typeSelect) {
            typeSelect.value = typeId ? String(typeId) : '';
        }
        modalTitleText.textContent = mode === 'edit' ? '編輯卡片' : '新增卡片';
        cardModalBackdrop.classList.add('show');
        setTimeout(() => titleInput.focus(), 10);
    }

    function closeCardModal() {
        cardModalBackdrop.classList.remove('show');
    }

    cardModalBackdrop.addEventListener('click', (e) => {
        if (e.target === cardModalBackdrop) closeCardModal();
    });
    modalCloseBtn.addEventListener('click', closeCardModal);
    modalCancelBtn.addEventListener('click', closeCardModal);

    /* --------- Settings Modal ---------- */

    function openSettingsModal() {
        loadBoardSettings().then(() => {
            settingsBackdrop.classList.add('show');
        });
    }
    function closeSettingsModal() {
        settingsBackdrop.classList.remove('show');
    }

    settingsBtn.addEventListener('click', openSettingsModal);
    settingsCloseBtn.addEventListener('click', closeSettingsModal);
    settingsBackdrop.addEventListener('click', (e) => {
        if (e.target === settingsBackdrop) closeSettingsModal();
    });

    function loadBoardSettings() {
        const fd = new FormData();
        fd.append('action', 'get_board_settings');
        fd.append('board_id', boardId);

        return fetch(location.href, {
            method: 'POST',
            body: fd
        })
            .then(r => r.json())
            .then(res => {
                if (!res.ok) throw new Error(res.error || '讀取設定失敗');
                currentSettings.board     = res.board;
                currentSettings.types     = res.types || [];
                currentSettings.templates = res.templates || [];

                if (currentSettings.board) {
                    boardNameInput.value = currentSettings.board.name;
                    boardTitleText.textContent = currentSettings.board.name;
                }

                renderCardTypes();
                renderTypeSelect();
                renderTemplatesList();
                renderTemplateSelect();
                applyCardDotColors();   // 依 Card Type 顏色更新每張卡片上的點
            })
            .catch(err => {
                console.error(err);
                showToast('讀取設定失敗');
            });
    }

    /* --------- Render helpers ---------- */

    function renderCardTypes() {
        if (!cardTypesListEl) return;
        cardTypesListEl.innerHTML = '';
        if (!currentSettings.types.length) {
            cardTypesListEl.textContent = '尚未建立任何 Card Type';
            return;
        }
        currentSettings.types.forEach(t => {
            const row = document.createElement('div');
            row.style.display = 'flex';
            row.style.alignItems = 'center';
            row.style.justifyContent = 'space-between';
            row.style.marginBottom = '4px';

            const left = document.createElement('div');
            left.style.display = 'flex';
            left.style.alignItems = 'center';
            left.style.gap = '6px';

            const dot = document.createElement('span');
            dot.style.width = '10px';
            dot.style.height = '10px';
            dot.style.borderRadius = '50%';
            dot.style.background = t.color;
            left.appendChild(dot);

            const name = document.createElement('span');
            name.textContent = t.name;
            name.style.fontSize = '13px';
            left.appendChild(name);

            const delBtn = document.createElement('button');
            delBtn.textContent = '刪除';
            delBtn.className = 'btn btn-secondary';
            delBtn.style.fontSize = '11px';
            delBtn.addEventListener('click', () => deleteCardType(t.id));

            row.appendChild(left);
            row.appendChild(delBtn);
            cardTypesListEl.appendChild(row);
        });
    }

    function renderTypeSelect() {
        if (!typeSelect) return;
        typeSelect.innerHTML = '';
        const optEmpty = document.createElement('option');
        optEmpty.value = '';
        optEmpty.textContent = '未分類';
        typeSelect.appendChild(optEmpty);

        currentSettings.types.forEach(t => {
            const opt = document.createElement('option');
            opt.value = t.id;
            opt.textContent = t.name;
            typeSelect.appendChild(opt);
        });
    }

    function renderTemplatesList() {
        if (!templatesListEl) return;
        templatesListEl.innerHTML = '';
        if (!currentSettings.templates.length) {
            templatesListEl.textContent = '尚未建立任何 To-do 模板';
            return;
        }
        currentSettings.templates.forEach(t => {
            const row = document.createElement('div');
            row.style.display = 'flex';
            row.style.alignItems = 'center';
            row.style.justifyContent = 'space-between';
            row.style.marginBottom = '4px';

            const name = document.createElement('span');
            name.textContent = t.name;
            name.style.fontSize = '13px';

            const delBtn = document.createElement('button');
            delBtn.textContent = '刪除';
            delBtn.className = 'btn btn-secondary';
            delBtn.style.fontSize = '11px';
            delBtn.addEventListener('click', () => deleteTemplate(t.id));

            row.appendChild(name);
            row.appendChild(delBtn);
            templatesListEl.appendChild(row);
        });
    }

    function renderTemplateSelect() {
        if (!templateSelect) return;
        templateSelect.innerHTML = '';
        const optEmpty = document.createElement('option');
        optEmpty.value = '';
        optEmpty.textContent = '選擇模板…';
        templateSelect.appendChild(optEmpty);

        currentSettings.templates.forEach(t => {
            const opt = document.createElement('option');
            opt.value = t.id;
            opt.textContent = t.name;
            templateSelect.appendChild(opt);
        });
    }

    // 根據 Card Type 顏色設定卡片上的點
    function applyCardDotColors() {
        const typeColorMap = {};
        (currentSettings.types || []).forEach(t => {
            typeColorMap[String(t.id)] = t.color || '#d1d5db';
        });

        document.querySelectorAll('.card').forEach(card => {
            const typeId = card.dataset.typeId;
            const dot = card.querySelector('.card-dot');
            if (!dot) return;
            const color = typeId && typeColorMap[typeId] ? typeColorMap[typeId] : '#d1d5db';
            dot.style.backgroundColor = color;
        });
    }

    /* --------- Settings handlers ---------- */

    addTypeBtn.addEventListener('click', () => {
        const name  = newTypeNameInput.value.trim();
        const color = newTypeColorInput.value || '#D1D5DB';
        if (!name) return;

        const fd = new FormData();
        fd.append('action', 'save_card_type');
        fd.append('board_id', boardId);
        fd.append('name', name);
        fd.append('color', color);

        fetch(location.href, {
            method: 'POST',
            body: fd
        }).then(r => r.json())
            .then(res => {
                if (!res.ok) throw new Error(res.error || '新增失敗');
                newTypeNameInput.value = '';
                loadBoardSettings();
            })
            .catch(err => {
                console.error(err);
                showToast('新增 Card Type 失敗');
            });
    });

    function deleteCardType(id) {
        if (!confirm('確定要刪除此 Card Type？')) return;
        const fd = new FormData();
        fd.append('action', 'delete_card_type');
        fd.append('id', id);

        fetch(location.href, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (!res.ok) throw new Error(res.error || '刪除失敗');
                loadBoardSettings();
            })
            .catch(err => {
                console.error(err);
                showToast('刪除 Card Type 失敗');
            });
    }

    boardNameInput.addEventListener('blur', () => {
        const name = boardNameInput.value.trim();
        if (!name || !currentSettings.board || name === currentSettings.board.name) return;

        const fd = new FormData();
        fd.append('action', 'save_board_name');
        fd.append('board_id', boardId);
        fd.append('name', name);

        fetch(location.href, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (!res.ok) throw new Error(res.error || '儲存失敗');
                currentSettings.board.name = name;
                boardTitleText.textContent = name;
                showToast('已更新看板名稱');
            })
            .catch(err => {
                console.error(err);
                showToast('更新看板名稱失敗');
            });
    });

    tplClearBtn.addEventListener('click', () => {
        tplNameInput.value  = '';
        tplItemsInput.value = '';
    });

    tplSaveBtn.addEventListener('click', () => {
        const name  = tplNameInput.value.trim();
        const items = tplItemsInput.value.trim();
        if (!name || !items) return;

        const fd = new FormData();
        fd.append('action', 'save_todo_template');
        fd.append('board_id', boardId);
        fd.append('name', name);
        fd.append('items', items);

        fetch(location.href, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (!res.ok) throw new Error(res.error || '儲存失敗');
                tplNameInput.value  = '';
                tplItemsInput.value = '';
                loadBoardSettings();
            })
            .catch(err => {
                console.error(err);
                showToast('儲存模板失敗');
            });
    });

    function deleteTemplate(id) {
        if (!confirm('確定要刪除此模板？')) return;
        const fd = new FormData();
        fd.append('action', 'delete_todo_template');
        fd.append('id', id);

        fetch(location.href, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (!res.ok) throw new Error(res.error || '刪除失敗');
                loadBoardSettings();
            })
            .catch(err => {
                console.error(err);
                showToast('刪除模板失敗');
            });
    }

    /* --------- Drag & drop cards ---------- */

    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.addEventListener('dragstart', e => {
            isDragging = true;
            card.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        });
        card.addEventListener('dragend', () => {
            card.classList.remove('dragging');
            setTimeout(() => { isDragging = false; }, 0);
            document.querySelectorAll('.column').forEach(col => col.classList.remove('drag-over'));
        });
    });

    document.querySelectorAll('.cards').forEach(container => {
        const columnEl = container.closest('.column');

        container.addEventListener('dragover', e => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            columnEl.classList.add('drag-over');

            const afterElement = getDragAfterElement(container, e.clientY);
            const dragging = document.querySelector('.card.dragging');
            if (!dragging) return;

            if (afterElement == null) {
                container.appendChild(dragging);
            } else {
                container.insertBefore(dragging, afterElement);
            }
        });

        container.addEventListener('dragleave', () => {
            columnEl.classList.remove('drag-over');
        });

        container.addEventListener('drop', e => {
            e.preventDefault();
            columnEl.classList.remove('drag-over');
            const dragging = document.querySelector('.card.dragging');
            if (!dragging) return;

            const toColumnId = container.dataset.columnId;
            const cardIds = Array.from(container.querySelectorAll('.card'))
                .map(c => c.dataset.cardId);

            const cardId = dragging.dataset.cardId;
            const fromColumnId = dragging.dataset.columnId;
            dragging.dataset.columnId = toColumnId;

            const isSameColumn = (toColumnId === fromColumnId);
            const action = isSameColumn ? 'reorder_cards' : 'move_card';

            const fd = new FormData();
            fd.append('action', action);

            if (isSameColumn) {
                fd.append('column_id', toColumnId);
                cardIds.forEach(id => fd.append('cards[]', id));
            } else {
                fd.append('card_id', cardId);
                fd.append('to_column_id', toColumnId);
                cardIds.forEach(id => fd.append('cards[]', id));
            }

            fetch(location.href, {
                method: 'POST',
                body: fd
            }).then(r => r.json())
                .then(res => {
                    if (!res.ok) throw new Error(res.error || '更新失敗');
                    showToast('已更新排序');
                })
                .catch(err => {
                    console.error(err);
                    showToast('更新失敗');
                });
        });
    });

    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.card:not(.dragging)')];

        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return {offset, element: child};
            } else {
                return closest;
            }
        }, {offset: Number.NEGATIVE_INFINITY, element: null}).element;
    }

    /* --------- Card click / open modal ---------- */

    document.querySelectorAll('.card').forEach(card => {
        card.addEventListener('click', () => {
            if (isDragging) return;
            const cardId   = card.dataset.cardId;
            const columnId = card.dataset.columnId;
            const columnName = columnNameMap[columnId] || '';

            const fd = new FormData();
            fd.append('action', 'get_card');
            fd.append('card_id', cardId);

            fetch(location.href, {
                method: 'POST',
                body: fd
            }).then(r => r.json())
                .then(res => {
                    if (!res.ok) throw new Error(res.error || '讀取失敗');
                    const c = res.card;

                    // 確保 settings 已載入（for type / templates / dot color）
                    loadBoardSettings().then(() => {
                        openCardModal('edit', {
                            cardId: c.id,
                            columnId: c.column_id,
                            columnName: columnNameMap[c.column_id] || columnName,
                            title: c.title,
                            description: c.description || '',
                            typeId: c.type_id || ''
                        });
                    });
                })
                .catch(err => {
                    console.error(err);
                    showToast('讀取失敗');
                });
        });
    });

    // 欄位 "+" 新增卡片
    document.querySelectorAll('[data-add-column-id]').forEach(btn => {
        btn.addEventListener('click', () => {
            const colId = btn.dataset.addColumnId;
            const colName = columnNameMap[colId] || '';
            openCardModal('create', {
                cardId: null,
                columnId: colId,
                columnName: colName,
                title: '',
                description: '',
                typeId: ''
            });
        });
    });

    /* --------- Card form submit ---------- */

    cardForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const cardId   = cardIdInput.value.trim();
        const columnId = columnSelect.value;
        const typeId   = typeSelect.value || '';
        const title    = titleInput.value.trim();
        const desc     = descInput.value.trim();
        if (!title) return;

        const fd = new FormData();
        fd.append('column_id', columnId);
        if (cardId) {
            fd.append('action', 'update_card');
            fd.append('card_id', cardId);
        } else {
            fd.append('action', 'add_card');
        }
        fd.append('title', title);
        fd.append('description', desc);
        fd.append('type_id', typeId);

        fetch(location.href, {
            method: 'POST',
            body: fd
        }).then(r => r.json())
            .then(res => {
                if (!res.ok) throw new Error(res.error || '儲存失敗');
                location.reload();
            })
            .catch(err => {
                console.error(err);
                showToast('儲存失敗');
            });
    });

    /* --------- Apply To-do template ---------- */

    if (templateApplyBtn) {
        templateApplyBtn.addEventListener('click', () => {
            const tplId = templateSelect.value;
            const cardId = cardIdInput.value.trim();
            if (!tplId) return;

            // 新卡片：前端直接插入 Markdown
            if (!cardId) {
                const tpl = (currentSettings.templates || []).find(t => String(t.id) === String(tplId));
                if (!tpl) return;
                let items = [];
                try {
                    items = JSON.parse(tpl.items || '[]');
                } catch (e) {}
                if (!items.length) return;
                let md = '\n\n';
                items.forEach(i => { md += '- [ ] ' + i + '\n'; });
                descInput.value = (descInput.value || '') + md;
                descInput.focus();
                return;
            }

            // 已存在卡片：交給後端更新 description
            const fd = new FormData();
            fd.append('action', 'apply_todo_template');
            fd.append('card_id', cardId);
            fd.append('template_id', tplId);

            fetch(location.href, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (!res.ok) throw new Error(res.error || '套用失敗');
                    location.reload();
                })
                .catch(err => {
                    console.error(err);
                    showToast('套用模板失敗');
                });
        });
    }

    /* --------- Init: 預先載入設定 & 塗好點的顏色 ---------- */
    loadBoardSettings();
})();
</script>
</body>
</html>
