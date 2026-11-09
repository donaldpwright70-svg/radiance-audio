<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

$u = user();
if (!$u || $u['account_status'] !== 'Approved' || !in_array($u['role'], ['business', 'artist', 'admin'], true)) {
    http_response_code(403);
    exit('Sign in to access audio.');
}

$id = (int) ($_GET['track'] ?? 0);
$sid = (int) ($_GET['submission'] ?? 0);
$row = null;

if ($id) {
    $q = db()->prepare('SELECT * FROM tracks WHERE id=?');
    $q->execute([$id]);
    $row = $q->fetch();
    if (!$row || !$row['filename'] || ($u['role'] === 'business' && !(int) $row['published']) || ($u['role'] === 'artist')) {
        $row = null;
    }
} elseif ($sid && in_array($u['role'], ['admin', 'artist'], true)) {
    $q = db()->prepare('SELECT * FROM submissions WHERE id=?');
    $q->execute([$sid]);
    $row = $q->fetch();
    if ($row && $u['role'] === 'artist' && (int) $row['user_id'] !== (int) $u['id']) {
        $row = null;
    }
}

if (!$row) {
    http_response_code(404);
    exit('Audio not available.');
}

$file = STORAGE . '/uploads/' . basename($row['filename']);
if (!is_file($file)) {
    http_response_code(404);
    exit('Audio file not found.');
}

session_write_close();
$size = filesize($file);
$start = 0;
$end = $size - 1;

header('Content-Type: ' . $row['mime']);
header('Accept-Ranges: bytes');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');

if (isset($_SERVER['HTTP_RANGE'])) {
    if (!preg_match('/^bytes=(\d*)-(\d*)$/', $_SERVER['HTTP_RANGE'], $m) || ($m[1] === '' && $m[2] === '')) {
        http_response_code(416);
        header('Content-Range: bytes */' . $size);
        exit;
    }
    if ($m[1] === '') {
        $suffix = (int) $m[2];
        $start = max(0, $size - $suffix);
    } else {
        $start = (int) $m[1];
        if ($m[2] !== '') {
            $end = min($end, (int) $m[2]);
        }
    }
    if ($start > $end || $start >= $size) {
        http_response_code(416);
        header('Content-Range: bytes */' . $size);
        exit;
    }
    http_response_code(206);
    header("Content-Range: bytes $start-$end/$size");
}

header('Content-Length: ' . ($end - $start + 1));
if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
    exit;
}

$f = fopen($file, 'rb');
fseek($f, $start);
$remaining = $end - $start + 1;
while ($remaining > 0 && !feof($f)) {
    $chunk = fread($f, min(65536, $remaining));
    if ($chunk === false) {
        break;
    }
    echo $chunk;
    $remaining -= strlen($chunk);
}
fclose($f);
