<?php
// public/get_notifications.php — AJAX endpoint for real-time alerts
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$user = currentUser();

$notifications = getUnreadNotifications($user['id']);
$count = count($notifications);

header('Content-Type: application/json');
echo json_encode([
    'count' => $count,
    'notifications' => $notifications
]);
exit;
