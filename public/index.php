<?php
// public/index.php — Entry point, redirect based on auth state
require_once __DIR__ . '/../includes/auth.php';
startSecureSession();

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
