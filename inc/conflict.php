<!-- Conflict Dialog -->
<div id="conflictModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.4); z-index:3000; justify-content:center; align-items:center;">
  <div style="background:#fff; padding:20px; border-radius:8px; min-width:300px;">
    <h3>❗ File or folder already exists</h3>
    <p>What do you want to do?</p>
    <div style="text-align:right;">
      <button onclick="document.getElementById('conflictModal').style.display='none'">Cancel</button>
      <button onclick="resolveConflict('overwrite')">✅ Overwrite</button>
      <button onclick="showRenameInput()">✏️ Rename</button>
    </div>
  </div>
</div>
