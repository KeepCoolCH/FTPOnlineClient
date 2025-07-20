<!-- Menubar -->
<br>
<div class="context-menuBarParent" id="ctxMenuBarParent">
<form id="ctxFormBarParent" method="POST" action="">
    <input type="hidden" name="new_folder_path" id="ctxNewFolderPath" value="<?= htmlspecialchars($path, ENT_QUOTES) ?>">
    <input type="hidden" name="new_folder" id="ctxNewFolderInput">
    <button type="button" onclick="triggerCreateFolderBar()" id="ctxNewFolderBtn">📁 Create folder in active directory</button>
      </form>

<div class="context-menuBar" id="ctxMenuBar" >
  <form id="ctxFormBar" method="POST" action="">
    <input type="hidden" id="ctxPath">
    <input type="hidden" name="rename_old" id="ctxRenameOld">
    <input type="hidden" name="rename_new" id="ctxRenameNew">
    <input type="hidden" name="delete" id="ctxDelete">
    <input type="hidden" name="zip_target" id="ctxZipTarget">
    <input type="hidden" name="unzip_file" id="ctxUnzipFile">
    <input type="hidden" name="copy_from" id="ctxCopyFrom">
    <input type="hidden" name="copy_to" id="ctxCopyTo">
    <input type="hidden" name="move_from" id="ctxMoveFrom">
    <input type="hidden" name="move_to" id="ctxMoveTo">
    <select id="copyTarget" style="width: 100%; display:none; margin: 5px 0;"></select>
    <button type="button" onclick="submitCopy()" id="ctxCopyBtnSubmit" style="display:none;">✅ Copy</button>
    <input type="hidden" name="download_zip" id="ctxDownloadZip">
    <input type="hidden" name="download" id="ctxDownload">
    <button type="button" onclick="triggerCreateFolder()" id="ctxNewFolderBtn">📁 Create folder in selected directory</button>
    <button type="button" onclick="triggerDownloadSelectedZip()" id="ctxDownloadSelectedZipBtn">📥 Download selection</button>
    <button type="button" onclick="triggerDownloadZip()" id="ctxDownloadZipBtn">📥 Download folder</button>
    <button type="button" onclick="triggerDownload()" id="ctxDownloadBtn">📥 Download file</button>
    <button type="button" onclick="triggerRename()" id="ctxRenameBtn">✏️ Rename</button>
    <button type="button" onclick="triggerEdit()" id="ctxEditBtn">📝 Edit</button>
    <button type="button" onclick="triggerDelete()" id="ctxDeleteBtn">🗑️ Delete</button>
    <button type="button" onclick="triggerCopy()" id="ctxCopyBtn">📋 Copy</button>
    <button type="button" onclick="triggerMove()" id="ctxMoveBtn">🔀 Move</button>
    <button type="button" onclick="triggerZip()" id="ctxZipBtn">🗜️ Create ZIP</button>
    <button type="button" onclick="triggerUnzip()" id="ctxUnzipBtn">📦 Extract ZIP</button>
  </form>
</div>
</div>