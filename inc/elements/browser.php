<!-- Browser -->
<h2>📂 Directory: <?= htmlspecialchars($path) ?></h2>
<?php
if (trim($path, '/') !== '') {
    $parent = dirname($path);
    if ($parent === '.' || $parent === '') $parent = '/';
    echo "<a href='?path=" . urlencode($parent) . "'>⬅️ Parent directory</a><br><br>";
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

$imageEntries = array_filter($entries, function($e) {
    $ext = strtolower(pathinfo($e['name'], PATHINFO_EXTENSION));
    return !$e['is_dir'] && in_array($ext, ['jpg','jpeg','png','gif','svg','webp']);
});

$imagePreviewPaths = array_map(function($e) use ($path) {
    $fullpath = ($path === '/' ? '' : $path) . '/' . $e['name'];
    $fullpath = preg_replace('#/+#', '/', $fullpath);
    return '?preview=' . rawurlencode($fullpath);
}, $imageEntries);

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
			$thumbUrl = '?preview=' . rawurlencode($fullpath);
			$escaped = htmlspecialchars($thumbUrl, ENT_QUOTES);
			$nameLink = "<a href=\"javascript:void(0);\" onclick=\"openLightbox('$escaped')\" style=\"text-decoration: none; color: inherit;\">" . htmlspecialchars($name) . "</a>";
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
<script>
window.lightboxImageList = <?= json_encode(
    array_values(
        array_map(function($e) use ($path) {
            $name = $e['name'];
            $full = ($path === '/' ? '' : $path) . '/' . $name;
            return '?preview=' . rawurlencode($full);
        }, array_filter($entries, function($e) {
            return !$e['is_dir'] && in_array(
                strtolower(pathinfo($e['name'], PATHINFO_EXTENSION)),
                ['jpg','jpeg','png','gif','svg','webp']
            );
        }))
    )
); ?>;
</script>
