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