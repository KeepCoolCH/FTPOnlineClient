<!-- Lightbox -->
<div id="lightbox" onclick="closeLightbox()">
  <button id="lightboxClose" onclick="event.stopPropagation(); closeLightbox()">✕</button>
  <button class="lightbox-nav" id="lightboxPrev" onclick="event.stopPropagation(); showPrev()">❮</button>
  <img id="lightboxImg" src="" alt="Preview">
  <button class="lightbox-nav" id="lightboxNext" onclick="event.stopPropagation(); showNext()">❯</button>
</div>