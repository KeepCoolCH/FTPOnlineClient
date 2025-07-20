<?php
if (isset($_GET['action']) && $_GET['action'] === 'subfolders') {
    if (ob_get_length()) ob_end_clean();
    header('Content-Type: text/html; charset=utf-8');

    $ftp = ftp_open_connection();
    $path = $_GET['path'] ?? '/';

    echo build_target_folder_tree($ftp, $path, 0, $_GET['current_path'] ?? '', PHP_INT_MAX);
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