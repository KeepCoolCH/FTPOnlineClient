function showSpinner() {
    document.getElementById('loadingOverlay').style.display = 'flex';
}

function hideSpinner() {
    document.getElementById('loadingOverlay').style.display = 'none';
}

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

async function triggerRename() {
    const oldPath = ctxPath.value;
    const oldName = oldPath.split('/').pop();
    const newName = prompt('New name:', oldName);
    if (!newName) return;
    
    if (newName === oldName) {
        hideSpinner();
        alert("❗ The new name is the same as the current one. Please enter a different name.");
        return;
    }
    
    const dir = oldPath.substring(0, oldPath.lastIndexOf('/')) || '/';
    const newPath = dir.replace(/\/+$/, '') + '/' + newName;
    
    const res = await fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
            check_exists: newPath
        })
    });
    
    const data = await res.json();
    
    if (data.exists) {
        pendingConflict = {
            from: oldPath,
            toDir: dir,
            suggested: data.suggested,
            mode: 'rename',
            userInput: newName
        };
        hideSpinner();
        document.getElementById('conflictModal').style.display = 'flex';
    } else {
        hideSpinner();
        document.getElementById('ctxRenameOld').value = oldPath;
        document.getElementById('ctxRenameNew').value = newName;
        document.getElementById('ctxForm').submit();
    }
}

function initModalFolderClicks() {
    const nodes = document.querySelectorAll('#modalFolderTree .folder-row');
    nodes.forEach(row => {
        row.addEventListener('click', () => {
            const path = row.dataset.path;
            document.getElementById('modalTarget').value = path;
            
            // Optional: visuelle Markierung setzen
            document.querySelectorAll('#modalFolderTree .folder-row').forEach(r => r.style.background = '');
            row.style.background = '#e6f0fa';
        });
    });
}

function triggerDelete() {
    const selected = [...document.querySelectorAll('tr.selected')].map(row => row.dataset.path);
    
    if (selected.length === 0) {
        selected.push(ctxPath.value); // fallback: only right-clicked item
    }
    
    if (!confirm(`Really delete ${selected.length} file(s)/folder(s)?`)) return;
    
    const formData = new URLSearchParams();
    formData.append('delete_bulk', JSON.stringify(selected));
    
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: formData
    })
    .then(() => {
        location.reload();
    })
    .catch(() => {
        hideSpinner();
        showErrorModal("❌ Error during deletion.");
    });
}

function triggerCopy() {
    const selectedPaths = [...document.querySelectorAll('tr.selected')].map(row => row.dataset.path);
    if (!selectedPaths.length) {
        alert("❗ Please select at least one file or folder.");
        return;
    }
    
    window.bulkCopySelection = selectedPaths;
    
    fetch('?action=subfolders&path=/&current_path=' + encodeURIComponent(window.currentPath))
    .then(res => res.text())
    .then(html => {
        const container = document.getElementById('copyTargetTree');
        container.innerHTML = html;
        
        // 🔒 Deactivate links
        container.querySelectorAll('a').forEach(a => {
            a.removeAttribute('href');
            a.style.cursor = 'default';
            a.addEventListener('click', e => e.preventDefault());
        });
        
        // Default selection
        let selectedTarget = '/';
        
        // Delegated click handler for all folder rows (including dynamically loaded)
        container.addEventListener('click', e => {
            const row = e.target.closest('.folder-row');
            if (row) {
                
                container.querySelectorAll('.folder-row').forEach(r => {
                    r.classList.remove('selected-folder');
                    r.style.fontWeight = '';
                });
                
                row.classList.add('selected-folder');
                row.style.fontWeight = 'bold';
                
                selectedTarget = row.dataset.path;
            }
        });
        //Modal Footer-Buttons
        const existingFooter = container.parentElement.querySelector('.modal-footer');
        if (existingFooter) existingFooter.remove();
        
        const footerDiv = document.createElement('div');
        footerDiv.className = 'modal-footer';
        footerDiv.style = 'text-align:right; margin-top:10px;';
        footerDiv.innerHTML = `
        <button onclick="document.getElementById('copySidebarModal').style.display='none'">Cancel</button>
        <button id="confirmCopyBtn" style="margin-left:10px;">OK</button>
      `;
        container.parentElement.appendChild(footerDiv);
        
        document.getElementById('confirmCopyBtn').addEventListener('click', () => {
            confirmCopyTo(selectedTarget);
        });
        
        document.getElementById('copySidebarModal').style.display = 'flex';
    })
    .catch(err => {
        console.error("❌ Failed to load folder tree:", err);
        showErrorModal("❌ Could not load folder tree.");
    });
}

async function confirmCopyTo(toDir) {
    const selected = window.bulkCopySelection;
    if (!selected || !Array.isArray(selected)) return;
    
    showSpinner();
    const conflicts = [];
    
    for (const fromPath of selected) {
        const name = fromPath.split('/').pop();
        const toPath = toDir.replace(/\/+$/, '') + '/' + name;
        
        // Check conflicts
        const checkRes = await fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                check_exists: toPath
            })
        });
        const checkData = await checkRes.json();
        
        if (checkData.exists) {
            conflicts.push({
                from: fromPath, suggested: checkData.suggested
            });
            continue;
        }
        
        // Copy ausführen
        const res = await fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                copy_from: fromPath, copy_to: toPath, ajax: '1'
            })
        });
        
        const data = await res.json();
        if (!data.success) {
            hideSpinner();
            showErrorModal(`❌ Cannot copy ${fromPath}`);
            return;
        }
    }
    
    if (conflicts.length > 0) {
        const fromList = conflicts.map(c => c.from);
        const choices = {
        };
        conflicts.forEach(c => choices[c.from] = {
            newName: c.suggested
        });
        
        pendingConflict = {
            from: fromList[0],
            toDir: toDir,
            suggested: conflicts[0].suggested,
            mode: 'copy',
            userInput: fromList[0].split('/').pop(),
            fromList,
            choices
        };
        
        hideSpinner();
        document.getElementById('conflictModal').style.display = 'flex';
        return;
    }
    
    hideSpinner();
    window.location.href = '?path=' + encodeURIComponent(toDir);
}

function triggerMove() {
    const selectedPaths = [...document.querySelectorAll('tr.selected')].map(row => row.dataset.path);
    if (!selectedPaths.length) {
        alert("❗ Please select at least one file or folder.");
        return;
    }
    
    window.bulkMoveSelection = selectedPaths;
    
    fetch('?action=subfolders&path=/&current_path=' + encodeURIComponent(window.currentPath))
    .then(res => res.text())
    .then(html => {
        const container = document.getElementById('moveTargetTree');
        container.innerHTML = html;
        
        // 🔒 Deactivate links
        container.querySelectorAll('a').forEach(a => {
            a.removeAttribute('href');
            a.style.cursor = 'default';
            a.addEventListener('click', e => e.preventDefault());
        });
        
        // Default selection
        let selectedTarget = '/';
        
        // Delegated click handler for all folder rows (including dynamically loaded)
        container.addEventListener('click', e => {
            const row = e.target.closest('.folder-row');
            if (row) {
                
                container.querySelectorAll('.folder-row').forEach(r => {
                    r.classList.remove('selected-folder');
                    r.style.fontWeight = '';
                });
                
                row.classList.add('selected-folder');
                row.style.fontWeight = 'bold';
                
                selectedTarget = row.dataset.path;
            }
        });
        
        const existingFooter = container.parentElement.querySelector('.modal-footer');
        if (existingFooter) existingFooter.remove();
        
        //Modal Footer-Buttons
        const footerDiv = document.createElement('div');
        footerDiv.className = 'modal-footer';
        footerDiv.style = 'text-align:right; margin-top:10px;';
        footerDiv.innerHTML = `
        <button onclick="document.getElementById('moveSidebarModal').style.display='none'">Cancel</button>
        <button id="confirmMoveBtn" style="margin-left:10px;">OK</button>
      `;
        container.parentElement.appendChild(footerDiv);
        
        document.getElementById('confirmMoveBtn').addEventListener('click', () => {
            confirmMoveTo(selectedTarget);
        });
        
        document.getElementById('moveSidebarModal').style.display = 'flex';
    })
    .catch(err => {
        console.error("❌ Failed to load folder tree:", err);
        showErrorModal("❌ Could not load folder tree.");
    });
}

async function confirmMoveTo(toDir) {
    const fromList = window.bulkMoveSelection || [];
    
    showSpinner();
    const conflicts = [];
    const toMove = [];
    
    for (const fromPath of fromList) {
        const name = fromPath.split('/').pop();
        const toPath = toDir.replace(/\/+$/, '') + '/' + name;
        
        if (toPath === fromPath || toPath.startsWith(fromPath + '/')) {
            hideSpinner();
            showErrorModal(`❌ Cannot move '${fromPath}' into itself or a subfolder.`);
            return;
        }
        
        const checkRes = await fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                check_exists: toPath
            })
        });
        
        const checkData = await checkRes.json();
        
        if (checkData.exists) {
            conflicts.push({
                from: fromPath, suggested: checkData.suggested
            });
        } else {
            toMove.push({
                from: fromPath, to: toPath
            });
        }
    }
    
    for (const item of toMove) {
        const res = await fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                move_from: item.from,
                move_to: item.to,
                ajax: '1'
            })
        });
        
        const data = await res.json();
        if (!data.success) {
            hideSpinner();
            showErrorModal(`❌ Cannot move ${item.from}`);
            return;
        }
    }
    
    if (conflicts.length > 0) {
        const fromList = conflicts.map(c => c.from);
        const choices = {
        };
        conflicts.forEach(c => {
            choices[c.from] = {
                newName: c.suggested
            };
        });
        
        pendingConflict = {
            from: fromList[0],
            toDir: toDir,
            suggested: conflicts[0].suggested,
            mode: 'move',
            userInput: fromList[0].split('/').pop(),
            fromList,
            choices
        };
        
        hideSpinner();
        document.getElementById('conflictModal').style.display = 'flex';
        return;
    }
    
    hideSpinner();
    window.location.href = '?path=' + encodeURIComponent(toDir);
}

async function confirmMoveToFromDrop(fromPaths, toDir) {
    if (!Array.isArray(fromPaths) || !fromPaths.length || !toDir) {
        showErrorModal("❌ Nothing selected or invalid target directory.");
        return;
    }
    
    showSpinner();
    const conflicts = [];
    
    for (const fromPath of fromPaths) {
        const name = fromPath.split('/').pop();
        const toPath = toDir.replace(/\/+$/, '') + '/' + name;
        
        if (toPath === fromPath || toPath.startsWith(fromPath + '/')) {
            hideSpinner();
            showErrorModal("❌ Cannot move into same folder or subfolder.");
            return;
        }
        
        // Check for conflicts
        const checkRes = await fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                check_exists: toPath
            })
        });
        const checkData = await checkRes.json();
        
        if (checkData.exists) {
            conflicts.push({
                from: fromPath, suggested: checkData.suggested
            });
            continue;
        }
        
        // Perform move
        const res = await fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                move_from: fromPath,
                move_to: toPath,
                ajax: '1'
            })
        });
        
        const data = await res.json();
        if (!data.success) {
            hideSpinner();
            showErrorModal(`❌ Cannot move ${fromPath}`);
            return;
        }
    }
    
    if (conflicts.length > 0) {
        const conflictFromList = conflicts.map(c => c.from);
        const choices = {
        };
        conflicts.forEach(c => {
            choices[c.from] = {
                newName: c.suggested
            };
        });
        
        pendingConflict = {
            from: conflictFromList[0],
            toDir: toDir,
            suggested: conflicts[0].suggested,
            mode: 'move',
            userInput: conflictFromList[0].split('/').pop(),
            fromList: conflictFromList,
            choices
        };
        
        hideSpinner();
        document.getElementById('conflictModal').style.display = 'flex';
        return;
    }
    
    hideSpinner();
    window.location.href = '?path=' + encodeURIComponent(toDir);
}



let pendingConflict = {
    from: '',
    toDir: '',
    suggested: ''
};

async function resolveConflict(action) {
    if (!pendingConflict || !Array.isArray(pendingConflict.fromList)) return;
    
    showSpinner();
    
    const toDir = pendingConflict.toDir;
    const mode = pendingConflict.mode;
    
    for (const from of pendingConflict.fromList) {
        const baseName = from.split('/').pop();
        let name = baseName;
        
        if (action === 'rename') {
            const nameParts = baseName.split('.');
            const base = nameParts.slice(0, -1).join('.') || nameParts[0];
            const ext = nameParts.length > 1 ? '.' + nameParts[nameParts.length - 1] : '';
            let counter = 1;
            let testName = `${base}_${counter}${ext}`;
            let exists = true;
            
            while (exists) {
                const checkPath = toDir.replace(/\/+$/, '') + '/' + testName;
                try {
                    const check = await fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            ajax: '1',
                            check_exists: checkPath
                        })
                    });
                    const result = await check.json();
                    exists = result.exists;
                    if (exists) {
                        counter++;
                        testName = `${base}_${counter}${ext}`;
                    }
                } catch {
                    exists = false;
                }
            }
            
            name = testName;
        }
        
        const to = toDir.replace(/\/+$/, '') + '/' + name;
        
        if (from === to || (mode !== 'rename' && to.startsWith(from + '/'))) {
            console.warn(`⚠️ Invalid move/copy: ${from} → ${to}`);
            continue;
        }
        
        const formData = new URLSearchParams();
        if (mode === 'copy') {
            formData.append('copy_from', from);
            formData.append('copy_to', to);
        } else if (mode === 'move') {
            formData.append('move_from', from);
            formData.append('move_to', to);
        } else if (mode === 'rename') {
            formData.append('rename_old', from);
            formData.append('rename_new', to);
        }
        formData.append('ajax', '1');
        
        try {
            const res = await fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: formData
            });
            const data = await res.json();
            if (!data.success) {
                showErrorModal(data.error || `❌ Failed: ${from}`);
                break;
            }
        } catch (e) {
            showErrorModal('❌ Server response is not valid JSON.');
            break;
        }
    }
    
    hideSpinner();
    pendingConflict = null;
    document.getElementById('conflictModal').style.display = 'none';
    
    window.location.href = '?path=' + encodeURIComponent(toDir);
}

function showRenameInput() {
    document.getElementById('conflictModal').style.display = 'none';
    resolveConflict('rename');
}

async function triggerZip() {
    const selected = [...document.querySelectorAll('tr.selected')].map(row => row.dataset.path);
    if (selected.length === 0) {
        alert("❗ Please select at least one file or folder.");
        return;
    }
    
    if (!confirm('Create ZIP archive from selection?')) return;
    showSpinner();
    
    const res = await fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
            zip_bulk: JSON.stringify(selected),
            ajax: 1
        })
    });
    
    try {
		const text = await res.text();
		console.log('🔍 RAW response:', text);
	
		const data = JSON.parse(text);
		if (data.success) {
			window.location.href = '?path=' + encodeURIComponent(data.path || window.currentPath);
		} else {
			hideSpinner();
			showErrorModal(data.error || '❌ ZIP creation failed.');
		}
	} catch (e) {
		hideSpinner();
		showErrorModal('❌ Server response is not valid JSON.');
	}
}

async function triggerUnzip() {
    try {
        showSpinner();
        const res = await fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                unzip_file: ctxPath.value, ajax: 1
            })
        });
        
        const raw = await res.text();
        console.log("📦 RAW UNZIP RESPONSE:\n", raw);
        
        if (!raw.trim().startsWith('{')) {
            throw new Error('Response is not JSON (maybe session timeout or error page?)');
        }
        
        const data = JSON.parse(raw);
        
        if (data.success) {
            window.location.href = '?path=' + encodeURIComponent(data.path || currentPath);
        } else {
            hideSpinner();
            showErrorModal(data.error || '❌ Unzipping failed.');
        }
    } catch (e) {
        hideSpinner();
        showErrorModal('❌ Server response is not valid JSON: ' + e.message);
    }
}

function triggerCreateFolderBar() {
    const targetPath = document.getElementById('ctxNewFolderPath').value;
    const folderName = prompt("New folder in directory " + targetPath);
    if (!folderName) return;
    document.getElementById('ctxNewFolderInput').value = folderName.trim();
    document.getElementById('ctxFormBarParent').submit();
}

function triggerCreateFolder() {
    const fullPath = ctxPath.value;
    const row = document.querySelector(`[data-path="${CSS.escape(fullPath)}"]`);
    if (!row) return;
    
    const type = row.dataset.type;
    const folderName = prompt("New folder in directory " + fullPath);
    if (!folderName) return;
    
    let folderPath = fullPath;
    if (type === 'file') {
        folderPath = fullPath.substring(0, fullPath.lastIndexOf('/')) || '/';
    }
    
    const form = document.createElement("form");
    form.method = "POST";
    form.action = "";
    
    const inputName = document.createElement("input");
    inputName.type = "hidden";
    inputName.name = "new_folder";
    inputName.value = folderName;
    
    const inputPath = document.createElement("input");
    inputPath.type = "hidden";
    inputPath.name = "new_folder_path";
    inputPath.value = folderPath;
    
    form.appendChild(inputName);
    form.appendChild(inputPath);
    document.body.appendChild(form);
    form.submit();
}

function triggerDownload() {
    const selected = [...document.querySelectorAll('tr.selected')].map(row => row.dataset.path);
    if (selected.length === 0) {
        alert("❗ Please select a file to download.");
        return;
    }
    
    if (selected.length > 1) {
        alert("❗ Please select only one file.");
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'GET';
    form.action = '';
    form.style.display = 'none';
    
    const input = document.createElement('input');
    input.name = 'download';
    input.value = selected[0]; // Nur ein Pfad
    form.appendChild(input);
    
    document.body.appendChild(form);
    form.submit();
}

function triggerDownloadZip() {
    const selected = [...document.querySelectorAll('tr.selected')].map(row => row.dataset.path);
    if (selected.length !== 1) {
        alert("❗ Please select exactly one folder to download as ZIP.");
        return;
    }
    
    const path = selected[0];
    const el = document.querySelector(`tr[data-path="${CSS.escape(path)}"]`);
    if (!el || el.dataset.type !== 'dir') {
        alert("❗ Selected item is not a folder.");
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'GET';
    form.action = '';
    form.style.display = 'none';
    
    const input = document.createElement('input');
    input.name = 'download_zip';
    input.value = path;
    form.appendChild(input);
    
    document.body.appendChild(form);
    form.submit();
}

function triggerDownloadSelectedZip() {
    const selected = [...document.querySelectorAll('tr.selected')].map(row => row.dataset.path);
    if (selected.length === 0) {
        hideSpinner();
        alert("❗ Please select at least one file or folder.");
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '';
    form.style.display = 'none';
    
    const input = document.createElement('input');
    input.name = 'download_bulk';
    input.value = JSON.stringify(selected);
    form.appendChild(input);
    
    document.body.appendChild(form);
    form.submit();
}

function triggerEdit() {
    const selected = document.querySelectorAll('tr.selected');
    if (selected.length !== 1) {
        alert("❗ Please select exactly one file to edit.");
        return;
    }
    
    const path = selected[0].dataset.path;
    const ext = path.split('.').pop().toLowerCase();
    const editable = ['txt', 'html', 'htm', 'css', 'js', 'php', 'md', 'json', 'xml'];
    
    if (!editable.includes(ext)) {
        alert("❌ Only text-based files can be edited.");
        return;
    }
    
    const editorModal = document.getElementById('editorModal');
    const editorFilename = document.getElementById('editorFilename');
    const editorContent = document.getElementById('editorContent');
    const editorFile = document.getElementById('editorFile');
    
    showSpinner();
    
    fetch(`?load=${encodeURIComponent(path)}`)
    .then(res => res.text())
    .then(text => {
        editorFilename.textContent = "Edit: " + path.split('/').pop();
        editorContent.value = text;
        editorFile.value = path;
        editorModal.style.display = 'flex';
    })
    .catch(() => {
        alert("❌ Failed to load file.");
    })
    .finally(() => {
        hideSpinner();
    });
}

function submitAction(action) {
    if (action === 'copy') {
        const newName = prompt('Target name:');
        if (!newName) return;
        ctxNew.value = newName;
    }
    ctxAction.value = action;
    
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams(new FormData(document.getElementById('ctxForm')))
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) location.reload();
        else {
            hideSpinner();
            alert('❌ Action failed.');
        }
    });
}

let lastSelectedRow = null;

function toggleSelection(row, event) {
    if (event.button !== 0) return;
    
    ctxMenu.style.display = 'none';
    
    const rows = Array.from(document.querySelectorAll('tr[data-path]'));
    const isShift = event.shiftKey;
    const isCtrlCmd = event.ctrlKey || event.metaKey;
    
    if (isShift && lastSelectedRow) {
        const start = rows.indexOf(lastSelectedRow);
        const end = rows.indexOf(row);
        const [min, max] = [Math.min(start, end), Math.max(start, end)];
        rows.forEach((r, i) => {
            r.classList.toggle('selected', i >= min && i <= max);
        });
    } else if (isCtrlCmd) {
        
        row.classList.toggle('selected');
        lastSelectedRow = row;
    } else {
        
        rows.forEach(r => r.classList.remove('selected'));
        row.classList.add('selected');
        lastSelectedRow = row;
    }
    
    showContextMenuBar(row.dataset.path);
    toggleContextMenuBarDisplay();
    
    event.stopPropagation?.();
}

async function bulkMove() {
    const selectedPaths = [...document.querySelectorAll('tr.selected')]
    .map(row => row.dataset.path);
    
    if (selectedPaths.length === 0) {
        hideSpinner();
        alert("❗ No files or folders selected.");
        return;
    }
    
    const targetDir = prompt("Enter target folder (e.g. /backup):");
    if (!targetDir) return;
    
    for (const fromPath of selectedPaths) {
        const fileName = fromPath.split('/').pop();
        const toPath = targetDir.replace(/\/+$/, '') + '/' + fileName;
        
        const formData = new URLSearchParams();
        formData.append('move_from', fromPath);
        formData.append('move_to', toPath);
        
        const res = await fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData
        });

        const data = await res.json();
        if (!data.success) {
            hideSpinner();
            showErrorModal("❌ Failed to move: " + fromPath);
            return;
        }
    }

    window.location.href = '?path=' + encodeURIComponent(targetDir);
}

function showErrorModal(message) {
    document.getElementById('errorModalMessage').innerText = message;
    document.getElementById('errorModal').style.display = 'flex';
}

const dropArea = document.getElementById('drop-area');
const fileInput = document.getElementById('fileElem');
let dragCounter = 0;

// Highlight drop area on drag
['dragenter', 'dragover'].forEach(eventName => {
    dropArea.addEventListener(eventName, e => {
        e.preventDefault();
        dragCounter++;
        dropArea.classList.add('highlight');
    }, false);
});

// Remove highlight on drop/leave
['dragleave', 'drop'].forEach(eventName => {
    dropArea.addEventListener(eventName, e => {
        e.preventDefault();
        dragCounter--;
        if (dragCounter === 0) {
            dropArea.classList.remove('highlight');
        }
    }, false);
});

// Handle drop event: assign files and submit form
dropArea.addEventListener('drop', e => {
    e.preventDefault();
    const dt = e.dataTransfer;
    const files = dt.files;
    if (files.length > 0) {
        fileInput.files = files;
        fileInput.form.submit(); // 🔁 auto-submit
    }
});

// Lightbox Script
let lightboxImageList = window.lightboxImageList || [];
let currentIndex = 0;

function openLightbox(url) {
    const decodedUrl = decodeURIComponent(url);
    currentIndex = lightboxImageList.findIndex(u => decodeURIComponent(u) === decodedUrl);

    if (currentIndex === -1) {
        console.warn('Image not found in list:', url);
        currentIndex = 0; // Fallback
    }

    const lb = document.getElementById('lightbox');
    const img = document.getElementById('lightboxImg');
    img.src = lightboxImageList[currentIndex];
    lb.style.display = 'flex';
}

function closeLightbox() {
    document.getElementById('lightbox').style.display = 'none';
    document.getElementById('lightboxImg').src = '';
}

function showPrev() {
    currentIndex = (currentIndex - 1 + lightboxImageList.length) % lightboxImageList.length;
    document.getElementById('lightboxImg').src = lightboxImageList[currentIndex];
}

function showNext() {
    currentIndex = (currentIndex + 1) % lightboxImageList.length;
    document.getElementById('lightboxImg').src = lightboxImageList[currentIndex];
}

document.addEventListener('keydown', function(e) {
    if (document.getElementById('lightbox').style.display === 'flex') {
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') showPrev();
        if (e.key === 'ArrowRight') showNext();
    }
});
