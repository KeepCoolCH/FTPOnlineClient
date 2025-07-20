<?php
/* FTP Online Client V.1.2
   Developed by Kevin Tobler
   www.kevintobler.ch
*/

session_start();

define('BASE_PATH', __DIR__);
$localTempDir = __DIR__ . '/inc/.temp';

require_once BASE_PATH . '/inc/functions/session.php';
require_once BASE_PATH . '/inc/functions/helpers.php';
require_once BASE_PATH . '/inc/functions/wrappers.php';
require_once BASE_PATH . '/inc/functions/foldertree.php';
require_once BASE_PATH . '/inc/functions/file-ops.php';
require_once BASE_PATH . '/inc/functions/zip.php';
require_once BASE_PATH . '/inc/functions/download.php';
require_once BASE_PATH . '/inc/functions/fileedit.php';

require_once BASE_PATH . '/inc/elements/spinner.php';
require_once BASE_PATH . '/inc/elements/contextmenu.php';
require_once BASE_PATH . '/inc/elements/move.php';
require_once BASE_PATH . '/inc/elements/copy.php';
require_once BASE_PATH . '/inc/elements/conflict.php';
require_once BASE_PATH . '/inc/elements/error.php';
require_once BASE_PATH . '/inc/elements/edit.php';
require_once BASE_PATH . '/inc/elements/sidebar.php';
require_once BASE_PATH . '/inc/elements/lightbox.php';
require_once BASE_PATH . '/inc/elements/header_logout.php';
require_once BASE_PATH . '/inc/elements/browser.php';
require_once BASE_PATH . '/inc/elements/menubar.php';
require_once BASE_PATH . '/inc/elements/upload.php';
require_once BASE_PATH . '/inc/elements/footer.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>FTP Manager</title>
<link rel="stylesheet" href="inc/style.css">
</head>
<body>
<script>
window.currentPath = <?= json_encode($path) ?>;
</script>
<script src="js/spinner.js"></script>
<script src="js/draggable.js"></script>
<script src="js/context-menu.js"></script>
<script src="js/conflict-handler.js"></script>
<script src="js/file-ops.js"></script>
<script src="js/zip.js"></script>
<script src="js/editor.js"></script>
<script src="js/lightbox.js"></script>
<script src="js/drop-upload.js"></script>
</body>
</html>
