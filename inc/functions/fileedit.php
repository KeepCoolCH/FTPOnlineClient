<?php
// 🔄 Fetch file content for editing
if (isset($_GET['load'])) {
    $ftpClient = ftp_open_connection();

    $filename = $_GET['load'];

    $tmp = tempnam($localTempDir, 'edit_');

    if ($ftpClient->get($filename, $tmp)) {
        header('Content-Type: text/plain');
        readfile($tmp);
        unlink($tmp);
    } else {
        echo "❌ Failed to load file for editing.";
    }
    exit;
}

// 💾 Save edited file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['file'], $_POST['content'])) {
  $filename = $_POST['file'];
  $content = $_POST['content'];

  $extension = pathinfo($filename, PATHINFO_EXTENSION);

  if (!is_dir($localTempDir)) {
    mkdir($localTempDir, 0777, true);
  }

  $tmp = $localTempDir . '/tmp_' . uniqid();
  if ($extension) {
    $tmp .= '.' . $extension;
  }

  file_put_contents($tmp, $content);

  if ($ftp->put($tmp, $filename)) {
    unlink($tmp);
    header("Location: " . $_SERVER['PHP_SELF'] . "?path=" . urlencode($path));
  } else {
    unlink($tmp);
    echo "<script>alert('❌ Failed to save the file.');</script>";
  }
  exit;
}

if (isset($_FILES['upload'])) {
    foreach ($_FILES['upload']['name'] as $i => $name) {
        if ($_FILES['upload']['error'][$i] === 0) {
            $tmp_name = $_FILES['upload']['tmp_name'][$i];
            $filename = basename($name);
            $filename = get_unique_ftp_filename($ftp, $path, $filename);
            $local = $localTempDir . '/upload_' . $filename;
            move_uploaded_file($tmp_name, $local);
            $ftp->put($local, $path . '/' . $filename);
            unlink($local);
        }
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?path=" . urlencode($path));
    exit;
}