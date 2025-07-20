<?php
// ➕ AJAX session check before FTP logic
$isAjax = (
    (isset($_POST['ajax']) && $_POST['ajax'] == 1) ||
    (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
);

if (!isset($_SESSION['ftp_host'], $_SESSION['ftp_user'], $_SESSION['ftp_pass'], $_SESSION['ftp_port'])) {
    if ($isAjax) {
        if (ob_get_length()) ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => '❌ Not logged in or session expired.']);
        exit;
    }
}

// 1. If credentials posted: test login first!
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ftp_host'])) {
    try {
        $test = new FtpClient(
            $_POST['ftp_secure'] ?? 'ftp',
            $_POST['ftp_host'],
            $_POST['ftp_port'] ?: 21,
            $_POST['ftp_user'],
            $_POST['ftp_pass']
        );
        // Login ok → save in session
        $_SESSION['ftp_secure'] = $_POST['ftp_secure'] ?? 'ftp';
        $_SESSION['ftp_host'] = $_POST['ftp_host'];
        $_SESSION['ftp_user'] = $_POST['ftp_user'];
        $_SESSION['ftp_pass'] = $_POST['ftp_pass'];
        $_SESSION['ftp_port'] = $_POST['ftp_port'] ?: 21;
        header("Location: " . $_SERVER['PHP_SELF']); // Reload to continue
        exit;
    } catch (Exception $e) {
        $_SESSION['login_error'] = $e->getMessage();
        header("Location: " . $_SERVER['PHP_SELF']); // Show form again
        exit;
    }
}

// 2. Show error if previously failed
if (isset($_SESSION['login_error'])) {
    echo "<div style='color:red;padding:10px;font-family:sans-serif;text-align:center'>{$_SESSION['login_error']}</div>";
    unset($_SESSION['login_error']);
}

// 3. If no session yet → show login form
if (!isset($_SESSION['ftp_host'], $_SESSION['ftp_user'], $_SESSION['ftp_pass'], $_SESSION['ftp_port'])) {
	echo '<meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=0.75" />';
    echo '<link rel="stylesheet" href="inc/style.css">';
    echo '<form method="post" style="max-width:400px;margin:50px auto;font-family:sans-serif;">
        <h2>FTP Login</h2>
        <select name="ftp_secure" required style="width:100%;padding:8px;margin:8px 0;">
            <option value="ftp">FTP</option>
            <option value="ftps">FTPS</option>
            <option value="sftp">SFTP</option>
        </select>
        <input type="text" name="ftp_host" placeholder="Server" required style="width:100%;padding:8px;margin:8px 0;">
        <input type="text" name="ftp_user" placeholder="Username" required style="width:100%;padding:8px;margin:8px 0;">
        <input type="password" name="ftp_pass" placeholder="Password" required style="width:100%;padding:8px;margin:8px 0;">
        <input type="number" name="ftp_port" placeholder="Port (default: 21)" style="width:100%;padding:8px;margin:8px 0;">
        <button type="submit" style="padding:10px 20px; margin:8px 0;">Connect</button>
    </form>';
    require_once 'inc/footer.php';
    exit;
}

// FTP connection data from session
define('FTP_SECURE', $_SESSION['ftp_secure']);
define('FTP_HOST', $_SESSION['ftp_host']);
define('FTP_USER', $_SESSION['ftp_user']);
define('FTP_PASS', $_SESSION['ftp_pass']);
define('FTP_PORT', $_SESSION['ftp_port']);
    
// Connection
function ftp_open_connection() {
    try {
        return new FtpClient(FTP_SECURE, FTP_HOST, FTP_PORT, FTP_USER, FTP_PASS);
    } catch (Exception $e) {

        $_SESSION['login_error'] = $e->getMessage();

        header("Location: " . $_SERVER['PHP_SELF']);
        session_unset();
        session_destroy();
        exit;
    }
}

$ftp = ftp_open_connection();
if (!$ftp || !($ftp instanceof FtpClient)) {
    abort_connection('❌ Connection failed.');
}

// Logout
if (isset($_POST['logout'])) {
    echo "❌ Successfully logged out from " . (defined('FTP_HOST') ? FTP_HOST : 'FTP server') . ".";
    session_destroy();
    header("Refresh: 2; URL=" . $_SERVER['PHP_SELF']);
    exit;
}

// Session abort
function abort_connection($error = '❌ Connection failed or session expired.') {
    $_SESSION['login_error'] = $error;

    if (isset($_POST['ajax']) && $_POST['ajax'] == 1) {
        if (ob_get_length()) ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $error]);
    } else {
        if (ob_get_length()) ob_end_clean();
        header("Location: " . $_SERVER['PHP_SELF']);
    }

    session_unset();
    session_destroy();
    exit;
}

$path = isset($_GET['path']) ? $_GET['path'] : '/';
if ($path === '') $path = '/';
$sort = $_GET['sort'] ?? 'name';
$sort_dir = $_GET['dir'] ?? 'asc';

class FtpClient {
    private $conn;
    private $type; // 'ftp', 'ftps', 'sftp'
    private $sftp;
    private $sftp_session;
    private $dirCache = [];

    public function __construct($type, $host, $port, $user, $pass) {
        $this->type = $type;
		
		$timeout = 5;
		
        if ($type === 'ftp') {
            $this->conn = ftp_connect($host, $port, $timeout);
            if (!$this->conn || !ftp_login($this->conn, $user, $pass)) {
                throw new Exception("FTP connection failed.");
            }
            ftp_pasv($this->conn, true);

        } elseif ($type === 'ftps') {
            $this->conn = ftp_ssl_connect($host, $port, $timeout);
            if (!$this->conn || !ftp_login($this->conn, $user, $pass)) {
                throw new Exception("FTPS connection failed.");
            }
            ftp_pasv($this->conn, true);

        } elseif ($type === 'sftp') {
            $this->sftp_session = ssh2_connect($host, $port);
            if (!$this->sftp_session || !ssh2_auth_password($this->sftp_session, $user, $pass)) {
                throw new Exception("SFTP connection failed.");
            }
            $this->sftp = ssh2_sftp($this->sftp_session);
            if (!$this->sftp) {
                throw new Exception("SFTP session creation failed.");
            }
            $this->conn = $this->sftp_session;
        } else {
            throw new Exception("Unknown protocol type: $type");
        }
    }

    public function list($path) {
		if ($this->type === 'sftp') {
			$dir = "ssh2.sftp://{$this->sftp}" . ($path === '/' ? '/' : rtrim($path, '/'));
			$entries = scandir($dir);
		} else {
			$entries = ftp_nlist($this->conn, $path);
		}
	
		return is_array($entries) ? array_values(array_filter($entries, function ($e) {
			return $e !== '.' && $e !== '..';
		})) : [];
	}

	public function listWithMeta($path) {
		$rawlist = $this->rawlist($path);
		if (!$rawlist || !is_array($rawlist)) return [];
	
		$entries = [];
		foreach ($rawlist as $entry) {
			$chunks = preg_split("/\s+/", $entry, 9);
			if (count($chunks) < 9) continue;
	
			$name = $chunks[8];
			$is_dir = $chunks[0][0] === 'd';
	
			$entries[] = [
				'name' => $name,
				'type' => $is_dir ? 'dir' : 'file',
				'size' => (int)$chunks[4],
				'date' => "$chunks[5] $chunks[6] $chunks[7]",
				'raw'  => $entry
			];
		}
	
		return $entries;
	}

	public function is_dir($path) {
		if ($this->type === 'sftp') {
			$sftpPath = "ssh2.sftp://{$this->sftp}{$path}";
			return is_dir($sftpPath);
		}
	
		$dir = dirname($path);
		$basename = basename($path);
		$entries = $this->listWithMeta($dir);
	
		foreach ($entries as $entry) {
			if ($entry['name'] === $basename) {
				return $entry['type'] === 'dir';
			}
		}
	
		return false;
	}
	
	public function check_dir_flag($path) {
		if (!$this->conn) return false;
	
		$original = ftp_pwd($this->conn);
		if ($original === false) return false;
	
		if (ftp_chdir($this->conn, $path)) {
			ftp_chdir($this->conn, $original);
			return true;
		}
		return false;
	}
    
	public function is_dir_cached($path) {
		$dir      = dirname($path);
		$basename = basename($path);
	
		if (!isset($this->dirCache[$dir])) {
			$this->dirCache[$dir] = $this->list($dir);
		}
	
		foreach ($this->dirCache[$dir] as $entry) {
			if (is_array($entry) && isset($entry['name']) && $entry['name'] === $basename) {
				return isset($entry['type'])
					? $entry['type'] === 'dir'
					: $this->check_dir_flag("$dir/{$entry['name']}");
			}
	
			if (is_string($entry) && $entry === $basename) {
				return $this->check_dir_flag("$dir/$entry");
			}
		}
	
		return false;
	}
	
	public function listDirectories($path) {
		$entries = $this->list($path);
	
		if (!$entries || !is_array($entries)) {
			error_log("⚠️ list() returned invalid data for path: $path");
			return [];
		}
	
		$dirs = [];
	
		foreach ($entries as $entry) {
			if (is_array($entry) && isset($entry['type']) && $entry['type'] === 'dir') {
				$fullPath = rtrim($path, '/') . '/' . $entry['name'];
				$fullPath = preg_replace('#/+#', '/', $fullPath);
	
				$dirs[] = [
					'name' => $entry['name'],
					'path' => $fullPath
				];
			} elseif (is_string($entry)) {
				// fallback, falls nur Dateinamen geliefert werden
				$fullPath = rtrim($path, '/') . '/' . $entry;
				$fullPath = preg_replace('#/+#', '/', $fullPath);
	
				if ($this->is_dir($fullPath)) {
					$dirs[] = [
						'name' => $entry,
						'path' => $fullPath
					];
				}
			}
		}
	
		return $dirs;
	}

    public function get($remote, $local) {
        if ($this->type === 'sftp') {
            $remotePath = "ssh2.sftp://{$this->sftp}" . $remote;
            return copy($remotePath, $local);
        } elseif ($this->type === 'ftp' || $this->type === 'ftps') {
            return ftp_get($this->conn, $local, $remote, FTP_BINARY);
        }
        return false;
    }

    public function put($local, $remote) {
        if ($this->type === 'sftp') {
            $remotePath = "ssh2.sftp://{$this->sftp}" . $remote;
            return copy($local, $remotePath);
        } elseif ($this->type === 'ftp' || $this->type === 'ftps') {
            return ftp_put($this->conn, $remote, $local, FTP_BINARY);
        }
        return false;
    }

    public function delete($path) {
        if ($this->type === 'sftp') {
            $remotePath = "ssh2.sftp://{$this->sftp}$path";
            return unlink($remotePath);
        }
        return ftp_delete($this->conn, $path);
    }

    public function rename($old, $new) {
        if ($this->type === 'sftp') {
            return ssh2_sftp_rename($this->sftp, $old, $new);
        }
        return ftp_rename($this->conn, $old, $new);
    }

    public function mkdir($path) {
        if ($this->type === 'sftp') {
            return ssh2_sftp_mkdir($this->sftp, $path, 0777, true);
        }
        return ftp_mkdir($this->conn, $path);
    }

    public function rmdir($path) {
        if ($this->type === 'sftp') {
            return ssh2_sftp_rmdir($this->sftp, $path);
        }
        return ftp_rmdir($this->conn, $path);
    }

    public function close() {
        if ($this->type !== 'sftp' && $this->conn) {
            ftp_close($this->conn);
        }
    }

    public function file_exists($path) {
        $parent = dirname($path);
        $basename = basename($path);
        $list = $this->list($parent);
        return $list && in_array($basename, $list);
    }

    public function size($path) {
        if ($this->type === 'sftp') {
            $stat = ssh2_sftp_stat($this->sftp, $path);
            return $stat && isset($stat['size']) ? $stat['size'] : -1;
        }
        return ftp_size($this->conn, $path);
    }

    public function getType() {
        return $this->type;
    }
    
    public function download($remoteFile, $localFile) {
		if ($this->type === 'sftp') {
			return ssh2_scp_recv($this->conn, $remoteFile, $localFile);
		} else {
			return ftp_get($this->conn, $localFile, $remoteFile, FTP_BINARY);
		}
	}

    public function rawlist($path) {
		if ($this->type === 'sftp') {
			$entries = scandir("ssh2.sftp://{$this->sftp}$path");
			if (!$entries) return false;
	
			$result = [];
			foreach ($entries as $entry) {
				if ($entry === '.' || $entry === '..') continue;
				$fullPath = "ssh2.sftp://{$this->sftp}$path/$entry";
				$stat = stat($fullPath);
				if (!$stat) continue;
	
				$is_dir = is_dir($fullPath);
				$size = $stat['size'] ?? 0;
				$date = date('M d H:i', $stat['mtime']);
	
				$result[] = sprintf('%s 1 user group %10d %s %s',
					$is_dir ? 'drwxr-xr-x' : '-rw-r--r--',
					$size,
					$date,
					$entry
				);
			}
			return $result;
		}
		$entries = $this->list($path);
		if (!$entries) return false;
	
		$result = [];
		foreach ($entries as $entry) {
			$fullPath = rtrim($path, '/') . '/' . $entry;
			$is_dir = $this->check_dir_flag($fullPath);
			$size = $is_dir ? 0 : ftp_size($this->conn, $fullPath);
			$time = ftp_mdtm($this->conn, $fullPath);
			$date = $time > 0 ? date('M d H:i', $time) : 'Jan 01 00:00';
	
			$result[] = sprintf('%s 1 user group %10d %s %s',
				$is_dir ? 'drwxr-xr-x' : '-rw-r--r--',
				$size,
				$date,
				$entry
			);
		}
		return $result;
	}

    public function recursive_fetch($remotePath, $localPath) {
        $items = $this->list($remotePath);
		if ($items === false) return;
	
		mkdir($localPath, 0777, true);
	
		foreach ($items as $item) {
			if (in_array(basename($item), ['.', '..'])) continue;
	
			$remoteItem = rtrim($remotePath, '/') . '/' . basename($item);
			$localItem = rtrim($localPath, '/') . '/' . basename($item);
	
			if ($this->is_dir($remoteItem)) {
				$this->recursive_fetch($remoteItem, $localItem);
			} else {
				$this->download($remoteItem, $localItem);
			}
		}
	
		if (empty(array_diff($items, ['.', '..']))) {
			mkdir($localPath, 0777, true);
		}
    }

public function recursive_delete($path) {
	if (rtrim($path, '/') === '/') return false;
    $list = $this->rawlist($path);
    if (!$list) return $this->rmdir($path);

    foreach ($list as $item) {
        $parts = preg_split("/\s+/", $item, 9);
        if (count($parts) < 9) continue;
        $name = $parts[8];
        if ($name === '.' || $name === '..') continue;
        $is_dir = $parts[0][0] === 'd';
        $full = $path . '/' . $name;

        if ($is_dir) {
            $this->recursive_delete($full);
            $this->rmdir($full);
        } else {
            $this->delete($full);
        }
    }

    return $this->rmdir($path);
}

public function getConnection() {
    return $this->conn;
}

public function get_unique_folder_name($baseDir, $folderName) {
    $newName = $folderName;
    $i = 1;
    $fullPath = rtrim($baseDir, '/') . '/' . $newName;

    while ($this->is_dir($fullPath)) {
        $newName = $folderName . '_' . $i;
        $fullPath = rtrim($baseDir, '/') . '/' . $newName;
        $i++;
    }

    return $newName;
}

public function recursive_upload($localDir, $remoteDir) {
    $uploaded = [];
    $errors = [];

    if (!is_dir($localDir)) {
        return ['success' => false, 'errors' => ["Local directory not found."]];
    }

    if (!$this->is_dir($remoteDir)) {
        if (!$this->mkdir($remoteDir)) {
            return ['success' => false, 'errors' => ["Target directory could not be created."]];
        }
    }

    $files = scandir($localDir);
    foreach ($files as $file) {
        if (in_array($file, ['.', '..', '__MACOSX', '.DS_Store'])) continue;

        $localPath = $localDir . '/' . $file;
        $remotePath = rtrim($remoteDir, '/') . '/' . $file;

        if (is_dir($localPath)) {
            $result = $this->recursive_upload($localPath, $remotePath);
            $uploaded = array_merge($uploaded, $result['uploaded'] ?? []);
            $errors = array_merge($errors, $result['errors'] ?? []);
        } else {
            if ($this->put($localPath, $remotePath)) {
                $uploaded[] = $remotePath;
            } else {
                $errors[] = "Upload failed: $localPath → $remotePath";
            }
        }
    }

    return [
        'success' => empty($errors),
        'uploaded' => $uploaded,
        'errors' => $errors
    	];
	}
}

// Create temp folder
$localTempDir = __DIR__ . '/temp';
if (!is_dir($localTempDir)) mkdir($localTempDir, 0777, true);

// Clean up temp folder after 10 minutes
register_shutdown_function(function() use ($localTempDir) {
    foreach (glob("$localTempDir/*") as $file) {
        if (filemtime($file) < time() - 600) {
            $realFile = realpath($file);
            $realBase = realpath($localTempDir);

            // Check if realpath is valid and file is inside the temp directory
            if ($realFile && $realBase && strpos($realFile, $realBase) === 0) {
                if (is_dir($file)) {
                    system('rm -rf ' . escapeshellarg($file));
                } else {
                    unlink($file);
                }
            }
        }
    }
});

if (isset($_GET['action']) && $_GET['action'] === 'subfolders') {
    if (ob_get_length()) ob_end_clean();
    header('Content-Type: text/html; charset=utf-8');

    $ftp = ftp_open_connection();
    $path = $_GET['path'] ?? '/';

    echo build_target_folder_tree($ftp, $path, 0, $_GET['current_path'] ?? '', PHP_INT_MAX);
    exit;
}

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

// File preview
if (isset($_GET['preview']) && $_GET['preview'] !== '') {
    $preview_path = $_GET['preview'];
    $isAjax = (
        (isset($_POST['ajax']) && $_POST['ajax'] == 1) ||
        (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    );

    try {
        $ftp = ftp_open_connection();
    } catch (Exception $e) {
        if (ob_get_length()) ob_end_clean();
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => '❌ Preview connection failed.']);
        } else {
            echo "❌ Connection to server failed.";
        }
        exit;
    }

    $ext = strtolower(pathinfo($preview_path, PATHINFO_EXTENSION));
    $allowed = [
        'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'gif' => 'image/gif', 'svg' => 'image/svg+xml', 'webp' => 'image/webp',
        'pdf' => 'application/pdf', 'txt' => 'text/plain', 'html' => 'text/html'
    ];

    if (!array_key_exists($ext, $allowed)) {
        if ($isAjax) {
            if (ob_get_length()) ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => '❌ Preview not allowed.']);
        } else {
            echo "❌ Preview not allowed.";
        }
        exit;
    }

    $tmp = tempnam($localTempDir, 'ftp_');
    if ($ftp->get($preview_path, $tmp)) {
        $ftp->close();
        if (ob_get_length()) ob_end_clean();
        header('Content-Type: ' . $allowed[$ext]);
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        unlink($tmp);
        exit;
    } else {
        $ftp->close();
        unlink($tmp);
        if ($isAjax) {
            if (ob_get_length()) ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => '❌ File could not be loaded.']);
        } else {
            echo "❌ File could not be loaded.";
        }
        exit;
    }
}

function ftp_file_exists($ftp, $path) {
    return $ftp->file_exists($path);
}

function is_ftp_directory($ftp, $path) {
    return $ftp->is_dir($path);
}

function get_unique_ftp_filename($ftp, $dir, $filename) {
    $existing = $ftp->list($dir);
    if (!is_array($existing)) $existing = [];

    $base = pathinfo($filename, PATHINFO_FILENAME);
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $i = 1;
    $newName = $filename;

    while (in_array($dir . '/' . $newName, $existing) || in_array($newName, $existing)) {
        $newName = $base . '_' . $i . ($ext ? '.' . $ext : '');
        $i++;
    }

    return $newName;
}

function get_unique_ftp_folder($ftp, $baseDir, $folderName) {
    $newName = $folderName;
    $i = 1;
    $fullPath = rtrim($baseDir, '/') . '/' . $newName;

    while ($ftp->dirExists($fullPath)) {
        $newName = $folderName . '_' . $i;
        $fullPath = rtrim($baseDir, '/') . '/' . $newName;
        $i++;
    }

    return $newName;
}

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

// List directories
if (isset($_GET['list_dirs'])) {
    ob_clean();
    $path = $_GET['path'] ?? '/';
    $entries = $ftp->list($path);
    $dirs = [];

    foreach ($entries as $entry) {
        if (!is_string($entry) || in_array($entry, ['.', '..'])) continue;

        $fullPath = rtrim($path, '/') . '/' . $entry;

        if ($ftp->is_dir($fullPath)) {
            $dirs[] = [
                'name' => $entry,
                'path' => $fullPath
            ];
        }
    }

    header('Content-Type: application/json');
    echo json_encode($dirs);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'list_subdirs') {
    $path = $_GET['path'] ?? '/';
    $dirs = [];

    $list = $ftp->nlist($path);
    foreach ($list as $entry) {
        if ($entry === '.' || $entry === '..') continue;

        $full = rtrim($path, '/') . '/' . basename($entry);
        if ($ftp->isDir($full)) {
            $dirs[] = basename($entry);
        }
    }

    header('Content-Type: application/json');
    echo json_encode($dirs);
    exit;
}

function build_folder_tree($ftp, $base = '/', $level = 0, $current_path = '', $max_level = PHP_INT_MAX) {
    if ($level >= $max_level) return '';

    $html = '';
    $current_path = rtrim($current_path, '/');

    if ($level === 0 && $base === '/') {
        $id = 'id_' . md5('/');
        $isActive = $current_path === '';
        $highlight = $isActive ? 'font-weight:bold;' : '';
        $html .= "<div style='padding-left:2px; cursor:pointer; $highlight'>";
        $html .= "<a href=\"?path=/\" data-path=\"/\" class=\"folder-link\">";
        $html .= "<div class=\"folder-row\" data-path=\"/\" data-type=\"dir\" 
                  ondragover=\"event.preventDefault(); this.classList.add('drop-hover');\" 
                  ondragleave=\"this.classList.remove('drop-hover');\" 
                  ondrop=\"this.classList.remove('drop-hover'); handleDrop(event, '/')\">";
        $html .= "<span id=\"icon_$id\" class=\"toggle\" onclick=\"toggleFolder('$id', 'icon_$id')\" data-path=\"/\">📂</span> ";
        $html .= "root /</div>";
        $html .= "</a>";
        $html .= "<div id=\"$id\" class=\"subfolders\" style=\"display:block; margin:0; padding-left:0;\" data-loaded=\"1\">";
    }

    $list = $ftp->list($base);
    if (!$list || !is_array($list)) return '';

    usort($list, 'strnatcasecmp');

    foreach ($list as $entry) {
        if (!is_string($entry) || trim($entry) === '' || in_array($entry, ['.', '..', 'temp'])) continue;

        $fullPath = rtrim(($base === '/' ? '' : $base) . '/' . $entry, '/');
        if (!$ftp->is_dir($fullPath)) continue;

        $isExactMatch = $fullPath === $current_path;
        $isParent = str_starts_with($current_path . '/', $fullPath . '/');
        $open = $isExactMatch || $isParent;

        $highlight = 'font-weight:' . ($isExactMatch ? 'bold' : 'normal') . ';';
        $padding = ($level + 1) * 5;

        $id = 'id_' . md5($fullPath);
        $iconId = 'icon_' . $id;
        $icon = $open ? '📂' : '📁';
        $ulDisplay = $open ? 'inline' : 'none';

        $html .= "<div style='padding-left:{$padding}px; cursor:pointer; $highlight'>";
        $html .= "<a href=\"?path=" . urlencode($fullPath) . "\" data-path=\"$fullPath\" class=\"folder-link\">";
        $html .= "<div class=\"folder-row\" data-path=\"$fullPath\" data-type=\"dir\" 
                  ondragover=\"event.preventDefault(); this.classList.add('drop-hover');\" 
                  ondragleave=\"this.classList.remove('drop-hover');\" 
                  ondrop=\"this.classList.remove('drop-hover'); handleDrop(event, '$fullPath');\">";
        $html .= "<span id=\"$iconId\" class=\"toggle\" onclick=\"toggleFolder('$id', '$iconId')\" data-path=\"$fullPath\">$icon</span> ";
        $html .= "$entry</div>";
        $html .= "</a>";

        $html .= "<div id=\"$id\" data-path=\"$fullPath\" data-type=\"dir\" class=\"subfolders\" style=\"display:$ulDisplay;\" data-loaded=\"" . ($open ? '1' : '0') . "\">";
        if ($open) {
            $html .= build_folder_tree($ftp, $fullPath, $level + 1, $current_path, $max_level);
        }
        $html .= "</div>"; // end subfolders
        $html .= "</div>"; // end folder entry
    }

    if ($level === 0 && $base === '/') {
        $html .= "</div></div>"; // close root wrapper
    }

    return $html;
}

function build_target_folder_tree($ftp, $base = '/', $level = 0, $current_path = '', $max_level = PHP_INT_MAX) {
    if ($level >= $max_level) return '';

    $html = '';
    $current_path = rtrim($current_path, '/');

    if ($level === 0 && $base === '/') {
        $id = 'id_' . md5('/');
        $isActive = $current_path === '';
        $highlight = $isActive ? 'font-weight:bold;' : '';
        $extraClass = $isActive ? ' selected-folder' : '';
        $html .= "<div style='padding-left:2px; cursor:pointer;'>";
        $html .= "<div class=\"folder-row$extraClass\" data-path=\"/\" data-type=\"dir\" style=\"$highlight\" onclick=\"toggleFolder('$id', 'icon_$id')\">";
        $html .= "<span id=\"icon_$id\" class=\"toggle\" data-path=\"/\">📂</span> root /</div>";
        $html .= "<div id=\"$id\" class=\"subfolders\" style=\"display:block; margin:0; padding-left:0;\" data-loaded=\"1\">";
    }

    $list = $ftp->list($base);
    if (!$list || !is_array($list)) return '';

    usort($list, 'strnatcasecmp');

    foreach ($list as $entry) {
        if (!is_string($entry) || trim($entry) === '' || in_array($entry, ['.', '..', 'temp'])) continue;

        $fullPath = '/' . ltrim($base, '/') . '/' . ltrim($entry, '/');
		$fullPath = preg_replace('#/+#', '/', $fullPath);
        if (!$ftp->is_dir($fullPath)) continue;

        $isExactMatch = $fullPath === $current_path;
        $isParent = str_starts_with($current_path . '/', $fullPath . '/');
        $open = $isExactMatch || $isParent;

        $highlight = $isExactMatch ? 'font-weight:bold;' : '';
        $extraClass = $isExactMatch ? ' selected-folder' : '';
        $padding = ($level + 1) * 5;

        $id = 'id_' . md5($fullPath);
        $iconId = 'icon_' . $id;
        $icon = $open ? '📂' : '📁';
        $ulDisplay = $open ? 'inline' : 'none';

        $html .= "<div style='padding-left:{$padding}px; cursor:pointer;'>";
        $html .= "<div class=\"folder-row$extraClass\" data-path=\"$fullPath\" data-type=\"dir\" onclick=\"toggleFolder('$id', '$iconId')\" style=\"$highlight\">";
        $html .= "<span id=\"$iconId\" class=\"toggle\" onclick=\"toggleFolder('$id', '$iconId')\" data-path=\"$fullPath\">$icon</span> ";
        $html .= "$entry</div>";

        $html .= "<div id=\"$id\" data-path=\"$fullPath\" data-type=\"dir\" class=\"subfolders\" style=\"display:$ulDisplay;\" data-loaded=\"" . ($open ? '1' : '0') . "\">";
        if ($open) {
            $html .= build_target_folder_tree($ftp, $fullPath, $level + 1, $current_path, $max_level);
        }
        $html .= "</div></div>";
    }

    if ($level === 0 && $base === '/') {
        $html .= "</div></div>";
    }

    return $html;
}


if (isset($_GET['action']) && $_GET['action'] === 'subfolders') {
    $path = $_GET['path'] ?? '/';
    $current_path = $_GET['current_path'] ?? '/';
    $mode = $_GET['mode'] ?? 'sidebar';

    if ($mode === 'target') {
        echo build_target_folder_tree($ftp->conn, $path, $current_path);
    } else {
        echo build_folder_tree($ftp->conn, $path, $current_path);
    }
    exit;
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

// 🔄 Fetch file content for editing
if (isset($_GET['load'])) {
    $ftpClient = ftp_open_connection();

    $filename = $_GET['load'];
    $localTempDir = __DIR__ . '/temp';

    if (!is_dir($localTempDir)) {
        mkdir($localTempDir, 0777, true);
    }

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

if (isset($_GET['download'])) {
    $download_path = $_GET['download'];
    $tmp = tempnam($localTempDir, 'ftp_');

    if (!$tmp) {
        die("❌ Could not create temp file.");
    }

    // Download file
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

$entries = [];
$rawlist = $ftp->rawlist($path);
if ($rawlist !== false) {
    foreach ($rawlist as $entry) {
        $chunks = preg_split("/\s+/", $entry, 9);
        if (count($chunks) < 9) continue;

        $name = $chunks[8];
        $fullpath = ($path === '/' ? '' : $path) . '/' . $name;
        $fullpath = preg_replace('#/+#', '/', $fullpath);

        $is_dir = $chunks[0][0] === 'd';

        $entries[] = [
            'is_dir' => $is_dir,
            'name' => $name,
            'size' => (int)$chunks[4],
            'date_str' => "$chunks[5] $chunks[6] $chunks[7]",
            'raw' => $entry,
            'chunks' => $chunks
        ];
    }
}

usort($entries, function($a, $b) use ($sort, $sort_dir) {
    if ($a['is_dir'] !== $b['is_dir']) return $a['is_dir'] ? -1 : 1; // Directories first
    $valA = $a[$sort] ?? $a['name'];
    $valB = $b[$sort] ?? $b['name'];
    $cmp = ($valA <=> $valB);
    return $sort_dir === 'desc' ? -$cmp : $cmp;
});

function sort_link($label, $field, $current, $dir) {
    $new_dir = ($field === $current && $dir === 'asc') ? 'desc' : 'asc';
    $arrow = $field === $current ? ($dir === 'asc' ? '▲' : '▼') : '';
    return "<a href='?sort=$field&dir=$new_dir'>$label $arrow</a>";
}

// Ajax check: does path already exist?
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_exists'])) {
    $checkPath = $_POST['check_exists'];
    $dir = dirname($checkPath);
    $name = basename($checkPath);

    $uniqueName = get_unique_ftp_filename($ftp, $dir, $name);

    echo json_encode([
        'exists' => ($uniqueName !== $name),
        'suggested' => $uniqueName
    ]);
    exit;
}

// Helper function send json
function send_json($data) {
    if (ob_get_length()) ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}