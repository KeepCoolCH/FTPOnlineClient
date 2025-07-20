<?php
// Download file
if (isset($_GET['download'])) {
    $download_path = $_GET['download'];
    $tmp = tempnam($localTempDir, 'ftp_');

    if (!$tmp) {
        die("❌ Could not create temp file.");
    }

    if ($ftp->get($download_path, $tmp)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($download_path) . '"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        unlink($tmp);
        exit;
    } else {
        unlink($tmp);
        die("❌ Failed to load file.");
    }
}

// Download as zip file
if (isset($_GET['download_zip'])) {
    $folder = $_GET['download_zip'];
    $result = ftp_download_folder_as_zip($ftp, $folder, $localTempDir);

    if (!$result['success']) {
        die($result['error']);
    }

    $zipPath = $result['zipPath'];
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . basename($folder) . '.zip"');
    header('Content-Length: ' . filesize($zipPath));
    readfile($zipPath);
    unlink($zipPath);
    exit;
}

// Download selection as ZIP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_bulk'])) {
    $items = json_decode($_POST['download_bulk'], true);
    if (!is_array($items) || empty($items)) {
        die("❌ Invalid selection.");
    }

    // 📦 Determine ZIP filename
    if (count($items) === 1) {
        $info = pathinfo($items[0]);
        $base = $info['filename'] ?? basename($items[0]);
        $zipName = $base . '.zip';
    } else {
        $zipName = 'selection_' . date('Ymd_His') . '.zip';
    }

    $baseZip = tempnam($localTempDir, 'bulkzip_');
	unlink($baseZip);
	$zipPath = $baseZip . '.zip';
    $tempFolder = $localTempDir . '/bulk_' . uniqid();
    mkdir($tempFolder, 0777, true);

    // ⬇️ Download selected files/folders to temp
    foreach ($items as $item) {
		$basename = basename($item);
		$target = $tempFolder . '/' . $basename;
	
		if ($ftp->is_dir($item)) {
			mkdir($target, 0777, true); // ⬅️ ensure top-level folder is created
			$ftp->recursive_fetch($item, $target);
		} else {
			$ftp->get($item, $target);
		}
	}

    // 🧳 Create ZIP (including empty folders)
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tempFolder, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $localPath = $file->getPathname();
            $relativePath = substr($localPath, strlen($tempFolder) + 1);

            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } elseif ($file->isFile()) {
                $zip->addFile($localPath, $relativePath);
            }
        }

        $zip->close();
    }

    // 📥 Send ZIP to browser
    if (ob_get_length()) ob_end_clean();
	header('Content-Description: File Transfer');
	header('Content-Type: application/zip');
	header('Content-Disposition: attachment; filename="' . basename($zipName) . '"');
	header('Content-Transfer-Encoding: binary');
	header('Expires: 0');
	header('Cache-Control: must-revalidate');
	header('Pragma: public');
	header('Content-Length: ' . filesize($zipPath));
	flush();
	readfile($zipPath);

    // 🧹 Clean up
	unlink($zipPath);
	system('rm -rf ' . escapeshellarg($tempFolder));
	exit;
}