<!-- Upload -->
<h3>📄 Upload Files:</h3>
<div id="drop-area">
  <p>Drag & drop files here or click</p>
  <form id="upload-form" method="post" enctype="multipart/form-data">
    <input type="file" name="upload[]" id="fileElem" multiple style="display:none;" onchange="this.form.submit()">
    <button type="button" onclick="document.getElementById('fileElem').click()">Select Files</button>
  </form>
</div>
