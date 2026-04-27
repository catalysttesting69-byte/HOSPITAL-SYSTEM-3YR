<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/encryption.php';

requireLogin();
$user = currentUser();
$hospitals = getAllHospitals();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Security token mismatch.';
    } else {
        $patientId   = (int)($_POST['patient_id'] ?? 0);
        $diagnosis   = trim($_POST['diagnosis'] ?? '');
        $receiverHId = (int)($_POST['receiver_hospital_id'] ?? 0);
        $doEncrypt   = !empty($_POST['encrypt']);

        if (!$patientId || !$diagnosis || !$receiverHId) {
            $error = 'Patient selection, diagnosis, and receiving hospital are required.';
        } elseif ($receiverHId === (int)$user['hospital_id']) {
            $error = 'Receiving hospital cannot be your own hospital.';
        } else {
            try {
                // Fetch patient name for legacy field
                $pdo = getDB();
                $pStmt = $pdo->prepare("SELECT full_name FROM patients WHERE id = ?");
                $pStmt->execute([$patientId]);
                $patient = $pStmt->fetch();
                
                if (!$patient) {
                    throw new RuntimeException("Selected patient not found.");
                }

                $filePath = null;
                if (!empty($_FILES['record_file']['name'])) {
                    $filePath = uploadPatientFile($_FILES['record_file']);
                }
                $storedDiagnosis = $doEncrypt ? encryptData($diagnosis) : $diagnosis;
                
                $stmt = $pdo->prepare("INSERT INTO patient_records (patient_id, patient_name, diagnosis, file_path, sender_id, receiver_hospital_id, is_encrypted) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$patientId, $patient['full_name'], $storedDiagnosis, $filePath, $user['id'], $receiverHId, $doEncrypt ? 1 : 0]);
                $recordId = (int)$pdo->lastInsertId();
                writeAuditLog('UPLOAD', $user['id'], $recordId);
                setFlash('success', "Patient record for '{$patient['full_name']}' uploaded successfully.");
                header('Location: dashboard.php'); exit;
            } catch (RuntimeException $e) { $error = $e->getMessage(); }
        }
    }
}

$patients = getAllPatients($user['hospital_id']);

$pageTitle = 'Upload Record';
require_once __DIR__ . '/../includes/layout_head.php';
?>

<!-- Topbar -->
<div class="topbar">
  <div>
    <p style="font-size:.78rem;color:var(--muted);font-weight:600;letter-spacing:.06em;text-transform:uppercase;margin-bottom:6px">Upload</p>
    <h1 style="font-size:1.6rem;font-weight:700;letter-spacing:-.03em">New Patient Record</h1>
  </div>
  <a href="dashboard.php" class="btn btn-ghost">
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
    Back
  </a>
</div>

<?php if ($error): ?>
  <div class="alert alert-error">
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 320px;gap:28px;align-items:start">

  <!-- Main Form -->
  <form method="POST" enctype="multipart/form-data" class="glass-card" style="padding:32px">
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

    <div style="margin-bottom:28px;padding-bottom:20px;border-bottom:1px solid var(--border)">
      <div style="font-size:.78rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px">Patient Details</div>
      <p style="font-size:.875rem;color:var(--muted)">Fill in the patient information and optional PDF report.</p>
    </div>

    <div class="form-group">
      <label class="form-label">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        Select Patient *
      </label>
      <div class="input-icon-wrap">
        <select name="patient_id" class="form-select" required>
          <option value="">— Choose a registered patient —</option>
          <?php foreach ($patients as $p): ?>
            <option value="<?= $p['id'] ?>" <?= (($_POST['patient_id'] ?? ($_GET['patient_id'] ?? '')) == $p['id']) ? 'selected' : '' ?>>
              <?= h($p['full_name']) ?> (<?= h($p['mrn']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <p style="font-size: .75rem; color: var(--muted); margin-top: 6px;">
        Don't see the patient? <a href="add_patient.php" style="color: var(--blue); font-weight: 600;">Register them first →</a>
      </p>
    </div>

    <div class="form-group">
      <label class="form-label">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Clinical Diagnosis &amp; Notes *
      </label>
      <textarea name="diagnosis" class="form-textarea" placeholder="Enter detailed diagnosis, medication, treatment history, and clinical notes..." required><?= htmlspecialchars($_POST['diagnosis'] ?? '') ?></textarea>
    </div>

    <div class="form-group">
      <label class="form-label">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        Receiving Hospital *
      </label>
      <select name="receiver_hospital_id" class="form-select" required>
        <option value="">— Select destination hospital —</option>
        <?php foreach ($hospitals as $h): if ((int)$h['id'] === (int)$user['hospital_id']) continue; ?>
          <option value="<?= $h['id'] ?>" <?= (($_POST['receiver_hospital_id'] ?? '') == $h['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($h['name']) ?> · <?= htmlspecialchars($h['location']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- File Upload -->
    <div class="form-group">
      <label class="form-label">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
        Patient Report PDF <span style="color:var(--dim)">(optional · max 10 MB)</span>
      </label>
      <div id="drop-zone" class="drop-zone">
        <svg width="36" height="36" fill="none" stroke="var(--muted)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="margin:0 auto 12px"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        <p style="font-size:.875rem;font-weight:500;color:var(--muted)">Drag & drop PDF here, or <span style="color:var(--blue)">browse files</span></p>
        <p id="file-label" style="font-size:.78rem;color:var(--dim);margin-top:8px">No file selected</p>
        <input type="file" id="file-input" name="record_file" accept=".pdf,application/pdf" class="hidden"/>
      </div>
    </div>

    <!-- Encryption Toggle -->
    <label class="enc-toggle" style="margin-bottom:24px;cursor:pointer">
      <input type="checkbox" name="encrypt" value="1" style="width:18px;height:18px;accent-color:var(--blue);flex-shrink:0;margin-top:2px" <?= !empty($_POST['encrypt']) ? 'checked' : '' ?>/>
      <div>
        <div style="font-size:.875rem;font-weight:600;margin-bottom:4px;display:flex;align-items:center;gap:8px">
          <svg width="15" height="15" fill="none" stroke="var(--blue)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
          Encrypt diagnosis with AES-256-CBC
        </div>
        <div style="font-size:.78rem;color:var(--muted)">Strongly recommended — diagnosis will be encrypted before storage and decrypted only with the recipient's PIN</div>
      </div>
    </label>

    <div style="display:flex;gap:12px">
      <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        Upload Patient Record
      </button>
      <a href="dashboard.php" class="btn btn-ghost">Cancel</a>
    </div>
  </form>

  <!-- Info Sidebar -->
  <div style="display:flex;flex-direction:column;gap:16px">

    <div class="glass-card" style="padding:20px">
      <div style="font-size:.78rem;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);margin-bottom:14px">How It Works</div>
      <?php foreach ([
        ['📋', 'Fill in patient details and select destination hospital'],
        ['📎', 'Optionally attach a PDF report (stored outside web root)'],
        ['🔒', 'Enable AES-256 encryption to protect the diagnosis'],
        ['🔗', 'Action is permanently logged in the blockchain audit trail'],
      ] as $i => [$icon, $text]): ?>
        <div class="step-row">
          <div class="step-num"><?= $i+1 ?></div>
          <div style="font-size:.8rem;color:var(--muted);line-height:1.5"><?= $icon ?> <?= $text ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="glass-card" style="padding:24px; background: linear-gradient(135deg, rgba(249,115,22,0.05) 0%, transparent 100%); border-color: rgba(249,115,22,0.2);">
      <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
        <div style="width:40px; height:40px; background:var(--orange); border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 12px rgba(249,115,22,0.3);">
          <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        </div>
        <div>
          <div style="font-size: .85rem; font-weight: 700; color: var(--txt);">Verified Secure</div>
          <div style="font-size: .7rem; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: .05em;">Military-Grade</div>
        </div>
      </div>
      <p style="font-size: .8rem; color: var(--muted); line-height: 1.6; margin-bottom: 0;">
        This record is protected by <strong>AES-256-CBC</strong> encryption and anchored to a tamper-proof audit chain.
      </p>
    </div>

    <div class="glass-card" style="padding:20px">
      <div style="font-size:.72rem;color:var(--muted);margin-bottom:6px">Uploading from</div>
      <div style="font-size:.95rem;font-weight:700"><?= htmlspecialchars($user['hospital_name']) ?></div>
      <div style="font-size:.8rem;color:var(--muted);margin-top:2px"><?= htmlspecialchars($user['hospital_location']) ?></div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
