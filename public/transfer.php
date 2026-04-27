<?php
// public/transfer.php — Transfer an existing patient record to another hospital
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/encryption.php';

requireLogin();
$user      = currentUser();
$hospitals = getAllHospitals();

$error   = '';
$success = '';

// Load only records sent by this doctor (status = pending, not yet transferred)
$pdo   = getDB();
$myRec = $pdo->prepare(
    "SELECT pr.*, h.name AS receiver_hospital_name
       FROM patient_records pr
       JOIN hospitals h ON h.id = pr.receiver_hospital_id
      WHERE pr.sender_id = ?
      ORDER BY pr.created_at DESC
      LIMIT 100"
);
$myRec->execute([$user['id']]);
$myRecords = $myRec->fetchAll();

// ── Handle Transfer POST ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Security token mismatch.';
    } else {
        $recordId    = (int) ($_POST['record_id']           ?? 0);
        $receiverHId = (int) ($_POST['receiver_hospital_id'] ?? 0);

        if (!$recordId || !$receiverHId) {
            $error = 'Please select both a record and a destination hospital.';
        } elseif ($receiverHId === (int)$user['hospital_id']) {
            $error = 'Cannot transfer to your own hospital.';
        } else {
            // Fetch the record and verify ownership
            $stmt = $pdo->prepare("SELECT * FROM patient_records WHERE id = ? AND sender_id = ?");
            $stmt->execute([$recordId, $user['id']]);
            $record = $stmt->fetch();

            if (!$record) {
                $error = 'Record not found or you do not own it.';
            } else {
                // Re-encrypt diagnosis for the new destination if not already encrypted
                $diagToStore = $record['diagnosis'];
                if (!$record['is_encrypted']) {
                    $diagToStore = encryptData($record['diagnosis']);
                }

                // Update record with new receiver & mark encrypted
                $upd = $pdo->prepare(
                    "UPDATE patient_records
                        SET receiver_hospital_id = ?,
                            diagnosis            = ?,
                            is_encrypted         = 1,
                            status               = 'pending'
                      WHERE id = ? AND sender_id = ?"
                );
                $upd->execute([$receiverHId, $diagToStore, $recordId, $user['id']]);

                // Audit log
                writeAuditLog('TRANSFER', $user['id'], $recordId);

                // Notify all doctors in the receiving hospital
                $rxDoctors = $pdo->prepare("SELECT id FROM users WHERE hospital_id = ?");
                $rxDoctors->execute([$receiverHId]);
                foreach ($rxDoctors->fetchAll() as $rxDoc) {
                    createNotification($rxDoc['id'], "New Encrypted Record from Dr. " . htmlspecialchars($user['name'] . ' (Record #' . $recordId . ')'), "view_record.php?id=" . $recordId);
                }

                $recHosp = getHospitalById($receiverHId);
                setFlash('success', "Record transferred to {$recHosp['name']} successfully.");
                header('Location: dashboard.php');
                exit;
            }
        }
    }
}

$pageTitle = 'Transfer Record';
require_once __DIR__ . '/../includes/layout_head.php';
?>

<div class="topbar">
  <div>
    <p style="font-size:.78rem;color:var(--muted);font-weight:600;letter-spacing:.06em;text-transform:uppercase;margin-bottom:6px">Transfer</p>
    <h1 style="font-size:1.6rem;font-weight:700;letter-spacing:-.03em">Transfer Patient Record</h1>
    <p style="font-size:.875rem;color:var(--muted);margin-top:4px">Securely send an encrypted record to a receiving hospital</p>
  </div>
  <a href="dashboard.php" class="btn btn-ghost">
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
    Back
  </a>
</div>

<?php if ($error): ?>
  <div class="alert alert-error" data-auto-dismiss>
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 340px;gap:28px;align-items:start">

  <!-- Transfer Form -->
  <form method="POST" class="glass-card" style="padding:32px;">
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
    <div style="font-size:.78rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.07em;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--border)">
      Transfer Details
    </div>

    <div class="form-group">
      <label class="form-label" for="record_id">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Select Patient Record *
      </label>
      <?php if (empty($myRecords)): ?>
        <div class="alert alert-info">
          No records found. <a href="upload.php" style="color:var(--blue);font-weight:600;">Upload one first →</a>
        </div>
      <?php else: ?>
        <select id="record_id" name="record_id" class="form-select" required>
          <option value="">— Choose a record —</option>
          <?php foreach ($myRecords as $r): ?>
            <option value="<?= $r['id'] ?>" <?= (($_POST['record_id'] ?? '') == $r['id']) ? 'selected' : '' ?>>
              #<?= $r['id'] ?> — <?= htmlspecialchars($r['patient_name']) ?> (→ <?= htmlspecialchars($r['receiver_hospital_name']) ?>) [<?= $r['is_encrypted'] ? '🔒 Encrypted' : 'Unencrypted' ?>]
            </option>
          <?php endforeach; ?>
        </select>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label class="form-label" for="receiver_hospital_id">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        Destination Hospital *
      </label>
      <select id="receiver_hospital_id" name="receiver_hospital_id" class="form-select" required>
        <option value="">— Select destination hospital —</option>
        <?php foreach ($hospitals as $h): ?>
          <?php if ((int)$h['id'] === (int)$user['hospital_id']) continue; ?>
          <option value="<?= $h['id'] ?>" <?= (($_POST['receiver_hospital_id'] ?? '') == $h['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($h['name']) ?> — <?= htmlspecialchars($h['location']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Security Notice -->
    <div style="padding:16px; border-radius:var(--r-md); background:rgba(20,184,166,0.1); border:1px solid rgba(20,184,166,0.3); margin-bottom:24px; display:flex; gap:12px; align-items:flex-start;">
      <svg style="width:20px;height:20px;color:var(--teal);flex-shrink:0;margin-top:2px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
      </svg>
      <div>
        <p style="font-size:.85rem;font-weight:600;color:var(--teal);">Secure Transfer Protocol</p>
        <p style="font-size:.78rem;color:var(--muted);margin-top:4px;line-height:1.5;">
          Diagnosis data will be AES-256-CBC encrypted before transfer. This action will be permanently logged in the audit chain.
        </p>
      </div>
    </div>

    <?php if (!empty($myRecords)): ?>
      <div style="display:flex; gap:12px;">
        <button type="submit" class="btn btn-primary" style="flex:1; justify-content:center;">
          <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
          </svg>
          Transfer Securely
        </button>
        <a href="dashboard.php" class="btn btn-ghost">Cancel</a>
      </div>
    <?php endif; ?>
  </form>

  <!-- Info Panel -->
  <div style="display:flex;flex-direction:column;gap:16px;">
    <div class="glass-card" style="padding:20px;">
      <h3 style="font-size:.85rem;font-weight:600;color:var(--txt);margin-bottom:16px;">Transfer Process</h3>
      <div style="display:flex;flex-direction:column;gap:16px;">
        <?php foreach ([
          ['🔍', 'Verify', 'Record ownership and sender credentials confirmed'],
          ['🔒', 'Encrypt', 'Diagnosis encrypted with AES-256-CBC'],
          ['📤', 'Transfer', 'Record sent to receiving hospital'],
          ['🔗', 'Log', 'Action hash-chained into immutable audit log'],
        ] as [$icon, $label, $desc]): ?>
          <div style="display:flex;align-items:flex-start;gap:12px;">
            <div style="width:32px;height:32px;border-radius:var(--r-sm);background:rgba(99,102,241,0.1);display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0;">
              <?= $icon ?>
            </div>
            <div>
              <p style="font-size:.8rem;font-weight:600;color:var(--txt);"><?= $label ?></p>
              <p style="font-size:.72rem;color:var(--muted);margin-top:2px;line-height:1.4;"><?= $desc ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="glass-card" style="padding:20px;">
      <p style="font-size:.72rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;">Transferring from:</p>
      <p style="font-size:.95rem;font-weight:700;color:var(--txt);margin-top:6px;"><?= htmlspecialchars($user['hospital_name']) ?></p>
      <p style="font-size:.78rem;color:var(--muted);margin-top:2px;"><?= htmlspecialchars($user['hospital_location']) ?></p>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
