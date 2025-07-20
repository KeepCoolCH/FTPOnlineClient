<!-- Custom Error Popup -->
<div id="errorModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.4); z-index:4000; justify-content:center; align-items:center;">
  <div style="background:#fff; padding:20px; border-radius:8px; min-width:300px; border-left: 6px solid #e74c3c;">
    <h3 style="color:#e74c3c; margin-top:0;">❌ Error</h3>
    <p id="errorModalMessage">An error has occurred.</p>
    <div style="text-align:right;">
      <button onclick="document.getElementById('errorModal').style.display='none'" style="background:#e74c3c;">Close</button>
    </div>
  </div>
</div>