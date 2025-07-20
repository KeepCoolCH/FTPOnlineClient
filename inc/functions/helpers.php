<?php
// Create temp folder
$localTempDir = __DIR__ . '/../.temp';
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

// RAW-List
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