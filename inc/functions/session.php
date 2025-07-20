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
	echo '<meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1" />';
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
