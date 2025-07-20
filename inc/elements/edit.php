<!-- File Edit dialog -->
<div id="editorModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:#000000aa; z-index:9999; justify-content:center; align-items:center;">
  <div style="background:white; padding:20px; width:80%; max-width:1000px; height:50%; max-height:1000px; display:flex; flex-direction:column;">
    <div style="display:flex; justify-content:space-between; align-items:center;">
      <h2 id="editorFilename" style="margin:0; font-size:1.2em;">Edit file</h2>
    </div>
    <form id="editorForm" method="POST" action="" style="flex:1; display:flex; flex-direction:column;">
      <textarea name="content" id="editorContent" style="flex:1; width:100%; font-family:monospace; font-size:14px;"></textarea>
      <input type="hidden" name="file" id="editorFile">
      <div style="text-align:right; margin-top:10px;">
        <button onclick="closeEditor()">Cancel</button>
        <button type="submit">💾 Save</button>
      </div>
    </form>
  </div>
</div>