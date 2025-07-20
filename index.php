<?php
/* FTP Online Client V.1.1
   Developed by Kevin Tobler
   www.kevintobler.ch
*/

session_start();

require_once 'inc/functions.php';
require_once 'inc/spinner.php';
require_once 'inc/contextmenu.php';
require_once 'inc/move.php';
require_once 'inc/copy.php';
require_once 'inc/conflict.php';
require_once 'inc/error.php';
require_once 'inc/edit.php';
require_once 'inc/sidebar.php';
require_once 'inc/lightbox.php';
require_once 'inc/header_logout.php';
require_once 'inc/browser.php';
require_once 'inc/menubar.php';
require_once 'inc/upload.php';
require_once 'inc/footer.php';

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
<script src="js/main.js"></script>
</body>
</html>
