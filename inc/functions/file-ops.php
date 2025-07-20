<?php
// Rename file or folder
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rename_bulk'])) {
    if (ob_get_length()) ob_end_clean();
    header('Content-Type: application/json');

    $renames = json_decode($_POST['rename_bulk'], true);
    if (!is_array($renames)) {
        echo json_encode(['success' => false, 'error' => 'Invalid rename data']);
        exit;
    }

    $results = [];
    foreach ($renames as $item) {
        $old = $item['old'] ?? null;
        $new = $item['new'] ?? null;

        if (!$old || !$new) {
            $results[] = ['success' => false, 'error' => 'Missing old or new path', 'old' => $old, 'new' => $new];
            continue;
        }

        // Fix: ensure proper path construction
        $newPath = rtrim(dirname($old), '/') . '/' . ltrim($new, '/');
        $success = $ftp->rename($old, $newPath);

        $results[] = [
            'old' => $old,
            'new' => $newPath,
            'success' => $success
        ];
    }

    echo json_encode([
        'success' => !in_array(false, array_column($results, 'success')),
        'results' => $results
    ]);
    exit;
}

// Single rename fallback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rename_old'], $_POST['rename_new'])) {
    if (ob_get_length()) ob_end_clean();
    header('Content-Type: application/json');

    $old = $_POST['rename_old'];
    $newNameOnly = $_POST['rename_new'];

    $pathInfo = pathinfo($old);
    $new = rtrim($pathInfo['dirname'], '/') . '/' . ltrim($newNameOnly, '/');

    $success = $ftp->rename($old, $new);

    if ($success) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?path=" . urlencode($path));
        exit;
    } else {
        echo json_encode(['success' => false, 'error' => 'Rename failed']);
    }
    exit;
}

// Single delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['delete'])) {
    $target = $_POST['delete'];
    if (rtrim($target, '/') === '/') {
        header("Location: " . $_SERVER['PHP_SELF'] . "?path=" . urlencode($path));
        exit;
    }
    $deleted = $ftp->delete($target);
    if (!$deleted) {
        $ftp->recursive_delete($target);
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?path=" . urlencode($path));
    exit;
}

// Bulk delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_bulk'])) {
    $items = json_decode($_POST['delete_bulk'], true);
    if (is_array($items)) {
        foreach ($items as $target) {
    		if (rtrim($target, '/') === '/') continue;
            if (!$ftp->delete($target)) {
                $ftp->recursive_delete($target);
            }
        }
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?path=" . urlencode($path));
    exit;
}

// Move file or folder
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['move_from']) && !empty($_POST['move_to'])) {
    $from = $_POST['move_from'];
    $to   = $_POST['move_to'];

    $sources = is_array($from) ? $from : [$from];
    $targets = is_array($to)   ? $to   : [$to];

    $success = true;

    foreach ($sources as $index => $src) {
        $dst = $targets[$index] ?? null;
        if (!$dst) continue;

        if ($ftp->file_exists($dst)) {
            if ($ftp->is_dir($dst)) {
                $ftp->recursive_delete($dst);
                $ftp->rmdir($dst);
            } else {
                $ftp->delete($dst);
            }
        }

        $result = $ftp->rename($src, $dst);
        if (!$result) $success = false;
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
    exit;
}

// Copy
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['copy_from'], $_POST['copy_to'])) {
    if (ob_get_length()) ob_end_clean();
    header('Content-Type: application/json');

    $sources = $_POST['copy_from'];
    $destinations = $_POST['copy_to'];
    $results = [];

    if (!is_array($sources)) $sources = [$sources];
    if (!is_array($destinations)) $destinations = [$destinations];

    foreach ($sources as $index => $sourceRaw) {
        $destinationRaw = $destinations[$index] ?? '';

        $source = preg_replace('#/+#', '/', trim($sourceRaw));
        $destination = preg_replace('#/+#', '/', trim($destinationRaw));
        $success = false;

        if (empty($source) || empty($destination)) {
            $results[] = ['success' => false, 'error' => 'Path is empty.'];
            continue;
        }

        if ($ftp->is_dir($source)) {
            $success = $success = ftp_recursive_copy($ftp, $source, $destination);
        } else {
            $tmp = tempnam($localTempDir, 'ftp_copy_');
            if (!$tmp) {
                $results[] = ['success' => false, 'error' => 'Could not create temporary file.'];
                continue;
            }

            if ($ftp->get($source, $tmp)) {
                if ($ftp->file_exists($destination)) {
                    $ftp->delete($destination);
                }

                $success = $ftp->put($tmp, $destination);

                if ($success && in_array($ftp->getType(), ['ftp', 'ftps'])) {
                    if (!file_exists($tmp) || filesize($tmp) <= 0) {
						$success = false;
					}
                }

                unlink($tmp);

                if (!$success) {
                    $results[] = ['success' => false, 'error' => 'Copy failed.'];
                    continue;
                }
            } else {
                unlink($tmp);
                $results[] = ['success' => false, 'error' => '❌ Could not download file from server (ftp->get).'];
                continue;
            }
        }

        $results[] = ['success' => true];
    }

    $allSuccess = count($results) > 0 && count(array_filter($results, fn($r) => $r['success'])) === count($results);

    echo json_encode(['success' => $allSuccess, 'results' => $results]);
    exit;
}


function ftp_recursive_copy($ftp, $src, $dst) {
    global $localTempDir;

    // Create destination folder – ignore if it already exists
    if (!$ftp->is_dir($dst)) {
        if (!$ftp->mkdir($dst)) return false;
    }

    $list = $ftp->list($src);
    if (!$list) return false;

    foreach ($list as $name) {
        if (in_array($name, ['.', '..'])) continue;

        $srcPath = rtrim($src, '/') . '/' . $name;
        $dstPath = rtrim($dst, '/') . '/' . $name;

        if ($ftp->is_dir($srcPath)) {
            // If folder already exists, either overwrite or continue recursively
            if (!ftp_recursive_copy($ftp, $srcPath, $dstPath)) {
				return false;
			}
        } else {
            $tmp = tempnam($localTempDir, 'ftp_copy_');

            if ($ftp->get($srcPath, $tmp)) {
                if ($ftp->file_exists($dstPath)) {
                    $ftp->delete($dstPath);
                }

                $ftp->put($tmp, $dstPath);
                unlink($tmp);
            } else {
                unlink($tmp); // Cleanup
            }
        }
    }

    return true;
}

$rawlist = $ftp->rawlist($path);

function ftp_recursive_delete($ftp, $dir) {
	if (rtrim($dir, '/') === '/') return false;
    $list = $ftp->rawlist($dir);
    if (!$list) {
        return $ftp->rmdir($dir);
    }

    foreach ($list as $item) {
        $parts = preg_split("/\s+/", $item, 9);
        if (count($parts) < 9) continue;
        $name = $parts[8];
        if ($name === '.' || $name === '..') continue;
        $is_dir = $parts[0][0] === 'd';
        $full = $dir . '/' . $name;

        if ($is_dir) {
            ftp_recursive_delete($ftp, $full);
            $ftp->rmdir($full);
        } else {
            $ftp->delete($full);
        }
    }

    return $ftp->rmdir($dir);
}

function ftp_recursive_upload($ftp, $localDir, $remoteDir) {
    $uploaded = [];
    $errors = [];

    // Only proceed if local directory exists
    if (!is_dir($localDir)) {
        $errors[] = "Local directory not found: $localDir";
        return ['success' => false, 'uploaded' => [], 'errors' => $errors];
    }

    // Create remote directory if it does not exist
    if (!$ftp->is_dir($remoteDir)) {
        if (!$ftp->mkdir($remoteDir)) {
            $errors[] = "Target directory could not be created: $remoteDir";
            return ['success' => false, 'uploaded' => [], 'errors' => $errors];
        }
    }

    $files = scandir($localDir);
    if (!is_array($files)) {
        $errors[] = "Could not read directory: $localDir";
        return ['success' => false, 'uploaded' => [], 'errors' => $errors];
    }

    foreach ($files as $file) {
        if (in_array($file, ['.', '..', '__MACOSX', '.DS_Store'])) continue;

        $localPath = $localDir . '/' . $file;
        $remotePath = rtrim($remoteDir, '/') . '/' . $file;

        if (is_dir($localPath)) {
            $result = ftp_recursive_upload($ftp, $localPath, $remotePath);
            $uploaded = array_merge($uploaded, $result['uploaded']);
            $errors = array_merge($errors, $result['errors']);
        } else {
            if ($ftp->put($localPath, $remotePath)) {
                $uploaded[] = $remotePath;
            } else {
                $errors[] = "❌ Upload failed: $localPath → $remotePath";
            }
        }
    }

    return [
        'success' => empty($errors),
        'uploaded' => $uploaded,
        'errors' => $errors
    ];
}

if (isset($_GET['rmdir'])) {
    $ftp->recursive_delete($target);
    header("Location: " . $_SERVER['PHP_SELF'] . "?path=" . urlencode($path));
    exit;
}

if (isset($_GET['delete'])) {
    $ftp->delete($_GET['delete']);
    header("Location: " . $_SERVER['PHP_SELF'] . "?path=" . urlencode($path));
    exit;
}

if (isset($_POST['new_folder'])) {
    $targetPath = $_POST['new_folder_path'] ?? $path;
    $folderName = trim($_POST['new_folder']);
    $newPath = rtrim($targetPath, '/') . '/' . $folderName;
    if ($folderName !== '') {
        $ftp->mkdir($newPath);
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?path=" . urlencode($newPath));
    exit;
}