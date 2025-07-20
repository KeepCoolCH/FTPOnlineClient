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