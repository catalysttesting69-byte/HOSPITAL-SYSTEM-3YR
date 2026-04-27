<?php
// ============================================================
// includes/functions.php — General Utility Functions
// ============================================================

require_once __DIR__ . '/../config/db.php';

/**
 * Sanitize a string for safe output in HTML
 */
function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * CSRF Protection: Generate a token and store it in session
 */
function generateCsrfToken(): string {
    startSecureSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF Protection: Verify the token from a request
 */
function verifyCsrfToken(): bool {
    startSecureSession();
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (empty($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}

/**
 * Check if the current user is an admin
 */
function isAdmin(): bool {
    startSecureSession();
    return ($_SESSION['user_role'] ?? '') === 'admin';
}

/**
 * Fetch all hospitals from DB
 */
function getAllHospitals(): array {
    $pdo = getDB();
    return $pdo->query("SELECT * FROM hospitals ORDER BY name ASC")->fetchAll();
}

/**
 * Get a single hospital by ID
 */
function getHospitalById(int $id): ?array {
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT * FROM hospitals WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Upload a PDF file securely to /uploads (outside public).
 * Returns relative file path on success or throws on failure.
 */
function uploadPatientFile(array $fileInput): string {
    if (!isset($fileInput['tmp_name']) || $fileInput['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('File upload error: ' . $fileInput['error']);
    }

    // Validate MIME type
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $fileInput['tmp_name']);
    finfo_close($finfo);

    if ($mimeType !== 'application/pdf') {
        throw new RuntimeException('Only PDF files are accepted.');
    }

    // Validate extension
    $ext = strtolower(pathinfo($fileInput['name'], PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        throw new RuntimeException('File must have a .pdf extension.');
    }

    // Validate size (max 10 MB)
    if ($fileInput['size'] > 10 * 1024 * 1024) {
        throw new RuntimeException('File exceeds 10 MB limit.');
    }

    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0750, true);
    }

    // Generate safe unique filename
    $newName = bin2hex(random_bytes(16)) . '.pdf';
    $dest    = $uploadDir . $newName;

    if (!move_uploaded_file($fileInput['tmp_name'], $dest)) {
        throw new RuntimeException('Failed to move uploaded file.');
    }

    return 'uploads/' . $newName;
}

/**
 * Fetch paginated patient records SENT by a specific user
 */
function getSentRecords(int $userId, int $limit = 20, int $offset = 0): array {
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        "SELECT pr.*, h.name AS receiver_hospital_name
           FROM patient_records pr
           JOIN hospitals h ON h.id = pr.receiver_hospital_id
          WHERE pr.sender_id = ?
          ORDER BY pr.created_at DESC
          LIMIT ? OFFSET ?"
    );
    $stmt->execute([$userId, $limit, $offset]);
    return $stmt->fetchAll();
}

/**
 * Fetch paginated patient records RECEIVED by a hospital
 */
function getReceivedRecords(int $hospitalId, int $limit = 20, int $offset = 0): array {
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        "SELECT pr.*, u.name AS sender_name, h.name AS sender_hospital_name
           FROM patient_records pr
           JOIN users u     ON u.id   = pr.sender_id
           JOIN hospitals h ON h.id   = u.hospital_id
          WHERE pr.receiver_hospital_id = ?
          ORDER BY pr.created_at DESC
          LIMIT ? OFFSET ?"
    );
    $stmt->execute([$hospitalId, $limit, $offset]);
    return $stmt->fetchAll();
}

/**
 * Fetch a single patient record by ID
 */
function getRecordById(int $id): ?array {
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        "SELECT pr.*,
                u.name AS sender_name, u.hospital_id AS sender_hospital_id,
                hs.name AS sender_hospital_name,
                hr.name AS receiver_hospital_name
           FROM patient_records pr
           JOIN users     u  ON u.id  = pr.sender_id
           JOIN hospitals hs ON hs.id = u.hospital_id
           JOIN hospitals hr ON hr.id = pr.receiver_hospital_id
          WHERE pr.id = ?"
    );
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Dashboard summary stats for the logged-in user
 */
function getDashboardStats(int $userId, int $hospitalId): array {
    $pdo = getDB();

    $sent = $pdo->prepare("SELECT COUNT(*) FROM patient_records WHERE sender_id = ?");
    $sent->execute([$userId]);

    $received = $pdo->prepare("SELECT COUNT(*) FROM patient_records WHERE receiver_hospital_id = ?");
    $received->execute([$hospitalId]);

    $pending = $pdo->prepare("SELECT COUNT(*) FROM patient_records WHERE receiver_hospital_id = ? AND status = 'pending'");
    $pending->execute([$hospitalId]);

    $logs = $pdo->prepare("SELECT COUNT(*) FROM audit_logs WHERE performed_by = ?");
    $logs->execute([$userId]);

    return [
        'sent'     => (int) $sent->fetchColumn(),
        'received' => (int) $received->fetchColumn(),
        'pending'  => (int) $pending->fetchColumn(),
        'logs'     => (int) $logs->fetchColumn(),
    ];
}

/**
 * Search patient records by patient name (for sender)
 */
function searchRecords(int $userId, int $hospitalId, string $query): array {
    $pdo  = getDB();
    $like = '%' . $query . '%';

    $stmt = $pdo->prepare(
        "SELECT pr.*, h.name AS receiver_hospital_name, u.name AS sender_name
           FROM patient_records pr
           JOIN hospitals h ON h.id = pr.receiver_hospital_id
           JOIN users u     ON u.id = pr.sender_id
          WHERE (pr.sender_id = ? OR pr.receiver_hospital_id = ?)
            AND pr.patient_name LIKE ?
          ORDER BY pr.created_at DESC
          LIMIT 50"
    );
    $stmt->execute([$userId, $hospitalId, $like]);
    return $stmt->fetchAll();
}

/**
 * Format a timestamp nicely
 */
function formatDate(string $timestamp): string {
    return date('d M Y, H:i', strtotime($timestamp));
}

/**
 * Flash message helpers
 */
function setFlash(string $type, string $message): void {
    startSecureSession();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    startSecureSession();
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * ── NOTIFICATIONS ──────────────────────────────────────
 */
function createNotification(int $userId, string $message, string $link = null): void {
    $pdo  = getDB();
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $message, $link]);
}

function getUnreadNotifications(int $userId): array {
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function markNotificationsRead(int $userId): void {
    $pdo  = getDB();
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$userId]);
}

/**
 * ── PATIENT MANAGEMENT ─────────────────────────────────
 */

function getAllPatients(int $hospitalId): array {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE hospital_id = ? ORDER BY full_name ASC");
    $stmt->execute([$hospitalId]);
    return $stmt->fetchAll();
}

function getPatientByMrn(string $mrn): ?array {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE mrn = ?");
    $stmt->execute([$mrn]);
    return $stmt->fetch() ?: null;
}

function createPatient(string $mrn, string $name, string $dob, string $gender, string $contact, int $hospitalId): int {
    $pdo = getDB();
    $stmt = $pdo->prepare("INSERT INTO patients (mrn, full_name, dob, gender, contact, hospital_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$mrn, $name, $dob, $gender, $contact, $hospitalId]);
    return (int)$pdo->lastInsertId();
}

/**
 * Get total unread notifications count
 */
function getUnreadNotificationsCount(int $userId): int {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}
