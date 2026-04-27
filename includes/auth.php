<?php
// ============================================================
// includes/auth.php — Authentication helpers
// ============================================================

require_once __DIR__ . '/../config/db.php';

/**
 * Start session securely
 */
function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => false,   // Set true in production (HTTPS)
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

/**
 * Check if user is logged in; redirect if not
 */
function requireLogin(): void {
    startSecureSession();
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Return current logged-in user array or null
 */
function currentUser(): ?array {
    startSecureSession();
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        "SELECT u.*, h.name AS hospital_name, h.location AS hospital_location
           FROM users u
           JOIN hospitals h ON h.id = u.hospital_id
          WHERE u.id = ?"
    );
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

/**
 * Register a new doctor
 * Returns ['success' => true] or ['error' => 'message']
 */
function registerUser(string $name, string $email, string $password, int $hospitalId, string $pin, string $role = 'doctor'): array {
    $pdo = getDB();

    // Check duplicate email
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        return ['error' => 'Email already registered.'];
    }

    $hashedPass = password_hash($password, PASSWORD_BCRYPT);
    $hashedPin  = password_hash($pin, PASSWORD_BCRYPT);
    
    $stmt = $pdo->prepare(
        "INSERT INTO users (name, email, password, hospital_id, role, pin) VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$name, $email, $hashedPass, $hospitalId, $role, $hashedPin]);
    return ['success' => true, 'id' => $pdo->lastInsertId()];
}

/**
 * Login user — sets session on success
 * Returns ['success' => true] or ['error' => 'message']
 */
function loginUser(string $email, string $password): array {
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return ['error' => 'Invalid email or password.'];
    }

    startSecureSession();
    session_regenerate_id(true);
    
    // Store user ID in session for MFA verification step
    $_SESSION['mfa_user_id'] = $user['id'];
    
    // In a real system, we'd send the email here. 
    // For now, we'll generate the code and store it.
    $mfaCode = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    $upd = $pdo->prepare("UPDATE users SET mfa_code = ? WHERE id = ?");
    $upd->execute([$mfaCode, $user['id']]);
    
    // Log the initiation
    error_log("MFA Code for User {$user['id']} ({$user['email']}): $mfaCode");

    // DEBUG: Store in session if debug is enabled
    if (getenv('APP_DEBUG') === 'true') {
        $_SESSION['debug_mfa_code'] = $mfaCode;
    }

    return ['success' => true, 'mfa_required' => true];
}

/**
 * Verify MFA code and finalize session
 */
function verifyMfaAndFinalize(string $code): array {
    startSecureSession();
    if (empty($_SESSION['mfa_user_id'])) {
        return ['error' => 'Session expired. Please log in again.'];
    }

    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['mfa_user_id']]);
    $user = $stmt->fetch();

    if (!$user || $user['mfa_code'] !== $code) {
        return ['error' => 'Invalid or expired verification code.'];
    }

    // Success: Set full session
    $_SESSION['user_id']     = $user['id'];
    $_SESSION['user_name']   = $user['name'];
    $_SESSION['user_role']   = $user['role'];
    $_SESSION['hospital_id'] = $user['hospital_id'];
    
    // Clear temporary data
    unset($_SESSION['mfa_user_id']);
    unset($_SESSION['debug_mfa_code']);
    
    $upd = $pdo->prepare("UPDATE users SET mfa_code = NULL WHERE id = ?");
    $upd->execute([$user['id']]);

    return ['success' => true];
}

/**
 * Logout current user
 */
function logoutUser(): void {
    startSecureSession();
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}
