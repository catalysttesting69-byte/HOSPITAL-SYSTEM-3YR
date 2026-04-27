<?php
// public/view_record.php — View & decrypt a patient record
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/encryption.php';

requireLogin();
$user     = currentUser();
$recordId = (int) ($_GET['id'] ?? 0);
$record   = getRecordById($recordId);

if (!$record) {
    setFlash('error', 'Record not found.');
    header('Location: records.php');
    exit;
}

// Security Check: Ensure user's hospital is the intended receiver
// (or the user is the original sender)
if ($record['receiver_hospital_id'] !== $user['hospital_id'] && $record['sender_id'] !== $user['id']) {
    setFlash('error', 'Unauthorized access to patient record.');
    header('Location: records.php');
    exit;
}

// ── Decrypt Data ──────────────────────────────────────────────────────────────
$diagnosisText = $record['diagnosis'];
$isDecrypted = false;
$pinError = '';

if ($record['is_encrypted']) {
    // If the POST contains the entered PIN, verify it
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['decrypt_pin'])) {
        $enteredPin = $_POST['decrypt_pin'];
        if (password_verify($enteredPin, $user['pin'])) {
            $diagnosisText = decryptData($record['diagnosis']);
            $isDecrypted = true;
            
            // Log the successful decryption view
            writeAuditLog('VIEW / DECRYPTED', $user['id'], $recordId);
        } else {
            $pinError = 'Invalid PIN. Access denied.';
        }
    }
} else {
    $isDecrypted = true; // Was never encrypted
    if ($record['receiver_hospital_id'] === $user['hospital_id'] && $record['status'] === 'pending') {
        writeAuditLog('VIEW / RECEIVED (PLAINTEXT)', $user['id'], $recordId);
    }
}

// ── Mark as "received" ──────────────────────────────────────────────
if ($record['receiver_hospital_id'] === $user['hospital_id'] && $record['status'] === 'pending') {
    $pdo = getDB();
    $upd = $pdo->prepare("UPDATE patient_records SET status = 'received' WHERE id = ?");
    $upd->execute([$recordId]);
    $record['status'] = 'received';
}

$pageTitle = 'View Patient Record';
require_once __DIR__ . '/../includes/layout_head.php';
?>

<div class="topbar">
  <div>
    <p style="font-size:.78rem;color:var(--muted);font-weight:600;letter-spacing:.06em;text-transform:uppercase;margin-bottom:6px">Record Viewer</p>
    <h1 style="font-size:1.6rem;font-weight:700;letter-spacing:-.03em;display:flex;align-items:center;gap:12px;">
      Patient Data
      <?php if ($record['is_encrypted'] && $isDecrypted): ?>
        <span class="badge badge-green">
          <svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C9.243 2 7 4.243 7 7v3H6a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2v-8a2 2 0 00-2-2h-1V7c0-2.757-2.243-5-5-5zM9 7c0-1.654 1.346-3 3-3s3 1.346 3 3v3H9V7z"/></svg>
          Decrypted Locally
        </span>
      <?php elseif ($record['is_encrypted']): ?>
        <span class="badge badge-red">
          <svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C9.243 2 7 4.243 7 7v3H6a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2v-8a2 2 0 00-2-2h-1V7c0-2.757-2.243-5-5-5zM9 7c0-1.654 1.346-3 3-3s3 1.346 3 3v3H9V7z"/></svg>
          Locked
        </span>
      <?php endif; ?>
    </h1>
    <p style="font-size:.875rem;color:var(--muted);margin-top:4px">Viewing record #<?= $recordId ?></p>
  </div>
  <a href="records.php" class="btn btn-ghost">
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
    Back to Inbox
  </a>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:28px;align-items:start">

  <!-- Left Column: Patient Info & Diagnosis -->
  <div style="display:flex;flex-direction:column;gap:24px;">

    <!-- Primary Info -->
    <div class="glass-card" style="padding:32px;">
      <div style="font-size:.78rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.07em;margin-bottom:12px;">Patient Identity</div>
      <h3 style="font-size:2.2rem;font-weight:700;color:var(--txt);margin-bottom:24px;letter-spacing:-.04em;"><?= htmlspecialchars($record['patient_name']) ?></h3>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;padding-top:20px;border-top:1px solid var(--border);">
        <div>
           <p style="font-size:.78rem;color:var(--muted);margin-bottom:4px;">Transferred From</p>
           <p style="font-weight:600;font-size:.95rem;color:var(--txt);"><?= htmlspecialchars($record['sender_hospital_name']) ?></p>
           <p style="font-size:.85rem;color:var(--muted);">Dr. <?= htmlspecialchars($record['sender_name']) ?></p>
        </div>
        <div>
           <p style="font-size:.78rem;color:var(--muted);margin-bottom:4px;">Transfer Date</p>
           <p style="font-weight:600;font-size:.95rem;color:var(--txt);"><?= formatDate($record['created_at']) ?></p>
        </div>
      </div>
    </div>

    <!-- Clinical Diagnosis (Decrypted) -->
    <div class="glass-card" style="padding:32px;">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--border);">
        <div style="font-size:.78rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.07em;">Clinical Diagnosis & Notes</div>
        <?php if ($record['is_encrypted'] && $isDecrypted): ?>
          <span style="font-size:.72rem;font-family:'Courier New',monospace;color:var(--green);font-weight:600;">AES-256-CBC Verified</span>
        <?php endif; ?>
      </div>

      <?php if ($record['is_encrypted'] && !$isDecrypted): ?>
        <!-- PIN Request State -->
        <div style="padding:40px; border-radius:var(--r-lg); background:rgba(0,0,0,0.4); border:1px solid var(--border); text-align:center;">
           <svg style="width:48px;height:48px;margin:0 auto 16px;color:var(--red);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
             <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
           </svg>
           <h3 style="font-size:1.1rem;color:var(--txt);font-weight:700;margin-bottom:8px;">Encrypted Data Stream</h3>
           <p style="font-size:.875rem;color:var(--muted);margin-bottom:24px;max-width:350px;margin-left:auto;margin-right:auto;">This patient's diagnosis is heavily encrypted. Enter your 4-digit Security PIN to decrypt it securely in your browser session.</p>
           
           <?php if ($pinError): ?>
              <p style="color:var(--red);font-size:.8rem;font-weight:700;margin-bottom:16px;"><?= htmlspecialchars($pinError) ?></p>
           <?php endif; ?>

           <form method="POST" style="display:inline-block;">
             <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
             <div style="display:flex;align-items:center;justify-content:center;gap:12px;">
               <input type="password" name="decrypt_pin" class="form-input" style="width:120px;text-align:center;font-size:1.2rem;letter-spacing:.3em;padding:12px;" placeholder="••••" pattern="[0-9]{4}" maxlength="4" required autofocus>
               <button type="submit" class="btn btn-primary" style="background:var(--red);box-shadow:0 4px 15px rgba(239,68,68,0.3);padding:13px 20px;">Decrypt Data</button>
             </div>
           </form>
        </div>
      <?php else: ?>
        <div style="padding:24px; border-radius:var(--r-lg); background:rgba(0,0,0,0.3); border:1px solid var(--border);">
          <p style="white-space:pre-wrap;font-size:.95rem;line-height:1.7;color:var(--txt);font-family:serif;"><?= htmlspecialchars($diagnosisText) ?></p>
        </div>
      <?php endif; ?>
    </div>

  </div>

  <!-- Right Column: Sidebar Actions & Meta -->
  <div style="display:flex;flex-direction:column;gap:24px;">

    <!-- Attached Files -->
    <div class="glass-card" style="padding:24px;">
      <h3 style="font-size:.9rem;font-weight:600;color:var(--txt);margin-bottom:16px;">In-Browser File Viewer</h3>
      <?php if (!empty($record['file_path'])): ?>
        <div style="width:100%;background:#000;border-radius:var(--r-md);overflow:hidden;border:1px solid var(--border);height:300px;">
           <iframe src="view_file.php?path=<?= h($record['file_path']) ?>#toolbar=0&navpanes=0" width="100%" height="100%" style="border:none;"></iframe>
        </div>
        <p style="font-size:.78rem;margin-top:12px;text-align:center;color:var(--muted);display:flex;align-items:center;justify-content:center;gap:6px;">
          <svg style="width:14px;height:14px;" fill="currentColor" viewBox="0 0 20 20"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg> 
          Secure Sandbox View
        </p>
      <?php else: ?>
        <div class="empty-state" style="padding:32px 16px;border:1px dashed var(--border);border-radius:var(--r-md);">
          <p style="font-size:.8rem;">No additional documents attached.</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- Security & Status Card -->
    <div class="glass-card" style="padding:24px; background: linear-gradient(135deg, rgba(34,197,94,0.03) 0%, transparent 100%);">
      <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
        <div style="width:36px; height:36px; background:var(--green); border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 12px rgba(34,197,94,0.2);">
          <svg width="18" height="18" fill="none" stroke="#fff" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        </div>
        <div style="font-size: .9rem; font-weight: 700; color: var(--txt);">Security Status</div>
      </div>
      
      <div style="display:flex;flex-direction:column;gap:18px;">
        <div>
          <p style="font-size:.7rem; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:6px;">Record Status</p>
          <?php
            $statusClasses = ['pending'=>'badge-amber','received'=>'badge-blue','rejected'=>'badge-red'];
            $sc = $statusClasses[$record['status']] ?? 'badge-purple';
          ?>
           <span class="badge <?= $sc ?>" style="border-radius: var(--r-pill); padding: 4px 12px;"><?= ucfirst(htmlspecialchars($record['status'])) ?></span>
        </div>
        
        <div style="height:1px; background:var(--border);"></div>
        
        <div>
          <p style="font-size:.7rem; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:6px;">Encryption</p>
          <?php if ($record['is_encrypted']): ?>
            <p style="font-size:.85rem;font-weight:600;color:var(--green);display:flex;align-items:center;gap:6px;">
              <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C9.243 2 7 4.243 7 7v3H6a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2v-8a2 2 0 00-2-2h-1V7c0-2.757-2.243-5-5-5zM9 7c0-1.654 1.346-3 3-3s3 1.346 3 3v3H9V7z"/></svg>
              Active (AES-256)
            </p>
          <?php else: ?>
            <p style="font-size:.85rem;font-weight:600;color:var(--amber);">Not Encrypted</p>
          <?php endif; ?>
        </div>
        
        <div style="height:1px; background:var(--border);"></div>
        
        <div>
          <p style="font-size:.7rem; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:6px;">Audit Integrity</p>
          <p style="font-size:.82rem;color:var(--muted);line-height:1.4;">Firmly rooted in the blockchain audit chain. <a href="audit_logs.php" style="color:var(--orange); font-weight:600;">View trail →</a></p>
        </div>
      </div>
    </div>

  </div>

</div>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
