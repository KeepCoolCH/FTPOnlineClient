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