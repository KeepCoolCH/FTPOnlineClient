document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.toggle').forEach(toggle => {
        toggle.addEventListener('click', e => {
            e.stopPropagation();
            const targetId = toggle.getAttribute('data-target');
            const target = document.getElementById(targetId);
            if (target) {
                const isVisible = target.style.display === 'inline';
                target.style.display = isVisible ? 'none' : 'inline';
                toggle.textContent = isVisible ? '📂' : '📂';
            }
        });
    });
});

const isMac = navigator.platform.toUpperCase().includes('MAC');
window.altPressed = false;

window.addEventListener('keydown', e => {
    if (e.key === 'Alt') window.altPressed = true;
});
window.addEventListener('keyup', e => {
    if (e.key === 'Alt') window.altPressed = false;
});

function initDraggables() {
    const fileRows     = [...document.querySelectorAll('tr[data-path]')];
    const sidebarItems = [];
    const allDraggables = [...fileRows];

    allDraggables.forEach(el => {
        el.setAttribute('draggable', 'true');

        el.addEventListener('dragstart', e => {
            let filesToMove = [{
                path: el.dataset.path
            }];
            
            if (el.tagName === 'TR' && el.classList.contains('selected')) {
                const selected = [...document.querySelectorAll('tr.selected')];
                if (selected.length > 0) {
                    filesToMove = selected.map(row => ({
                        path: row.dataset.path
                    }));
                }
            }
            
            e.dataTransfer.setData('text/plain', JSON.stringify(filesToMove));
            el.classList.add('dragging');
        });
        
        el.addEventListener('dragend', () => {
            el.classList.remove('dragging');
        });
    });
}

function initDropTargets() {
    document.querySelectorAll('.folder-row[data-path], .subfolders[data-path], tr[data-type="dir"][data-path]').forEach(item => {
        if (item.dataset.dropInitialized) return;
        
        item.addEventListener('dragover', e => {
            e.preventDefault();
            e.stopPropagation();
            item.classList.add('drop-hover');
        });
        
        item.addEventListener('dragleave', () => {
            item.classList.remove('drop-hover');
        });
        
        item.addEventListener('drop', async e => {
            e.preventDefault();
            e.stopPropagation();
            item.classList.remove('drop-hover');
            
            const folderRow = e.target.closest('[data-path][data-type="dir"]');
            if (!folderRow) {
                showErrorModal("❌ Drop target missing or invalid.");
                return;
            }
            
            const toDir = folderRow.dataset.path?.replace(/\/+$/, '') || '/';
            if (!toDir) {
                showErrorModal("❌ Please select a valid folder.");
                return;
            }
            
            let draggedItems;
            try {
                draggedItems = JSON.parse(e.dataTransfer.getData("text/plain"));
            } catch (err) {
                showErrorModal("❌ Dragging within the sidebar is not allowed.");
                return;
            }
            
            const fromPaths = draggedItems.map(item => item.path);
            if (!fromPaths.length) {
                showErrorModal("❌ No items to move or copy.");
                return;
            }
            
            showSpinner();
            
            if (window.altPressed) {
                window.bulkCopySelection = fromPaths;
                confirmCopyTo(toDir);
            } else {
                window.bulkMoveSelection = fromPaths;
                confirmMoveTo(toDir);
            }
        });
        
        item.dataset.dropInitialized = "1";
    });
}

initDraggables();
initDropTargets();
