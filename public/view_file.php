<?php
// public/view_file.php — Securely serve files from /uploads
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$user = currentUser();

$path = $_GET['path'] ?? '';
if (!$path) {
    die("File not specified.");
}

// Clean path to prevent traversal
$basename = basename($path);
$fullPath = realpath(__DIR__ . '/../uploads/' . $basename);

if (!$fullPath || !file_exists($fullPath)) {
    die("File not found.");
}

// Security Check: Verify that the user has permission to view the record linked to this file
$pdo = getDB();
$stmt = $pdo->prepare(
    "SELECT * FROM patient_records 
      WHERE file_path = ? 
        AND (sender_id = ? OR receiver_hospital_id = ?)"
);
// Match the relative path stored in DB
$dbPath = 'uploads/' . $basename;
$stmt->execute([$dbPath, $user['id'], $user['hospital_id']]);
$record = $stmt->fetch();

if (!$record) {
    die("Access denied to this file.");
}

// Serve the file
$mime = 'application/pdf';
header("Content-Type: $mime");
header("Content-Disposition: inline; filename=\"$basename\"");
header("Content-Length: " . filesize($fullPath));
readfile($fullPath);
exit;
