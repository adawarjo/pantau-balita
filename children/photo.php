<?php
/**
 * Menyajikan foto anak hanya untuk orangtua pemiliknya.
 * Folder uploads/ ditutup dari akses langsung (.htaccess).
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (!is_logged_in()) {
    http_response_code(403);
    exit;
}
$child = find_child(query_int('id'));
$path = $child ? child_photo_path($child['photo']) : null;
if (!$path || !is_file($path)) {
    http_response_code(404);
    exit;
}
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
    http_response_code(415);
    exit;
}
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, max-age=86400');
header('Content-Disposition: inline');
readfile($path);
