<?php
// public/update_theme.php — AJAX endpoint to save user theme preference
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$user = currentUser();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$theme = $data['theme'] ?? 'light';

if (!in_array($theme, ['light', 'dark'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid theme']);
    exit;
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("UPDATE users SET theme = ? WHERE id = ?");
    $stmt->execute([$theme, $user['id']]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
exit;
