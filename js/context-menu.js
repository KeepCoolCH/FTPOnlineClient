function toggleFolder(targetId, iconId) {
    const container = document.getElementById(targetId);
    const icon = document.getElementById(iconId);
    if (!container) return;
    
    const isVisible = container.style.display === 'block';
    container.style.display = isVisible ? 'none' : 'block';
    if (icon) icon.textContent = isVisible ? '📁' : '📂';
    
    // AJAX-Nachladung nur beim ersten Öffnen
    if (!container.dataset.loaded || container.dataset.loaded !== "1") {
        const path = icon?.getAttribute('data-path') || '';
        fetch('?action=subfolders&path=' + encodeURIComponent(path))
        .then(res => res.text())
        .then(html => {
            container.innerHTML = html;
            container.dataset.loaded = "1";
            container.style.display = 'block';
            if (icon) icon.textContent = '📂';
            
            // Drop-Ziele neu initialisieren
            if (typeof initDropTargets === 'function') {
                initDropTargets();
            }
        })
        .catch(error => {
            console.error("Error loading subfolders:", error);
            container.dataset.loaded = "error";
        });
    }
}

function navigateTo(event, path) {
    event.preventDefault();
    loadMainContent(path);
    history.pushState(null, '', '?path=' + path);
}

const ctxMenu = document.getElementById('ctxMenu');
const ctxMenuBar = document.getElementById('ctxMenuBar');
toggleContextMenuBarDisplay();
const ctxPath = document.getElementById('ctxPath');
const ctxNew = document.getElementById('ctxNew');
const ctxAction = document.getElementById('ctxAction');
const ctxZipTarget = document.getElementById('ctxZipTarget');

function clearCtxForm() {
    const ids = [
        'ctxRenameOld', 'ctxRenameNew', 'ctxDelete',
        'ctxUnzipFile', 'ctxCopyFrom', 'ctxCopyTo',
        'ctxMoveFrom', 'ctxMoveTo',
        'ctxDownload', 'ctxDownloadZip'
    ];
    ids.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = ''; // important: clear it!
    });
}

document.querySelectorAll('tr[data-path]').forEach(el => {
    el.addEventListener('contextmenu', e => {
        const path = el.dataset.path;
        
        // 👉 Update selection (right-click → only select this row)
        const row = el.closest('tr');
        if (row && !row.classList.contains('selected')) {
            document.querySelectorAll('tr.selected').forEach(r => r.classList.remove('selected'));
            row.classList.add('selected');
        }
        
        showContextMenu(e, path); 
    });
});

function updateContextButtons(displayStyle, path, parentId) {
    const selected = [...document.querySelectorAll('tr.selected')];
    const selectedCount = selected.length;
    const target = document.querySelector(`tr[data-path="${CSS.escape(path)}"]`);
    const isFile = target?.dataset.type === 'file';
    const isDir  = target?.dataset.type === 'dir';
    const hasDir = selected.some(row => row.dataset.type === 'dir');
    const zipPath = selected[0]?.dataset.path || '';
    const isRoot = path === '/';
    
    const buttons = {
        ctxDownloadSelectedZipBtn: (!isRoot && selectedCount > 1),
        ctxDownloadBtn: (!isRoot && selectedCount === 1 && isFile),
        ctxDownloadZipBtn: (!isRoot && selectedCount === 1 && isDir),
        ctxRenameBtn: (!isRoot && selectedCount === 1),
        ctxEditBtn: (selectedCount === 1 && isFile),
        ctxDeleteBtn: (!isRoot && selectedCount >= 1),
        ctxCopyBtn: (!isRoot && selectedCount >= 1),
        ctxMoveBtn: (!isRoot && selectedCount >= 1),
        ctxZipBtn: (!isRoot && selectedCount >= 1),
        ctxNewFolderBtn: (selectedCount === 1 && isDir),
        ctxUnzipBtn: (!isRoot && selectedCount === 1 && zipPath.toLowerCase().endsWith('.zip') && !isDir)
    };
    
    const parent = document.getElementById(parentId);
    if (!parent) return;
    
    Object.entries(buttons).forEach(([id, shouldShow]) => {
        const el = parent.querySelector(`#${id}`);
        if (el) el.style.display = shouldShow ? displayStyle : 'none';
    });
}

function showContextMenuBar(path) {
    const ctxMenuBar = document.getElementById('ctxMenuBar');
    ctxPath.value = path;
    
    updateContextButtons('inline-block', path, 'ctxFormBar');
    ctxMenuBar.style.display = 'inline-block';
}

function toggleContextMenuBarDisplay() {
    const selected = document.querySelectorAll('tr.selected');
    const menuBar = document.getElementById('ctxMenuBar');
    menuBar.style.display = selected.length > 0 ? 'inline-block' : 'none';
}

document.addEventListener('click', (e) => {
    const isClickInside = e.target.closest('tr') || e.target.closest('#ctxMenuBar');
    if (!isClickInside) {
        document.querySelectorAll('tr.selected').forEach(r => r.classList.remove('selected'));
        toggleContextMenuBarDisplay();
    }
});

function showContextMenu(e, path) {
    e.preventDefault();
    e.stopPropagation();
    
    const ctxMenu = document.getElementById('ctxMenu');
    ctxPath.value = path;
    
    updateContextButtons('block', path, 'ctxMenu');
    
    ctxMenu.style.display = 'block';
    ctxMenu.style.left = e.pageX + 'px';
    ctxMenu.style.top = e.pageY + 'px';
}

document.addEventListener('click', () => ctxMenu.style.display = 'none');