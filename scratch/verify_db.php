<?php
require_once __DIR__ . '/../includes/functions.php';

try {
    $pdo = getDB();
    echo "--- Users List ---\n";
    $stmt = $pdo->query("SELECT id, name, email, role FROM users");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']} | Name: {$row['name']} | Email: {$row['email']} | Role: {$row['role']}\n";
    }
    
    // Set first user to admin if any
    $pdo->exec("UPDATE users SET role = 'admin' LIMIT 1");
    echo "\nUpdated first user to 'admin'.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
