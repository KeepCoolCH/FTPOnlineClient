<?php
// Wrapper: Directory listing
function ftp_list($connection, $path) {
    if ($connection instanceof FtpClient) {
        return $connection->list($path);
    }

    if ($connection['type'] === 'ftp') {
        return ftp_list($connection['conn'], $path);
    } elseif ($connection['type'] === 'sftp') {
        $sftp = $connection['sftp'];
        $dir = "ssh2.sftp://$sftp$path";
        $handle = opendir($dir);
        $result = [];
        if ($handle) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry !== '.' && $entry !== '..') {
                    $result[] = $entry;
                }
            }
            closedir($handle);
        }
        return $result;
    }

    return false;
}

// Wrapper: Delete file
function ftp_delete_file($connection, $path) {
    if ($connection['type'] === 'ftp') {
        return ftp_delete($connection['conn'], $path);
    } elseif ($connection['type'] === 'sftp') {
        return unlink("ssh2.sftp://{$connection['sftp']}$path");
    }
    return false;
}

// Wrapper: Upload file
function ftp_upload_file($connection, $remotePath, $localPath) {
    if ($connection['type'] === 'ftp') {
        return ftp_put($connection['conn'], $remotePath, $localPath, FTP_BINARY);
    } elseif ($connection['type'] === 'sftp') {
        return copy($localPath, "ssh2.sftp://{$connection['sftp']}$remotePath");
    }
    return false;
}

// Wrapper: Download file
function ftp_download_file($connection, $remotePath, $localPath) {
    if ($connection['type'] === 'ftp') {
        return ftp_get($connection['conn'], $localPath, $remotePath, FTP_BINARY);
    } elseif ($connection['type'] === 'sftp') {
        return copy("ssh2.sftp://{$connection['sftp']}$remotePath", $localPath);
    }
    return false;
}

// Wrapper: Download folder
function ftp_download_folder_as_zip($ftp, $remoteFolderPath, $localTempDir) {
    if (!$ftp->is_dir($remoteFolderPath)) {
        return ['success' => false, 'error' => '❌ Folder not found.'];
    }

    $tmpDir = $localTempDir . '/ftp_zip_' . uniqid();
    mkdir($tmpDir, 0777, true);

    $ftp->recursive_fetch($remoteFolderPath, $tmpDir);

    $base = tempnam($localTempDir, 'ftp_zip_');
	unlink($base);
	$zipPath = $base . '.zip';
    $zip = new ZipArchive();
    if (!$zip->open($zipPath, ZipArchive::CREATE)) {
        system('rm -rf ' . escapeshellarg($tmpDir));
        return ['success' => false, 'error' => '❌ Could not create ZIP archive.'];
    }

    $baseName = basename($remoteFolderPath);

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($tmpDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        $localPath = $file->getPathname();
        $relativePath = substr($localPath, strlen($tmpDir) + 1);
        $zipPathInArchive = $baseName . '/' . $relativePath;

        if ($file->isDir()) {
            $zip->addEmptyDir($zipPathInArchive);
        } else {
            $zip->addFile($localPath, $zipPathInArchive);
        }
    }

    $rootIsEmpty = count(scandir($tmpDir)) === 2;
    if ($rootIsEmpty) {
        $zip->addEmptyDir($baseName);
    }

    $zip->close();
    system('rm -rf ' . escapeshellarg($tmpDir));

    if (ob_get_length()) ob_end_clean();
    header('Content-Description: File Transfer');
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $baseName . '.zip"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($zipPath));
    flush();
    readfile($zipPath);

    unlink($zipPath);
    exit;
}

// Wrapper: Create directory
function ftp_mkdir_custom($connection, $path) {
    if ($connection['type'] === 'ftp') {
        return ftp_mkdir($connection['conn'], $path);
    } elseif ($connection['type'] === 'sftp') {
        return mkdir("ssh2.sftp://{$connection['sftp']}$path");
    }
    return false;
}

// Wrapper: Rename/move file or folder
function ftp_rename_custom($connection, $old, $new) {
    if ($connection['type'] === 'ftp') {
        return ftp_rename($connection['conn'], $old, $new);
    } elseif ($connection['type'] === 'sftp') {
        return rename("ssh2.sftp://{$connection['sftp']}$old", "ssh2.sftp://{$connection['sftp']}$new");
    }
    return false;
}