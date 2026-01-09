<?php
// Provide content of a logged-in user's .otrc file

$u = preg_replace('/[^-a-zA-Z0-9_]/', '', $_SERVER['REMOTE_USER'] ?? '');
$d = preg_replace('/[^-a-zA-Z0-9_]/', '', $_GET['d'] ?? '');

if (empty($u) || empty($d)) {
    http_response_code(400);
    echo "invalid request";
    exit;
}

$file = "/usr/local/owntracks/userdata/{$u}-{$d}.otrc";

if (!file_exists($file)) {
    http_response_code(404);
    echo "OTRC not found";
    exit;
}

header("Content-Description: File Transfer");
header("Content-Type: application/json");
header("Content-Disposition: attachment; filename=\"" . basename($file) . "\"");
header("Content-Length: " . filesize($file));

readfile($file);
exit;
?>

