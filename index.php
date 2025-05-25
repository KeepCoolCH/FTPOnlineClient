<?php

// FTP Online Client V.1.0
// made by Kevin Tobler
// www.kevintobler.ch

session_start();
ob_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$css_style = '<style>
footer {
	position: fixed;
	bottom: 0;
    left: 0;
    width: 100%;
    background-color: #ffffff;
    text-align: center;
	margin: 150px 0px 0px 0px;
	padding: 15px;
    }
    body {
	font-family: sans-serif;
	background: #f5f7fa;
	padding: 20px;
	}
	
	.drop-hover {
	  outline: 2px dashed #007bff;
	  background-color: #e6f0fa;
	  border-radius: 8px;
	}
	
	.folder-row.drop-hover {
	  outline: 2px dashed #007bff;
	  background-color: #e6f0fa;
	  border-radius: 8px;
	}
	
	.folder-row {
	  position: relative;
	  cursor: pointer;
	  padding: 10px;
	  border-radius: 8px;
	}
	
	.folder-row:hover {
	  background-color: #e6f0fa;
	  border-radius: 8px;
	}
	
	.subfolders {
	  position: relative;
	  cursor: pointer;
	  padding: 0px;
	}
	
	.file-actions form {
		display: inline;
	}
	
	#drop-area {
		border: 2px dashed #888;
		padding: 20px;
		border-radius: 10px;
		background: #fff;
		text-align: center;
		margin-top: 20px;
		height: 150px; /* Increased height */
		display: flex;
		justify-content: center;
		align-items: center;
		flex-direction: column;
	}
	
	#drop-area.highlight {
		background-color: #d0ebff;
		border-color: #339af0;
	}
	
	button {
		background: #cccccc;
		color: #fff;
		border: none;
		padding: 6px 12px;
		margin: 2px;
		border-radius: 2px;
		cursor: pointer;
	}
	
	button:hover {
		background: #eee;
	}
	
	.filename {
		flex: 1;
		text-align: left;
		display: flex;
		align-items: center;
	}
	
	.file-actions {
		flex-shrink: 0;
	}
	
	.folder-row.selected-folder {
	  font-weight: bold;
	  background: #e6f0fa;
	}
	
	td img {
		max-height: 40px;
		max-width: 60px;
		vertical-align: middle;
		margin-right: 8px;
		border-radius: 4px;
	}
	
	a {
		color: #000000; /* Link color */
		text-decoration: none; /* No underline */
	}
	
	a:hover {
		text-decoration: none; /* Optional underline on hover */
		color: #0056b3; /* Hover color */
	}
	
	tbody tr {
		border-bottom: 1px solid #ccc;
	}
	
	tbody tr:hover {
		background-color: #eef2f5;
	}
	
	tbody tr[data-href] {
		cursor: pointer;
	}
	
	tbody tr[data-href]:hover {
		background-color: #eef2f5;
	}
	
	tr.dragging {
		opacity: 0.5;
		background-color: #cce5ff !important;
	}
	
	tr.drop-target {
		outline: 2px dashed #007bff;
		background-color: #e6f0fa;
	}
	
	tr.selected {
		background-color: #cde5ff !important;
	}
	
	.context-menu {
		position: absolute;
		background: #fff;
		border: 1px solid #ccc;
		display: none;
		z-index: 1000;
		box-shadow: 2px 2px 10px rgba(0,0,0,0.2);
	}
	
	.context-menu button {
		display: block;
		width: 100%;
		background: none;
		border: none;
		padding: 8px 12px;
		text-align: left;
		cursor: pointer;
		color: #000;
	}
	
	.context-menu button:hover {
		background: #eee;
	}
	
	.context-menuBar {
		position: flex;
		display: none;
		display: inline;
		z-index: 1000;
	}
	
	.context-menuBar button:hover {
		background: #eee;
	}
	
	.context-menuBarParent {
		position: flex;
		display: none;
		display: inline;
		margin-left: 5px;
		margin-right: 5px;
		z-index: 1000;
	}
	
	#loadingOverlay {
		display: none;
		position: fixed;
		top: 0; left: 0;
		width: 100vw; height: 100vh;
		background: rgba(255, 255, 255, 0.7);
		z-index: 5000;
		justify-content: center;
		align-items: center;
	}
	
	#lightbox {
		position: fixed;
		top: 0;
		left: 0;
		width: 100vw; /* Full viewport width */
		height: 100vh; /* Full viewport height */
		background: rgba(0,0,0,1);
		z-index: 9999;
		display: none;
		align-items: center;
		justify-content: center;
	}
	
	.lightbox-content {
		position: relative;
		width: 100%;
		height: 100%;
		display: flex;
		flex-direction: column;
		justify-content: center;
		align-items: center;
	}
	
	.lightbox-content img#lightbox-img {
		max-width: 100%;
		max-height: 100%;
		object-fit: contain;
		border-radius: 0;
		display: block;
	}
	
	.close {
		position: absolute;
		top: 15px;
		right: 15px;
		background: transparent;
		border: none;
		cursor: pointer;
		z-index: 1000;
	}
	
	/* Arrow buttons visible by default */
	.prev, .next {
		position: absolute;
		top: 50%;
		transform: translateY(-50%);
		background: transparent;
		border: none;
		cursor: pointer;
		z-index: 1000;
	}
	
	.prev {
		left: 20px;
	}
	
	.next {
		right: 20px;
	}
	
	/* Hide on small screens (e.g. mobile) */
	@media (max-width: 768px) {
		.prev, .next {
			display: none;
		}
	}
	
	#loading img {
		width: 32px;
		height: 32px;
	}
	
	.close, .prev, .next {
		transition: opacity 0.5s;
	}
	
	#lightbox-overlay {
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		cursor: grab;
		z-index: 10;
	}
    </style>';
	
    $footer = '<footer>FTP Online Client V.1.0 © 2025 by Kevin Tobler - <a href="https://kevintobler.ch" target="_blank">www.kevintobler.ch</a></footer>';
    

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
    echo $css_style;
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
    echo $footer;
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
            $this->conn = @ftp_connect($host, $port, $timeout);
            if (!$this->conn || !@ftp_login($this->conn, $user, $pass)) {
                throw new Exception("FTP connection failed.");
            }
            ftp_pasv($this->conn, true);

        } elseif ($type === 'ftps') {
            $this->conn = @ftp_ssl_connect($host, $port, $timeout);
            if (!$this->conn || !@ftp_login($this->conn, $user, $pass)) {
                throw new Exception("FTPS connection failed.");
            }
            ftp_pasv($this->conn, true);

        } elseif ($type === 'sftp') {
            $this->sftp_session = @ssh2_connect($host, $port);
            if (!$this->sftp_session || !@ssh2_auth_password($this->sftp_session, $user, $pass)) {
                throw new Exception("SFTP connection failed.");
            }
            $this->sftp = @ssh2_sftp($this->sftp_session);
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
			$entries = @scandir($dir);
		} else {
			$entries = @ftp_nlist($this->conn, $path);
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
	
		$original = @ftp_pwd($this->conn);
		if ($original === false) return false;
	
		if (@ftp_chdir($this->conn, $path)) {
			@ftp_chdir($this->conn, $original);
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
            return @copy($remotePath, $local);
        } elseif ($this->type === 'ftp' || $this->type === 'ftps') {
            return @ftp_get($this->conn, $local, $remote, FTP_BINARY);
        }
        return false;
    }

    public function put($local, $remote) {
        if ($this->type === 'sftp') {
            $remotePath = "ssh2.sftp://{$this->sftp}" . $remote;
            return @copy($local, $remotePath);
        } elseif ($this->type === 'ftp' || $this->type === 'ftps') {
            return @ftp_put($this->conn, $remote, $local, FTP_BINARY);
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
            $stat = @ssh2_sftp_stat($this->sftp, $path);
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
			$entries = @scandir("ssh2.sftp://{$this->sftp}$path");
			if (!$entries) return false;
	
			$result = [];
			foreach ($entries as $entry) {
				if ($entry === '.' || $entry === '..') continue;
				$fullPath = "ssh2.sftp://{$this->sftp}$path/$entry";
				$stat = @stat($fullPath);
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
			$size = $is_dir ? 0 : @ftp_size($this->conn, $fullPath);
			$time = @ftp_mdtm($this->conn, $fullPath);
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
	
		@mkdir($localPath, 0777, true);
	
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
			@mkdir($localPath, 0777, true);
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
            $realFile = @realpath($file);
            $realBase = @realpath($localTempDir);

            // Check if realpath is valid and file is inside the temp directory
            if ($realFile && $realBase && strpos($realFile, $realBase) === 0) {
                if (is_dir($file)) {
                    system('rm -rf ' . escapeshellarg($file));
                } else {
                    @unlink($file);
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
        return @ftp_list($connection['conn'], $path);
    } elseif ($connection['type'] === 'sftp') {
        $sftp = $connection['sftp'];
        $dir = "ssh2.sftp://$sftp$path";
        $handle = @opendir($dir);
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
        return @ftp_delete($connection['conn'], $path);
    } elseif ($connection['type'] === 'sftp') {
        return @unlink("ssh2.sftp://{$connection['sftp']}$path");
    }
    return false;
}

// Wrapper: Upload file
function ftp_upload_file($connection, $remotePath, $localPath) {
    if ($connection['type'] === 'ftp') {
        return @ftp_put($connection['conn'], $remotePath, $localPath, FTP_BINARY);
    } elseif ($connection['type'] === 'sftp') {
        return copy($localPath, "ssh2.sftp://{$connection['sftp']}$remotePath");
    }
    return false;
}

// Wrapper: Download file
function ftp_download_file($connection, $remotePath, $localPath) {
    if ($connection['type'] === 'ftp') {
        return @ftp_get($connection['conn'], $localPath, $remotePath, FTP_BINARY);
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

    $zipPath = tempnam($localTempDir, 'ftp_zip_') . '.zip';
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

    return ['success' => true, 'zipPath' => $zipPath];
}

// Wrapper: Create directory
function ftp_mkdir_custom($connection, $path) {
    if ($connection['type'] === 'ftp') {
        return @ftp_mkdir($connection['conn'], $path);
    } elseif ($connection['type'] === 'sftp') {
        return @mkdir("ssh2.sftp://{$connection['sftp']}$path");
    }
    return false;
}

// Wrapper: Rename/move file or folder
function ftp_rename_custom($connection, $old, $new) {
    if ($connection['type'] === 'ftp') {
        return @ftp_rename($connection['conn'], $old, $new);
    } elseif ($connection['type'] === 'sftp') {
        return @rename("ssh2.sftp://{$connection['sftp']}$old", "ssh2.sftp://{$connection['sftp']}$new");
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
    $zipPath = tempnam($localTempDir, 'zip_') . '.zip';
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

    header('Content-Type: application/json');
    echo json_encode([
        'success' => ($success && $ftpUpload),
        'zip' => $uniqueName,
        'path' => $path
    ]);
    exit;
}

// Convert ZIP entry name
function convert_zip_entry_name($entry) {
    $tryEncodings = ['UTF-8', 'CP437', 'ISO-8859-1', 'Windows-1252'];
    foreach ($tryEncodings as $enc) {
        $converted = @iconv($enc, 'UTF-8//IGNORE', $entry);
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

    // Optional debug output
    if (isset($_POST['debug'])) {
        $response['debug'] = $debug;
    }

    if (isset($_POST['ajax']) && $_POST['ajax'] == 1) {
        if (ob_get_length()) ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } else {
        if ($response['success']) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?path=" . urlencode($path));
        } else {
            echo "<script>hideSpinner(); alert('❌ Extraction failed: " . htmlspecialchars($response['error'] ?? 'Unknown error') . "');</script>";
        }
        exit;
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

    $zipPath = tempnam($localTempDir, 'bulkzip_') . '.zip';
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
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipName . '"');
    header('Content-Length: ' . filesize($zipPath));
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

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>FTP Manager</title>
<?php echo $css_style; ?>

<div class="context-menu" id="ctxMenu">
  <form id="ctxForm" method="POST" action="">
    <input type="hidden" id="ctxPath">
    <input type="hidden" name="rename_old" id="ctxRenameOld">
    <input type="hidden" name="rename_new" id="ctxRenameNew">
    <input type="hidden" name="delete" id="ctxDelete">
    <input type="hidden" name="zip_target" id="ctxZipTarget">
    <input type="hidden" name="unzip_file" id="ctxUnzipFile">
    <input type="hidden" name="copy_from" id="ctxCopyFrom">
    <input type="hidden" name="copy_to" id="ctxCopyTo">
    <input type="hidden" name="move_from" id="ctxMoveFrom">
    <input type="hidden" name="move_to" id="ctxMoveTo">
    <select id="copyTarget" style="width: 100%; display:none; margin: 5px 0;"></select>
    <button type="button" onclick="submitCopy()" id="ctxCopyBtnSubmit" style="display:none;">✅ Copy</button>
    <input type="hidden" name="download_zip" id="ctxDownloadZip">
    <input type="hidden" name="download" id="ctxDownload">
    <button type="button" onclick="triggerCreateFolder()" id="ctxNewFolderBtn">📁 Create folder in this directory</button>
    <button type="button" onclick="triggerDownloadSelectedZip()" id="ctxDownloadSelectedZipBtn">📥 Download selection</button>
    <button type="button" onclick="triggerDownloadZip()" id="ctxDownloadZipBtn">📥 Download folder</button>
    <button type="button" onclick="triggerDownload()" id="ctxDownloadBtn">📥 Download file</button>
    <button type="button" onclick="triggerRename()" id="ctxRenameBtn">✏️ Rename</button>
    <button type="button" onclick="triggerEdit()" id="ctxEditBtn">📝 Edit</button>
    <button type="button" onclick="triggerDelete()" id="ctxDeleteBtn">🗑️ Delete</button>
    <button type="button" onclick="triggerCopy()" id="ctxCopyBtn">📋 Copy</button>
    <button type="button" onclick="triggerMove()" id="ctxMoveBtn">🔀 Move</button>
    <button type="button" onclick="triggerZip()" id="ctxZipBtn">🗜️ Create ZIP</button>
    <button type="button" onclick="triggerUnzip()" id="ctxUnzipBtn">📦 Extract ZIP</button>
  </form>
</div>
</head>
<body>

<!-- Move Modal -->
<div id="moveSidebarModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:2000; justify-content:center; align-items:center;">
  <div style="background:#fff; padding:20px; border-radius:8px; min-width:400px; max-height:80vh; overflow-y:auto;">
    <h3>📁 Select target folder</h3>
    <div id="moveTargetTree"></div>
  </div>
</div>

<!-- Copy Modal -->
<div id="copySidebarModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:2000; justify-content:center; align-items:center;">
  <div style="background:#fff; padding:20px; border-radius:8px; min-width:400px; max-height:80vh; overflow-y:auto;">
    <h3>📁 Select target folder</h3>
    <div id="copyTargetTree"></div>
  </div>
</div>

<!-- Conflict dialog -->
<div id="conflictModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.4); z-index:3000; justify-content:center; align-items:center;">
  <div style="background:#fff; padding:20px; border-radius:8px; min-width:300px;">
    <h3>❗ File or folder already exists</h3>
    <p>What do you want to do?</p>
    <div style="text-align:right;">
      <button onclick="document.getElementById('conflictModal').style.display='none'">Cancel</button>
      <button onclick="resolveConflict('overwrite')">✅ Overwrite</button>
      <button onclick="showRenameInput()">✏️ Rename</button>
    </div>
  </div>
</div>

<!-- 🔔 Custom Error Popup -->
<div id="errorModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.4); z-index:4000; justify-content:center; align-items:center;">
  <div style="background:#fff; padding:20px; border-radius:8px; min-width:300px; border-left: 6px solid #e74c3c;">
    <h3 style="color:#e74c3c; margin-top:0;">❌ Error</h3>
    <p id="errorModalMessage">An error has occurred.</p>
    <div style="text-align:right;">
      <button onclick="document.getElementById('errorModal').style.display='none'" style="background:#e74c3c;">Close</button>
    </div>
  </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(255,255,255,0.7); z-index:5000; justify-content:center; align-items:center;">
  <img src="lightbox/loading.gif" alt="Loading..." style="width:48px; height:48px;">
</div>

<!-- File Edit dialog -->
<div id="editorModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:#000000aa; z-index:9999; justify-content:center; align-items:center;">
  <div style="background:white; padding:20px; width:80%; max-width:1000px; height:50%; max-height:1000px; display:flex; flex-direction:column;">
    <div style="display:flex; justify-content:space-between; align-items:center;">
      <h2 id="editorFilename" style="margin:0; font-size:1.2em;">Edit file</h2>
    </div>
    <form id="editorForm" method="POST" action="" style="flex:1; display:flex; flex-direction:column;">
      <textarea name="content" id="editorContent" style="flex:1; width:100%; font-family:monospace; font-size:14px;"></textarea>
      <input type="hidden" name="file" id="editorFile">
      <div style="text-align:right; margin-top:10px;">
        <button onclick="closeEditor()">Cancel</button>
        <button type="submit">💾 Save</button>
      </div>
    </form>
  </div>
</div>

<!-- 📁 Sidebar -->
<div style="display: flex;">
  <div style="width: 250px; padding: 10px; background: #fff; border-radius: 8px; margin-right: 20px;">
    <h3>Folder Structure</h3>
    <div id="sidebarTree" style="list-style:none; padding-left:0px;">
      <?= build_folder_tree($ftp, '/', 0, $path, PHP_INT_MAX) ?>
    </div>
  </div>

  <!-- 📄 Main Content (file view) -->
  <div style="flex: 1;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
      <h1 style="margin: 0;">FTP Server</h1>
      
      <div class="context-menuBarParent" id="ctxMenuBarParent">
  <form id="ctxFormBarParent" method="POST" action="">
    <input type="hidden" name="new_folder_path" id="ctxNewFolderPath" value="<?= htmlspecialchars($path, ENT_QUOTES) ?>">
    <input type="hidden" name="new_folder" id="ctxNewFolderInput">
    <button type="button" onclick="triggerCreateFolderBar()" id="ctxNewFolderBtn">📁 Create folder in active directory</button>
      </form>
</div>
      <div class="context-menuBar" id="ctxMenuBar">
  <form id="ctxFormBar" method="POST" action="">
    <input type="hidden" id="ctxPath">
    <input type="hidden" name="rename_old" id="ctxRenameOld">
    <input type="hidden" name="rename_new" id="ctxRenameNew">
    <input type="hidden" name="delete" id="ctxDelete">
    <input type="hidden" name="zip_target" id="ctxZipTarget">
    <input type="hidden" name="unzip_file" id="ctxUnzipFile">
    <input type="hidden" name="copy_from" id="ctxCopyFrom">
    <input type="hidden" name="copy_to" id="ctxCopyTo">
    <input type="hidden" name="move_from" id="ctxMoveFrom">
    <input type="hidden" name="move_to" id="ctxMoveTo">
    <select id="copyTarget" style="width: 100%; display:none; margin: 5px 0;"></select>
    <button type="button" onclick="submitCopy()" id="ctxCopyBtnSubmit" style="display:none;">✅ Copy</button>
    <input type="hidden" name="download_zip" id="ctxDownloadZip">
    <input type="hidden" name="download" id="ctxDownload">
    <button type="button" onclick="triggerCreateFolder()" id="ctxNewFolderBtn">📁 Create folder in selected directory</button>
    <button type="button" onclick="triggerDownloadSelectedZip()" id="ctxDownloadSelectedZipBtn">📥 Download selection</button>
    <button type="button" onclick="triggerDownloadZip()" id="ctxDownloadZipBtn">📥 Download folder</button>
    <button type="button" onclick="triggerDownload()" id="ctxDownloadBtn">📥 Download file</button>
    <button type="button" onclick="triggerRename()" id="ctxRenameBtn">✏️ Rename</button>
    <button type="button" onclick="triggerEdit()" id="ctxEditBtn">📝 Edit</button>
    <button type="button" onclick="triggerDelete()" id="ctxDeleteBtn">🗑️ Delete</button>
    <button type="button" onclick="triggerCopy()" id="ctxCopyBtn">📋 Copy</button>
    <button type="button" onclick="triggerMove()" id="ctxMoveBtn">🔀 Move</button>
    <button type="button" onclick="triggerZip()" id="ctxZipBtn">🗜️ Create ZIP</button>
    <button type="button" onclick="triggerUnzip()" id="ctxUnzipBtn">📦 Extract ZIP</button>
  </form>
</div>
      
      <form method="post" style="margin: 0;">
        <button name="logout" value="1">🔌 Disconnect</button>
      </form>
    </div>

<!-- Lightbox -->
<div id="lightbox" class="lightbox" onclick="closeLightbox()">
  <div class="lightbox-content" onclick="event.stopPropagation()">
    <button class="close" onclick="closeLightbox()">
      <img src="lightbox/close.png" alt="Close" style="width:27px;height:27px;">
    </button>
    <button class="prev" onclick="prevImage(event)">
      <img src="lightbox/prev.png" alt="Previous" style="width:45px;height:50px;">
    </button>
    <button class="next" onclick="nextImage(event)">
      <img src="lightbox/next.png" alt="Next" style="width:45px;height:50px;">
    </button>
    <div id="loading" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);display:none;">
      <img src="lightbox/loading.gif" alt="Loading..." style="width:32px;height:32px;">
    </div>
    <img id="lightbox-img" src="" style="display:none;">
    <div id="lightbox-overlay" style="display:none;"></div>
  </div>
</div>

<h2>📂 Directory: <?= htmlspecialchars($path) ?></h2>
<?php
if (trim($path, '/') !== '') {
    $parent = dirname($path);
    if ($parent === '.' || $parent === '') $parent = '/';
    echo "<a href='?path=" . urlencode($parent) . "'>⬅️ Parent directory</a>";
}
echo "
<table style=\"width:100%; border-collapse: collapse;\">
<thead>
  <tr style=\"background:#eee; text-align:left;\">
    <th style=\"padding:8px; width:50px;\">Type</th>
    <th style=\"padding:8px; width:3000px;\">" . sort_link('Name', 'name', $sort, $sort_dir) . "</th>
    <th style=\"padding:8px; text-align:right; width:100px;\">" . sort_link('Size', 'size', $sort, $sort_dir) . "</th>
    <th style=\"padding:8px; text-align:right; min-width:150px;\">" . sort_link('Modified', 'date_str', $sort, $sort_dir) . "</th>
  </tr>
</thead>
<tbody>
";

$entries = array_filter($entries, function($e) {
    $hidden = ['.ds_store', '__macosx', '.git', 'thumbs.db'];
    return !in_array(strtolower($e['name']), $hidden);
});

foreach ($entries as $entry) {
    $chunks = $entry['chunks'];
    $is_dir = $entry['is_dir'];
    $name = $entry['name'];
    $fullpath = ($path === '/' ? '' : $path) . '/' . $name;
    $fullpath = preg_replace('#/+#', '/', $fullpath);
    $data_attrs = 'data-path="' . htmlspecialchars($fullpath) . '" data-type="' . ($is_dir ? 'dir' : 'file') . '"';
    $icon = $is_dir ? (($path . '/' . $name) === $path ? "📂" : "📁") : "📄";
    $size = $is_dir ? "–" : number_format($entry['size'] / 1024, 1) . " KB";

    $month = $chunks[5];
    $day   = $chunks[6];
    $yearOrTime = $chunks[7];

    if (strpos($yearOrTime, ':') !== false) {
        $year = date("Y");
        $time = $yearOrTime;
    } else {
        $year = $yearOrTime;
        $time = "00:00";
    }

    $date_string = "$month $day $year $time";
    $dt = DateTime::createFromFormat("M d Y H:i", $date_string);
    $modified = $dt ? $dt->format("Y-m-d H:i") : $date_string;

    $url = '#';
    if ($is_dir) {
        $url = "?path=" . urlencode($fullpath);
    } else {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $image_exts = ['jpg','jpeg','png','gif','svg','webp'];
        $viewable = array_merge($image_exts, ['pdf','txt','html','htm']);

        if (in_array($ext, $image_exts) || in_array($ext, $viewable)) {
            $url = "?preview=" . urlencode($fullpath);
        } else {
            $url = "?download=" . urlencode($fullpath);
        }
    }

    if (!empty($entries)) {
        $onclick = $is_dir
            ? "window.location.href='$url'"
            : "window.open('$url', '_blank')";

        echo "<tr $data_attrs onclick=\"toggleSelection(this, event)\" ondblclick=\"$onclick\" style=\"cursor:pointer;\">";
        echo "<td style='padding:8px; text-align:center;'>$icon</td>";

        if (!$is_dir && in_array($ext, ['jpg','jpeg','png','gif','svg','webp'])) {
            $thumbUrl = '?preview=' . urlencode($fullpath) . '&t=' . time();
            $escaped = htmlspecialchars($thumbUrl, ENT_QUOTES);
            $nameLink = "<a href=\"javascript:void(0);\" style=\"text-decoration: none; color: inherit;\" ondblclick=\"event.stopPropagation(); openLightbox('$escaped')\">" . htmlspecialchars($name) . "</a>";
        } else {
            $nameLink = htmlspecialchars($name);
        }

        echo "<td style='padding:8px; width:3000px; white-space: nowrap;'>$nameLink</td>";
        echo "<td style='padding:8px; text-align:right; width:100px; white-space: nowrap;'>$size</td>";
        echo "<td style='padding:8px; text-align:right; min-width:150px; white-space: nowrap;'>$modified</td>";
        echo "</tr>";
    }
}

if (empty($entries)) {
    $escapedPath = htmlspecialchars($path);
    echo "<tr data-path=\"$escapedPath\" data-type=\"dir\" onclick=\"toggleSelection(this, event)\">";
    echo "<td style='padding:8px; text-align:center;'>📭</td>";
    echo "<td style='padding:8px; width:3000px;'>No files or folders found</td>";
    echo "<td style='padding:8px; text-align:right; width:100px; white-space: nowrap;'></td>";
    echo "<td style='padding:8px; text-align:right; min-width:150px; white-space: nowrap;'></td>";
    echo "</tr>";
}

echo "</tbody></table>";
?>

<h3>📄 Upload Files:</h3>
<div id="drop-area">
  <p>Drag & drop files here or click</p>
  <form id="upload-form" method="post" enctype="multipart/form-data">
    <input type="file" name="upload[]" id="fileElem" multiple style="display:none;" onchange="this.form.submit()">
    <button type="button" onclick="document.getElementById('fileElem').click()">Select Files</button>
  </form>
</div>
</div>

<script>
function showSpinner() {
  document.getElementById('loadingOverlay').style.display = 'flex';
}

function hideSpinner() {
  document.getElementById('loadingOverlay').style.display = 'none';
}

document.addEventListener('DOMContentLoaded', () => {
document.querySelectorAll('.toggle').forEach(toggle => {
        toggle.addEventListener('click', e => {
            e.stopPropagation();
            const targetId = toggle.getAttribute('data-target');
            const target = document.getElementById(targetId);
            if (target) {
                const isVisible = target.style.display === 'inline';
                target.style.display = isVisible ? 'none' : 'inline';
                toggle.textContent = isVisible ? '📂' : '📂';
            }
        });
    });
});

const isMac = navigator.platform.toUpperCase().includes('MAC');
window.altPressed = false;

window.addEventListener('keydown', e => {
  if (e.key === 'Alt') window.altPressed = true;
});
window.addEventListener('keyup', e => {
  if (e.key === 'Alt') window.altPressed = false;
});

function initDraggables() {
  const fileRows     = [...document.querySelectorAll('tr[data-path]')];
  const sidebarItems = [];
  const allDraggables = [...fileRows];

  allDraggables.forEach(el => {
    el.setAttribute('draggable', 'true');

    el.addEventListener('dragstart', e => {
      let filesToMove = [{ path: el.dataset.path }];

      if (el.tagName === 'TR' && el.classList.contains('selected')) {
        const selected = [...document.querySelectorAll('tr.selected')];
        if (selected.length > 0) {
          filesToMove = selected.map(row => ({ path: row.dataset.path }));
        }
      }

      e.dataTransfer.setData('text/plain', JSON.stringify(filesToMove));
      el.classList.add('dragging');
    });

    el.addEventListener('dragend', () => {
      el.classList.remove('dragging');
    });
  });
}

function initDropTargets() {
  document.querySelectorAll('.folder-row[data-path], .subfolders[data-path], tr[data-type="dir"][data-path]').forEach(item => {
    if (item.dataset.dropInitialized) return;

    item.addEventListener('dragover', e => {
      e.preventDefault();
      e.stopPropagation();
      item.classList.add('drop-hover');
    });

    item.addEventListener('dragleave', () => {
      item.classList.remove('drop-hover');
    });

    item.addEventListener('drop', async e => {
      e.preventDefault();
      e.stopPropagation();
      item.classList.remove('drop-hover');

      const folderRow = e.target.closest('[data-path][data-type="dir"]');
      if (!folderRow) {
        showErrorModal("❌ Drop target missing or invalid.");
        return;
      }

      const toDir = folderRow.dataset.path?.replace(/\/+$/, '') || '/';
      if (!toDir) {
        showErrorModal("❌ Please select a valid folder.");
        return;
      }

      let draggedItems;
      try {
        draggedItems = JSON.parse(e.dataTransfer.getData("text/plain"));
      } catch (err) {
        showErrorModal("❌ Dragging within the sidebar is not allowed.");
        return;
      }

      const fromPaths = draggedItems.map(item => item.path);
      if (!fromPaths.length) {
        showErrorModal("❌ No items to move or copy.");
        return;
      }

      showSpinner();

      if (window.altPressed) {
        window.bulkCopySelection = fromPaths;
        confirmCopyTo(toDir);
      } else {
        window.bulkMoveSelection = fromPaths;
        confirmMoveTo(toDir);
      }
    });

    item.dataset.dropInitialized = "1";
  });
}

initDraggables();
initDropTargets();


function toggleFolder(targetId, iconId) {
    const container = document.getElementById(targetId);
    const icon = document.getElementById(iconId);
    if (!container) return;

    const isVisible = container.style.display === 'block';
    container.style.display = isVisible ? 'none' : 'block';
    if (icon) icon.textContent = isVisible ? '📁' : '📂';

    // AJAX-Nachladung nur beim ersten Öffnen
    if (!container.dataset.loaded || container.dataset.loaded !== "1") {
        const path = icon?.getAttribute('data-path') || '';
        fetch('?action=subfolders&path=' + encodeURIComponent(path))
            .then(res => res.text())
            .then(html => {
                container.innerHTML = html;
                container.dataset.loaded = "1";
                container.style.display = 'block';
                if (icon) icon.textContent = '📂';

                // Drop-Ziele neu initialisieren
                if (typeof initDropTargets === 'function') {
                    initDropTargets();
                }
            })
            .catch(error => {
                console.error("Error loading subfolders:", error);
                container.dataset.loaded = "error";
            });
    }
}

function navigateTo(event, path) {
    event.preventDefault();
    loadMainContent(path);
    history.pushState(null, '', '?path=' + path);
}

const ctxMenu = document.getElementById('ctxMenu');
const ctxMenuBar = document.getElementById('ctxMenuBar');
toggleContextMenuBarDisplay();
const ctxPath = document.getElementById('ctxPath');
const ctxNew = document.getElementById('ctxNew');
const ctxAction = document.getElementById('ctxAction');
const ctxZipTarget = document.getElementById('ctxZipTarget');

function clearCtxForm() {
  const ids = [
    'ctxRenameOld', 'ctxRenameNew', 'ctxDelete',
    'ctxUnzipFile', 'ctxCopyFrom', 'ctxCopyTo',
    'ctxMoveFrom', 'ctxMoveTo',
    'ctxDownload', 'ctxDownloadZip'
  ];
  ids.forEach(id => {
    const el = document.getElementById(id);
    if (el) el.value = ''; // important: clear it!
  });
}

document.querySelectorAll('tr[data-path]').forEach(el => {
  el.addEventListener('contextmenu', e => {
    const path = el.dataset.path;

    // 👉 Update selection (right-click → only select this row)
    const row = el.closest('tr');
    if (row && !row.classList.contains('selected')) {
      document.querySelectorAll('tr.selected').forEach(r => r.classList.remove('selected'));
      row.classList.add('selected');
    }

    showContextMenu(e, path); 
  });
});

function updateContextButtons(displayStyle, path, parentId) {
  const selected = [...document.querySelectorAll('tr.selected')];
  const selectedCount = selected.length;
  const target = document.querySelector(`tr[data-path="${CSS.escape(path)}"]`);
  const isFile = target?.dataset.type === 'file';
  const isDir  = target?.dataset.type === 'dir';
  const hasDir = selected.some(row => row.dataset.type === 'dir');
  const zipPath = selected[0]?.dataset.path || '';
  const isRoot = path === '/';

  const buttons = {
    ctxDownloadSelectedZipBtn: (!isRoot && selectedCount > 1),
    ctxDownloadBtn: (!isRoot && selectedCount === 1 && isFile),
    ctxDownloadZipBtn: (!isRoot && selectedCount === 1 && isDir),
    ctxRenameBtn: (!isRoot && selectedCount === 1),
    ctxEditBtn: (selectedCount === 1 && isFile),
    ctxDeleteBtn: (!isRoot && selectedCount >= 1),
    ctxCopyBtn: (!isRoot && selectedCount >= 1),
    ctxMoveBtn: (!isRoot && selectedCount >= 1),
    ctxZipBtn: (!isRoot && selectedCount >= 1),
    ctxNewFolderBtn: (selectedCount === 1 && isDir),
    ctxUnzipBtn: (!isRoot && selectedCount === 1 && zipPath.toLowerCase().endsWith('.zip') && !isDir)
  };
  
  const parent = document.getElementById(parentId);
  if (!parent) return;

  Object.entries(buttons).forEach(([id, shouldShow]) => {
    const el = parent.querySelector(`#${id}`);
    if (el) el.style.display = shouldShow ? displayStyle : 'none';
  });
}

function showContextMenuBar(path) {
  const ctxMenuBar = document.getElementById('ctxMenuBar');
  ctxPath.value = path;
  
  updateContextButtons('inline-block', path, 'ctxFormBar');
  ctxMenuBar.style.display = 'inline-block';
}

function toggleContextMenuBarDisplay() {
  const selected = document.querySelectorAll('tr.selected');
  const menuBar = document.getElementById('ctxMenuBar');
  menuBar.style.display = selected.length > 0 ? 'inline-block' : 'none';
}

document.addEventListener('click', (e) => {
  const isClickInside = e.target.closest('tr') || e.target.closest('#ctxMenuBar');
  if (!isClickInside) {
    document.querySelectorAll('tr.selected').forEach(r => r.classList.remove('selected'));
    toggleContextMenuBarDisplay();
  }
});

function showContextMenu(e, path) {
  e.preventDefault();
  e.stopPropagation();

  const ctxMenu = document.getElementById('ctxMenu');
  ctxPath.value = path;
  
  updateContextButtons('block', path, 'ctxMenu');
  
  ctxMenu.style.display = 'block';
  ctxMenu.style.left = e.pageX + 'px';
  ctxMenu.style.top = e.pageY + 'px';
}

document.addEventListener('click', () => ctxMenu.style.display = 'none');

async function triggerRename() {
  const oldPath = ctxPath.value;
  const oldName = oldPath.split('/').pop();
  const newName = prompt('New name:', oldName);
  if (!newName) return;

  if (newName === oldName) {
    hideSpinner();
    alert("❗ The new name is the same as the current one. Please enter a different name.");
    return;
  }

  const dir = oldPath.substring(0, oldPath.lastIndexOf('/')) || '/';
  const newPath = dir.replace(/\/+$/, '') + '/' + newName;

  const res = await fetch('', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ check_exists: newPath })
  });

  const data = await res.json();

  if (data.exists) {
    pendingConflict = {
      from: oldPath,
      toDir: dir,
      suggested: data.suggested,
      mode: 'rename',
      userInput: newName
    };
    hideSpinner();
    document.getElementById('conflictModal').style.display = 'flex';
  } else {
    hideSpinner();
    document.getElementById('ctxRenameOld').value = oldPath;
    document.getElementById('ctxRenameNew').value = newName;
    document.getElementById('ctxForm').submit();
  }
}

function initModalFolderClicks() {
  const nodes = document.querySelectorAll('#modalFolderTree .folder-row');
  nodes.forEach(row => {
    row.addEventListener('click', () => {
      const path = row.dataset.path;
      document.getElementById('modalTarget').value = path;

      // Optional: visuelle Markierung setzen
      document.querySelectorAll('#modalFolderTree .folder-row').forEach(r => r.style.background = '');
      row.style.background = '#e6f0fa';
    });
  });
}

function triggerDelete() {
  const selected = [...document.querySelectorAll('tr.selected')].map(row => row.dataset.path);

  if (selected.length === 0) {
    selected.push(ctxPath.value); // fallback: only right-clicked item
  }

  if (!confirm(`Really delete ${selected.length} file(s)/folder(s)?`)) return;

  const formData = new URLSearchParams();
  formData.append('delete_bulk', JSON.stringify(selected));

  fetch('', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: formData
  })
    .then(() => {
      location.reload();
    })
    .catch(() => {
      hideSpinner();
      showErrorModal("❌ Error during deletion.");
    });
}

function triggerCopy() {
  const selectedPaths = [...document.querySelectorAll('tr.selected')].map(row => row.dataset.path);
  if (!selectedPaths.length) {
    alert("❗ Please select at least one file or folder.");
    return;
  }

  window.bulkCopySelection = selectedPaths;

  fetch('?action=subfolders&path=/&current_path=<?= urlencode($path) ?>')
    .then(res => res.text())
    .then(html => {
      const container = document.getElementById('copyTargetTree');
      container.innerHTML = html;

      // 🔒 Deactivate links
      container.querySelectorAll('a').forEach(a => {
        a.removeAttribute('href');
        a.style.cursor = 'default';
        a.addEventListener('click', e => e.preventDefault());
      });

      // Default selection
      let selectedTarget = '/';

	// Delegated click handler for all folder rows (including dynamically loaded)
	container.addEventListener('click', e => {
	  const row = e.target.closest('.folder-row');
	  if (row) {
		// ✳️ Entferne alte Markierung
		container.querySelectorAll('.folder-row').forEach(r => {
		  r.classList.remove('selected-folder');
		  r.style.fontWeight = '';
		});
	
		// ✳️ Neue Markierung setzen
		row.classList.add('selected-folder');
		row.style.fontWeight = 'bold';
	
		selectedTarget = row.dataset.path;
	  }
	});

      // 🔁 Remove old footer if it exists
      const existingFooter = container.parentElement.querySelector('.modal-footer');
      if (existingFooter) existingFooter.remove();

      // ✅ Add new footer
      const footerDiv = document.createElement('div');
      footerDiv.className = 'modal-footer';
      footerDiv.style = 'text-align:right; margin-top:10px;';
      footerDiv.innerHTML = `
        <button onclick="document.getElementById('copySidebarModal').style.display='none'">Cancel</button>
        <button id="confirmCopyBtn" style="margin-left:10px;">OK</button>
      `;
      container.parentElement.appendChild(footerDiv);
      
	  document.getElementById('confirmCopyBtn').addEventListener('click', () => {
		confirmCopyTo(selectedTarget);
	  });

      document.getElementById('copySidebarModal').style.display = 'flex';
    })
    .catch(err => {
      console.error("❌ Failed to load folder tree:", err);
      showErrorModal("❌ Could not load folder tree.");
    });
}

async function confirmCopyTo(toDir) {
  const selected = window.bulkCopySelection;
  if (!selected || !Array.isArray(selected)) return;

  showSpinner();
  const conflicts = [];

  for (const fromPath of selected) {
    const name = fromPath.split('/').pop();
    const toPath = toDir.replace(/\/+$/, '') + '/' + name;

    // Check Konflikte
    const checkRes = await fetch('', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ check_exists: toPath })
    });
    const checkData = await checkRes.json();

    if (checkData.exists) {
      conflicts.push({ from: fromPath, suggested: checkData.suggested });
      continue;
    }

    // Copy ausführen
    const res = await fetch('', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ copy_from: fromPath, copy_to: toPath, ajax: '1' })
    });

    const data = await res.json();
    if (!data.success) {
      hideSpinner();
      showErrorModal(`❌ Cannot copy ${fromPath}`);
      return;
    }
  }

  if (conflicts.length > 0) {
    const fromList = conflicts.map(c => c.from);
    const choices = {};
    conflicts.forEach(c => choices[c.from] = { newName: c.suggested });

    pendingConflict = {
      from: fromList[0],
      toDir: toDir,
      suggested: conflicts[0].suggested,
      mode: 'copy',
      userInput: fromList[0].split('/').pop(),
      fromList,
      choices
    };

    hideSpinner();
    document.getElementById('conflictModal').style.display = 'flex';
    return;
  }

  hideSpinner();
  window.location.href = '?path=' + encodeURIComponent(toDir);
}

function triggerMove() {
  const selectedPaths = [...document.querySelectorAll('tr.selected')].map(row => row.dataset.path);
  if (!selectedPaths.length) {
    alert("❗ Please select at least one file or folder.");
    return;
  }

  window.bulkMoveSelection = selectedPaths;

  fetch('?action=subfolders&path=/&current_path=<?= urlencode($path) ?>')
    .then(res => res.text())
    .then(html => {
      const container = document.getElementById('moveTargetTree');
      container.innerHTML = html;

      // 🔒 Deactivate links
      container.querySelectorAll('a').forEach(a => {
        a.removeAttribute('href');
        a.style.cursor = 'default';
        a.addEventListener('click', e => e.preventDefault());
      });

      // Default selection
      let selectedTarget = '/';

	// Delegated click handler for all folder rows (including dynamically loaded)
	container.addEventListener('click', e => {
	  const row = e.target.closest('.folder-row');
	  if (row) {
		// ✳️ Entferne alte Markierung
		container.querySelectorAll('.folder-row').forEach(r => {
		  r.classList.remove('selected-folder');
		  r.style.fontWeight = '';
		});
	
		// ✳️ Neue Markierung setzen
		row.classList.add('selected-folder');
		row.style.fontWeight = 'bold';
	
		selectedTarget = row.dataset.path;
	  }
	});

      // 🔁 Remove old footer if it exists
      const existingFooter = container.parentElement.querySelector('.modal-footer');
      if (existingFooter) existingFooter.remove();

      // ✅ Add new footer
      const footerDiv = document.createElement('div');
      footerDiv.className = 'modal-footer';
      footerDiv.style = 'text-align:right; margin-top:10px;';
      footerDiv.innerHTML = `
        <button onclick="document.getElementById('moveSidebarModal').style.display='none'">Cancel</button>
        <button id="confirmMoveBtn" style="margin-left:10px;">OK</button>
      `;
      container.parentElement.appendChild(footerDiv);
      
      document.getElementById('confirmMoveBtn').addEventListener('click', () => {
        confirmMoveTo(selectedTarget);
      });

      document.getElementById('moveSidebarModal').style.display = 'flex';
    })
    .catch(err => {
      console.error("❌ Failed to load folder tree:", err);
      showErrorModal("❌ Could not load folder tree.");
    });
}

async function confirmMoveTo(toDir) {
  const fromList = window.bulkMoveSelection || [];

  showSpinner();
  const conflicts = [];
  const toMove = [];

  for (const fromPath of fromList) {
    const name = fromPath.split('/').pop();
    const toPath = toDir.replace(/\/+$/, '') + '/' + name;

    if (toPath === fromPath || toPath.startsWith(fromPath + '/')) {
      hideSpinner();
      showErrorModal(`❌ Cannot move '${fromPath}' into itself or a subfolder.`);
      return;
    }

    const checkRes = await fetch('', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ check_exists: toPath })
    });

    const checkData = await checkRes.json();

    if (checkData.exists) {
      conflicts.push({ from: fromPath, suggested: checkData.suggested });
    } else {
      toMove.push({ from: fromPath, to: toPath });
    }
  }

  for (const item of toMove) {
    const res = await fetch('', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        move_from: item.from,
        move_to: item.to,
        ajax: '1'
      })
    });

    const data = await res.json();
    if (!data.success) {
      hideSpinner();
      showErrorModal(`❌ Cannot move ${item.from}`);
      return;
    }
  }

  if (conflicts.length > 0) {
    const fromList = conflicts.map(c => c.from);
    const choices = {};
    conflicts.forEach(c => {
      choices[c.from] = { newName: c.suggested };
    });

    pendingConflict = {
      from: fromList[0],
      toDir: toDir,
      suggested: conflicts[0].suggested,
      mode: 'move',
      userInput: fromList[0].split('/').pop(),
      fromList,
      choices
    };

    hideSpinner();
    document.getElementById('conflictModal').style.display = 'flex';
    return;
  }

  hideSpinner();
  window.location.href = '?path=' + encodeURIComponent(toDir);
}

async function confirmMoveToFromDrop(fromPaths, toDir) {
  if (!Array.isArray(fromPaths) || !fromPaths.length || !toDir) {
    showErrorModal("❌ Nothing selected or invalid target directory.");
    return;
  }

  showSpinner();
  const conflicts = [];

  for (const fromPath of fromPaths) {
    const name = fromPath.split('/').pop();
    const toPath = toDir.replace(/\/+$/, '') + '/' + name;

    if (toPath === fromPath || toPath.startsWith(fromPath + '/')) {
      hideSpinner();
      showErrorModal("❌ Cannot move into same folder or subfolder.");
      return;
    }

    // Check for conflicts
    const checkRes = await fetch('', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ check_exists: toPath })
    });
    const checkData = await checkRes.json();

    if (checkData.exists) {
      conflicts.push({ from: fromPath, suggested: checkData.suggested });
      continue;
    }

    // Perform move
    const res = await fetch('', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        move_from: fromPath,
        move_to: toPath,
        ajax: '1'
      })
    });

    const data = await res.json();
    if (!data.success) {
      hideSpinner();
      showErrorModal(`❌ Cannot move ${fromPath}`);
      return;
    }
  }

  if (conflicts.length > 0) {
    const conflictFromList = conflicts.map(c => c.from);
    const choices = {};
    conflicts.forEach(c => {
      choices[c.from] = { newName: c.suggested };
    });

    pendingConflict = {
      from: conflictFromList[0],
      toDir: toDir,
      suggested: conflicts[0].suggested,
      mode: 'move',
      userInput: conflictFromList[0].split('/').pop(),
      fromList: conflictFromList,
      choices
    };

    hideSpinner();
    document.getElementById('conflictModal').style.display = 'flex';
    return;
  }

  hideSpinner();
  window.location.href = '?path=' + encodeURIComponent(toDir);
}



let pendingConflict = {
  from: '',
  toDir: '',
  suggested: ''
};

async function resolveConflict(action) {
  if (!pendingConflict || !Array.isArray(pendingConflict.fromList)) return;

  showSpinner();

  const toDir = pendingConflict.toDir;
  const mode = pendingConflict.mode;

  for (const from of pendingConflict.fromList) {
    const baseName = from.split('/').pop();
    let name = baseName;

    if (action === 'rename') {
      const nameParts = baseName.split('.');
      const base = nameParts.slice(0, -1).join('.') || nameParts[0];
      const ext = nameParts.length > 1 ? '.' + nameParts[nameParts.length - 1] : '';
      let counter = 1;
      let testName = `${base}_${counter}${ext}`;
      let exists = true;

      while (exists) {
        const checkPath = toDir.replace(/\/+$/, '') + '/' + testName;
        try {
          const check = await fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
              ajax: '1',
              check_exists: checkPath
            })
          });
          const result = await check.json();
          exists = result.exists;
          if (exists) {
            counter++;
            testName = `${base}_${counter}${ext}`;
          }
        } catch {
          exists = false;
        }
      }

      name = testName;
    }

    const to = toDir.replace(/\/+$/, '') + '/' + name;

    if (from === to || (mode !== 'rename' && to.startsWith(from + '/'))) {
      console.warn(`⚠️ Invalid move/copy: ${from} → ${to}`);
      continue;
    }

    const formData = new URLSearchParams();
    if (mode === 'copy') {
      formData.append('copy_from', from);
      formData.append('copy_to', to);
    } else if (mode === 'move') {
      formData.append('move_from', from);
      formData.append('move_to', to);
    } else if (mode === 'rename') {
      formData.append('rename_old', from);
      formData.append('rename_new', to);
    }
    formData.append('ajax', '1');

    try {
      const res = await fetch('', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData
      });
      const data = await res.json();
      if (!data.success) {
        showErrorModal(data.error || `❌ Failed: ${from}`);
        break;
      }
    } catch (e) {
      showErrorModal('❌ Server response is not valid JSON.');
      break;
    }
  }

  hideSpinner();
  pendingConflict = null;
  document.getElementById('conflictModal').style.display = 'none';

  window.location.href = '?path=' + encodeURIComponent(toDir);
}

function showRenameInput() {
  document.getElementById('conflictModal').style.display = 'none';
  resolveConflict('rename');
}

async function triggerZip() {
  const selected = [...document.querySelectorAll('tr.selected')].map(row => row.dataset.path);
  if (selected.length === 0) {
    alert("❗ Please select at least one file or folder.");
    return;
  }

  if (!confirm('Create ZIP archive from selection?')) return;
  showSpinner();

  const res = await fetch('', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({
      zip_bulk: JSON.stringify(selected),
      ajax: 1
    })
  });

  try {
    const data = await res.json();
    if (data.success) {
      window.location.href = '?path=' + encodeURIComponent(data.path || '<?= $path ?>');
    } else {
      hideSpinner();
      showErrorModal(data.error || '❌ ZIP creation failed.');
    }
  } catch (e) {
    hideSpinner();
    showErrorModal('❌ Server response is not valid JSON.');
  }
}

async function triggerUnzip() {
  try {
    showSpinner();
    const res = await fetch('', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ unzip_file: ctxPath.value, ajax: 1 })
    });

    const raw = await res.text();
    console.log("📦 RAW UNZIP RESPONSE:\n", raw);

    if (!raw.trim().startsWith('{')) {
      throw new Error('Response is not JSON (maybe session timeout or error page?)');
    }

    const data = JSON.parse(raw);

    if (data.success) {
      window.location.href = '?path=' + encodeURIComponent(data.path || '<?= $path ?>');
    } else {
      hideSpinner();
      showErrorModal(data.error || '❌ Unzipping failed.');
    }
  } catch (e) {
    hideSpinner();
    showErrorModal('❌ Server response is not valid JSON: ' + e.message);
  }
}

function triggerCreateFolderBar() {
  const targetPath = document.getElementById('ctxNewFolderPath').value;
  const folderName = prompt("New folder in directory " + targetPath);
  if (!folderName) return;
  document.getElementById('ctxNewFolderInput').value = folderName.trim();
  document.getElementById('ctxFormBarParent').submit();
}

function triggerCreateFolder() {
  const fullPath = ctxPath.value;
  const row = document.querySelector(`[data-path="${CSS.escape(fullPath)}"]`);
  if (!row) return;

  const type = row.dataset.type;
  const folderName = prompt("New folder in directory " + fullPath);
  if (!folderName) return;

  let folderPath = fullPath;
  if (type === 'file') {
    folderPath = fullPath.substring(0, fullPath.lastIndexOf('/')) || '/';
  }

  const form = document.createElement("form");
  form.method = "POST";
  form.action = "";

  const inputName = document.createElement("input");
  inputName.type = "hidden";
  inputName.name = "new_folder";
  inputName.value = folderName;

  const inputPath = document.createElement("input");
  inputPath.type = "hidden";
  inputPath.name = "new_folder_path";
  inputPath.value = folderPath;

  form.appendChild(inputName);
  form.appendChild(inputPath);
  document.body.appendChild(form);
  form.submit();
}

function triggerDownload() {
  const selected = [...document.querySelectorAll('tr.selected')].map(row => row.dataset.path);
  if (selected.length === 0) {
    alert("❗ Please select a file to download.");
    return;
  }

  if (selected.length > 1) {
    alert("❗ Please select only one file.");
    return;
  }

  const form = document.createElement('form');
  form.method = 'GET';
  form.action = '';
  form.style.display = 'none';

  const input = document.createElement('input');
  input.name = 'download';
  input.value = selected[0]; // Nur ein Pfad
  form.appendChild(input);

  document.body.appendChild(form);
  form.submit();
}

function triggerDownloadZip() {
  const selected = [...document.querySelectorAll('tr.selected')].map(row => row.dataset.path);
  if (selected.length !== 1) {
    alert("❗ Please select exactly one folder to download as ZIP.");
    return;
  }

  const path = selected[0];
  const el = document.querySelector(`tr[data-path="${CSS.escape(path)}"]`);
  if (!el || el.dataset.type !== 'dir') {
    alert("❗ Selected item is not a folder.");
    return;
  }

  const form = document.createElement('form');
  form.method = 'GET';
  form.action = '';
  form.style.display = 'none';

  const input = document.createElement('input');
  input.name = 'download_zip';
  input.value = path;
  form.appendChild(input);

  document.body.appendChild(form);
  form.submit();
}

function triggerDownloadSelectedZip() {
  const selected = [...document.querySelectorAll('tr.selected')].map(row => row.dataset.path);
  if (selected.length === 0) {
    hideSpinner();
    alert("❗ Please select at least one file or folder.");
    return;
  }

  const form = document.createElement('form');
  form.method = 'POST';
  form.action = '';
  form.style.display = 'none';

  const input = document.createElement('input');
  input.name = 'download_bulk';
  input.value = JSON.stringify(selected);
  form.appendChild(input);

  document.body.appendChild(form);
  form.submit();
}

function triggerEdit() {
  const selected = document.querySelectorAll('tr.selected');
  if (selected.length !== 1) {
    alert("❗ Please select exactly one file to edit.");
    return;
  }

  const path = selected[0].dataset.path;
  const ext = path.split('.').pop().toLowerCase();
  const editable = ['txt', 'html', 'htm', 'css', 'js', 'php', 'md', 'json', 'xml'];

  if (!editable.includes(ext)) {
    alert("❌ Only text-based files can be edited.");
    return;
  }

  const editorModal = document.getElementById('editorModal');
  const editorFilename = document.getElementById('editorFilename');
  const editorContent = document.getElementById('editorContent');
  const editorFile = document.getElementById('editorFile');

  showSpinner();

  fetch(`?load=${encodeURIComponent(path)}`)
    .then(res => res.text())
    .then(text => {
      editorFilename.textContent = "Edit: " + path.split('/').pop();
      editorContent.value = text;
      editorFile.value = path;
      editorModal.style.display = 'flex';
    })
    .catch(() => {
      alert("❌ Failed to load file.");
    })
    .finally(() => {
      hideSpinner();
    });
}

function submitAction(action) {
  if (action === 'copy') {
    const newName = prompt('Target name:');
    if (!newName) return;
    ctxNew.value = newName;
  }
  ctxAction.value = action;

  fetch('', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams(new FormData(document.getElementById('ctxForm')))
  })
    .then(res => res.json())
    .then(data => {
      if (data.success) location.reload();
      else {
        hideSpinner();
        alert('❌ Action failed.');
      }
    });
}

let lastSelectedRow = null;

function toggleSelection(row, event) {
  if (event.button !== 0) return;

  ctxMenu.style.display = 'none';

  const rows = Array.from(document.querySelectorAll('tr[data-path]'));
  const isShift = event.shiftKey;

  if (isShift && lastSelectedRow) {
    const start = rows.indexOf(lastSelectedRow);
    const end = rows.indexOf(row);
    const [min, max] = [Math.min(start, end), Math.max(start, end)];
    rows.forEach((r, i) => {
      r.classList.toggle('selected', i >= min && i <= max);
    });
  } else {
    row.classList.toggle('selected');
    lastSelectedRow = row;
  }

  showContextMenuBar(row.dataset.path);
  toggleContextMenuBarDisplay();

  event.stopPropagation?.();
}

async function bulkMove() {
  const selectedPaths = [...document.querySelectorAll('tr.selected')]
    .map(row => row.dataset.path);

  if (selectedPaths.length === 0) {
    hideSpinner();
    alert("❗ No files or folders selected.");
    return;
  }

  const targetDir = prompt("Enter target folder (e.g. /backup):");
  if (!targetDir) return;

  for (const fromPath of selectedPaths) {
    const fileName = fromPath.split('/').pop();
    const toPath = targetDir.replace(/\/+$/, '') + '/' + fileName;

    const formData = new URLSearchParams();
    formData.append('move_from', fromPath);
    formData.append('move_to', toPath);

    const res = await fetch('', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: formData
    });

    const data = await res.json();
    if (!data.success) {
      hideSpinner();
      showErrorModal("❌ Failed to move: " + fromPath);
      return;
    }
  }

  window.location.href = '?path=' + encodeURIComponent(targetDir);
}

function showErrorModal(message) {
  document.getElementById('errorModalMessage').innerText = message;
  document.getElementById('errorModal').style.display = 'flex';
}

const dropArea = document.getElementById('drop-area');
const fileInput = document.getElementById('fileElem');
let dragCounter = 0;

// Highlight drop area on drag
['dragenter', 'dragover'].forEach(eventName => {
  dropArea.addEventListener(eventName, e => {
    e.preventDefault();
    dragCounter++;
    dropArea.classList.add('highlight');
  }, false);
});

// Remove highlight on drop/leave
['dragleave', 'drop'].forEach(eventName => {
  dropArea.addEventListener(eventName, e => {
    e.preventDefault();
    dragCounter--;
    if (dragCounter === 0) {
      dropArea.classList.remove('highlight');
    }
  }, false);
});

// Handle drop event: assign files and submit form
dropArea.addEventListener('drop', e => {
  e.preventDefault();
  const dt = e.dataTransfer;
  const files = dt.files;
  if (files.length > 0) {
    fileInput.files = files;
    fileInput.form.submit(); // 🔁 auto-submit
  }
});

// Lightbox drag/swipe overlay
const lightboxOverlay = document.getElementById('lightbox-overlay');

if (lightboxOverlay) {
  lightboxOverlay.addEventListener('mousedown', function(e) {
    isDragging = true;
    dragStartX = e.clientX;
    dragEndX = dragStartX;
  });

  lightboxOverlay.addEventListener('mousemove', function(e) {
    if (isDragging) {
      dragEndX = e.clientX;
    }
  });

  lightboxOverlay.addEventListener('mouseup', function(e) {
    if (isDragging) {
      isDragging = false;
      dragEndX = e.clientX;
      handleMouseSwipe();
    }
  });

  lightboxOverlay.addEventListener('touchstart', function(e) {
    startX = e.changedTouches[0].screenX;
  });

  lightboxOverlay.addEventListener('touchend', function(e) {
    endX = e.changedTouches[0].screenX;
    handleSwipe();
  });

  lightboxOverlay.addEventListener('touchmove', function() {
    if (!suppressIconShow) showIcons();
  });
}

let lightboxImages = [];
let currentIndex = 0;
let startX = 0;
let endX = 0;
let isDragging = false;
let dragStartX = 0;
let dragEndX = 0;
let suppressIconShow = false;
let iconTimer = null;

window.lightboxImageList = <?= json_encode(
  array_values(
    array_filter($entries, function($e) {
        $ext = strtolower(pathinfo($e['name'], PATHINFO_EXTENSION));
        return !$e['is_dir'] && in_array($ext, ['jpg','jpeg','png','gif','svg','webp']);
    })
  ),
  JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) ?>.map(e => '?preview=<?= $path ?>/' + encodeURIComponent(e.name));

function openLightbox(url) {
  lightboxImages = window.lightboxImageList || [];
  currentIndex = lightboxImages.indexOf(url);
  if (currentIndex === -1) currentIndex = 0;

  const img = document.getElementById('lightbox-img');
  const loading = document.getElementById('loading');

  img.style.display = 'none';
  loading.style.display = 'block';
  document.getElementById('lightbox').style.display = 'flex';

  img.onload = () => {
    loading.style.display = 'none';
    img.style.display = 'block';
  };

  img.src = lightboxImages[currentIndex];
  document.addEventListener('keydown', handleKeyPress);
}

function closeLightbox() {
  document.getElementById('lightbox').style.display = 'none';
  document.getElementById('lightbox-overlay').style.display = 'none';
  document.removeEventListener('keydown', handleKeyPress);
}

function nextImage(event) {
  event?.stopPropagation();
  currentIndex = (currentIndex + 1) % lightboxImages.length;
  openLightbox(lightboxImages[currentIndex]);
}

function prevImage(event) {
  event?.stopPropagation();
  currentIndex = (currentIndex - 1 + lightboxImages.length) % lightboxImages.length;
  openLightbox(lightboxImages[currentIndex]);
}

function handleSwipe() {
  const threshold = 50;
  const distance = endX - startX;
  if (Math.abs(distance) > threshold) {
    animateImageSwipe(distance > 0 ? 'right' : 'left');
  }
}

function handleMouseSwipe() {
  const threshold = 50;
  const distance = dragEndX - dragStartX;
  if (Math.abs(distance) > threshold) {
    animateImageSwipe(distance > 0 ? 'right' : 'left');
  }
}

function animateImageSwipe(direction) {
  const img = document.getElementById('lightbox-img');
  const offset = direction === 'right' ? '100%' : '-100%';

  img.style.transition = 'transform 0.3s ease';
  img.style.transform = `translateX(${offset})`;

  setTimeout(() => {
    if (direction === 'right') {
      prevImage();
    } else {
      nextImage();
    }

    img.style.transition = 'none';
    img.style.transform = `translateX(${direction === 'right' ? '-100%' : '100%'})`;

    setTimeout(() => {
      img.style.transition = 'transform 0.3s ease';
      img.style.transform = 'translateX(0)';
    }, 20);
  }, 300);
}

function handleKeyPress(e) {
  if (e.key === "ArrowRight") {
    suppressIconShow = true;
    nextImage();
    setTimeout(() => { suppressIconShow = false; }, 300);
  } else if (e.key === "ArrowLeft") {
    suppressIconShow = true;
    prevImage();
    setTimeout(() => { suppressIconShow = false; }, 300);
  } else if (e.key === "Escape") {
    closeLightbox();
  }
}

function startIconTimer() {
  clearTimeout(iconTimer);
  iconTimer = setTimeout(() => {
    hideIcons();
  }, 3000);
}

function hideIcons() {
  document.querySelectorAll('.close, .prev, .next').forEach(btn => {
    btn.style.opacity = '0';
    btn.style.pointerEvents = 'none';
  });
}

function showIcons() {
  document.querySelectorAll('.close, .prev, .next').forEach(btn => {
    btn.style.opacity = '1';
    btn.style.pointerEvents = 'auto';
  });
  startIconTimer();
}

// Lightbox full area behavior
if (lightbox) {
  lightbox.addEventListener('mousedown', function(e) {
    isDragging = true;
    dragStartX = e.clientX;
    dragEndX = dragStartX;
  });

  lightbox.addEventListener('mousemove', function(e) {
    if (isDragging) {
      dragEndX = e.clientX;
    }
  });

  lightbox.addEventListener('mouseup', function(e) {
    if (isDragging) {
      isDragging = false;
      dragEndX = e.clientX;
      handleMouseSwipe();
    }
  });

  lightbox.addEventListener('mousemove', function(e) {
    if (!isDragging && !suppressIconShow) {
      showIcons();
    }
  });

  lightbox.addEventListener('touchstart', function(e) {
    startX = e.changedTouches[0].screenX;
  });

  lightbox.addEventListener('touchend', function(e) {
    endX = e.changedTouches[0].screenX;
    handleSwipe();
  });

  lightbox.addEventListener('touchmove', function() {
    if (!suppressIconShow) showIcons();
  });

  lightbox.addEventListener('click', function() {
    showIcons();
  });
}

document.querySelector('.lightbox-content').addEventListener('click', function(e) {
  e.stopPropagation();
  showIcons();
});
</script>
</div>
</div>
<?php
echo $footer;
?>
</body>
</html>