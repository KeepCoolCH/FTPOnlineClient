<!-- 📁 Sidebar -->
<div style="display: flex;">
  <div style="width: 250px; padding: 10px; background: #fff; border-radius: 8px; margin-right: 20px;">
    <h3>Folder Structure</h3>
    <div id="sidebarTree" style="list-style:none; padding-left:0px;">
      <?= build_folder_tree($ftp, '/', 0, $path, PHP_INT_MAX) ?>
    </div>
  </div>