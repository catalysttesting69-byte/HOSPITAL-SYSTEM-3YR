<?php
// public/verify_mfa.php — Second step of login
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();

// If no user is in the MFA middle-state, redirect to login
if (empty($_SESSION['mfa_user_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Security token mismatch.';
    } else {
        $code = trim($_POST['mfa_code'] ?? '');
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND mfa_code = ?");
        $stmt->execute([$_SESSION['mfa_user_id'], $code]);
        $user = $stmt->fetch();

        if ($user) {
            // Success! Finalize the login
            finalizeLogin($user['id']);
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid or expired verification code.';
        }
    }
}

$pageTitle = 'Verify Identity';
// We use a simplified layout or just include head
require_once __DIR__ . '/../includes/layout_head.php';
?>

<div style="min-height: 80vh; display: flex; align-items: center; justify-content: center; padding: 20px;">
    <div class="glass-card" style="width: 100%; max-width: 400px; padding: 40px; text-align: center;">
        <div style="width: 64px; height: 64px; background: rgba(59,130,246,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
            <svg width="32" height="32" fill="none" stroke="var(--blue)" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>

        <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--txt); margin-bottom: 8px;">Check your email</h1>
        <p style="font-size: 0.875rem; color: var(--muted); margin-bottom: 32px; line-height: 1.5;">
            We've sent a 6-digit verification code to your registered email address. Please enter it below to continue.
        </p>

        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom: 24px; font-size: 0.8rem;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <div class="form-group" style="margin-bottom: 24px;">
                <input type="text" name="mfa_code" class="form-input" 
                       style="text-align: center; font-size: 1.5rem; letter-spacing: 0.5em; padding: 16px; font-weight: 700;" 
                       placeholder="000000" maxlength="6" pattern="[0-9]{6}" required autofocus autocomplete="one-time-code">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 14px;">
                Verify & Login
            </button>
        </form>

        <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--border);">
            <p style="font-size: 0.75rem; color: var(--muted);">
                Didn't receive the code? Check your spam folder or 
                <a href="login.php" style="color: var(--blue); font-weight: 600;">try logging in again</a>.
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
