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
