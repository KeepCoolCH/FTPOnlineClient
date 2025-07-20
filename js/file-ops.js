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