<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

$file = basename($_GET['file'] ?? '');
$path = __DIR__ . '/../uploads/nrc/' . $file;

if (empty($file) || !file_exists($path) || !preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $file)) {
    http_response_code(404);
    echo 'Not found.';
    exit;
}

$info = @getimagesize($path);
if (!$info) {
    http_response_code(404);
    echo 'Not found.';
    exit;
}

header('Content-Type: ' . $info['mime']);
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, no-store');
readfile($path);
