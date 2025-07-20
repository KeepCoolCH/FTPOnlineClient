<?php
// Create ZIP archive
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['zip_bulk'])) {
    $items = json_decode($_POST['zip_bulk'], true);
    if (!is_array($items) || empty($items)) {
        echo json_encode(['success' => false, 'error' => '❌ No items selected.']);
        exit;
    }

    // Determine ZIP filename
    if (count($items) === 1) {
        $base = pathinfo($items[0], PATHINFO_FILENAME);
        $zipName = basename($base) . '.zip';
    } else {
        $zipName = 'selection_' . date('Ymd_His') . '.zip';
    }

    // Prepare temporary paths
    $tmpBase = tempnam($localTempDir, 'zip_');
	unlink($tmpBase);
	$zipPath = $tmpBase . '.zip';
    $tempFolder = $localTempDir . '/zip_' . uniqid();
    mkdir($tempFolder, 0777, true);

    // Download FTP items to temporary local folder
    foreach ($items as $item) {
        $target = $tempFolder . '/' . basename($item);
        if ($ftp->is_dir($item)) {
            mkdir($target, 0777, true); // ensure empty root folder exists
            $ftp->recursive_fetch($item, $target);
        } else {
            $ftp->get($item, $target);
        }
    }

    // Create ZIP archive
    $zip = new ZipArchive();
    $success = false;

    if ($zip->open($zipPath, ZipArchive::CREATE)) {
        $dirIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tempFolder, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($dirIterator as $file) {
            $localPath = $file->getPathname();
            $relativePath = substr($localPath, strlen($tempFolder) + 1);

            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } elseif ($file->isFile()) {
                $zip->addFile($localPath, $relativePath);
            }
        }

        // Add fallback file if archive is empty
        if ($zip->numFiles === 0) {
            $zip->addFromString('README.txt', 'This ZIP contains only empty folders.');
        }

        $zip->close();
        $success = true;
    }

    // Upload ZIP back to FTP
    $remoteZipDir = $path;
    $uniqueName = get_unique_ftp_filename($ftp, $remoteZipDir, $zipName);
    $ftpUpload = $ftp->put($zipPath, $remoteZipDir . '/' . $uniqueName);

    // Clean up temporary files
    unlink($zipPath);
    system('rm -rf ' . escapeshellarg($tempFolder));

    send_json([
        'success' => true,
        'zip' => $uniqueName,
        'path' => $path
    ]);
}

// Convert ZIP entry name
function convert_zip_entry_name($entry) {
    $tryEncodings = ['UTF-8', 'CP437', 'ISO-8859-1', 'Windows-1252'];
    foreach ($tryEncodings as $enc) {
        $converted = iconv($enc, 'UTF-8//IGNORE', $entry);
        if ($converted && preg_match('//u', $converted)) {
            return str_replace(["\r", "\n", "\0"], '', $converted);
        }
    }
    return str_replace(["\r", "\n", "\0"], '', $entry); // Fallback
}

// Extract ZIP archive
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unzip_file'])) {
    $zipPath = trim($_POST['unzip_file']);
    $response = ['success' => false];
    $debug = [];

    if (empty($zipPath)) {
        $response['error'] = '❌ UNZIP error: path is empty.';
    } else {
        $tmpZip = tempnam($localTempDir, 'unzip_');
        $extractDir = $localTempDir . '/' . uniqid('extract_');

        $debug[] = "🔄 Downloading ZIP: $zipPath → $tmpZip";

        if ($ftp->get($zipPath, $tmpZip)) {
            $zip = new ZipArchive();
            if ($zip->open($tmpZip)) {
                mkdir($extractDir, 0777, true);

                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entry = $zip->getNameIndex($i);
                    if ($entry === false) continue;

                    $converted = convert_zip_entry_name($entry);
                    $contents = $zip->getFromIndex($i);
                    if ($contents === false) continue;

                    $targetPath = $extractDir . '/' . $converted;
                    $targetPath = preg_replace('#/+#', '/', $targetPath);

                    if (substr($converted, -1) === '/') {
                        if (!is_dir($targetPath)) mkdir($targetPath, 0777, true);
                    } else {
                        $dirPath = dirname($targetPath);
                        if (!is_dir($dirPath)) mkdir($dirPath, 0777, true);
                        file_put_contents($targetPath, $contents);
                    }
                }

                $zip->close();

                $zipBaseName = pathinfo($zipPath, PATHINFO_FILENAME);
                while (str_ends_with($zipBaseName, '.zip')) {
                    $zipBaseName = substr($zipBaseName, 0, -4);
                }

                $targetDirName = $ftp->get_unique_folder_name(dirname($zipPath), $zipBaseName);
                $remoteTarget = rtrim(dirname($zipPath), '/') . '/' . $targetDirName;
                $debug[] = "📤 Upload target: $remoteTarget";

                $uploadResult = $ftp->recursive_upload($extractDir, $remoteTarget);
                system('rm -rf ' . escapeshellarg($extractDir));
                unlink($tmpZip);

                if ($uploadResult['success']) {
                    $response['success'] = true;
                    $response['unpacked_to'] = $targetDirName;
                } else {
                    $response['error'] = '❌ Upload failed.';
                    $response['upload_errors'] = $uploadResult['errors'];
                }
            } else {
                unlink($tmpZip);
                $response['error'] = '❌ Could not open ZIP file.';
            }
        } else {
            unlink($tmpZip);
            $response['error'] = '❌ Could not download ZIP file.';
        }
    }

    if (isset($_POST['ajax']) && $_POST['ajax'] == 1) {
    send_json($response);
	}
}