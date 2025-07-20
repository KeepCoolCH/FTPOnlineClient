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
