<?php
// public/add_patient.php — Register a new patient
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';

requireLogin();
$user = currentUser();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Security token mismatch.';
    } else {
        $mrn      = trim($_POST['mrn'] ?? '');
        $name     = trim($_POST['full_name'] ?? '');
        $dob      = $_POST['dob'] ?? '';
        $gender   = $_POST['gender'] ?? '';
        $contact  = trim($_POST['contact'] ?? '');

        if (!$mrn || !$name || !$dob || !$gender) {
            $error = 'Please fill in all required fields.';
        } elseif (getPatientByMrn($mrn)) {
            $error = 'A patient with this MRN already exists.';
        } else {
            $patientId = createPatient($mrn, $name, $dob, $gender, $contact, $user['hospital_id']);
            writeAuditLog('PATIENT_REGISTRATION', $user['id'], null);
            setFlash('success', "Patient '$name' registered successfully.");
            header('Location: patients.php');
            exit;
        }
    }
}

$pageTitle = 'Register Patient';
require_once __DIR__ . '/../includes/layout_head.php';
?>

<div class="topbar">
  <div>
    <p style="font-size:.78rem;color:var(--muted);font-weight:600;letter-spacing:.06em;text-transform:uppercase;margin-bottom:6px">Patients</p>
    <h1 style="font-size:1.6rem;font-weight:700;letter-spacing:-.03em">Register New Patient</h1>
  </div>
  <a href="patients.php" class="btn btn-ghost">Back to List</a>
</div>

<?php if ($error): ?>
  <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" class="glass-card" style="padding:32px; max-width: 600px;">
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

    <div class="form-group">
      <label class="form-label">Medical Record Number (MRN) *</label>
      <input type="text" name="mrn" class="form-input" placeholder="e.g. MRN-2024-001" required value="<?= h($_POST['mrn'] ?? '') ?>">
    </div>

    <div class="form-group">
      <label class="form-label">Full Name *</label>
      <input type="text" name="full_name" class="form-input" placeholder="Patient's Full Name" required value="<?= h($_POST['full_name'] ?? '') ?>">
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div class="form-group">
          <label class="form-label">Date of Birth *</label>
          <input type="date" name="dob" class="form-input" required value="<?= h($_POST['dob'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Gender *</label>
          <select name="gender" class="form-select" required>
            <option value="">Select Gender</option>
            <option value="Male" <?= ($_POST['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= ($_POST['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
            <option value="Other" <?= ($_POST['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
          </select>
        </div>
    </div>

    <div class="form-group">
      <label class="form-label">Contact Info (Phone/Email)</label>
      <input type="text" name="contact" class="form-input" placeholder="Optional contact details" value="<?= h($_POST['contact'] ?? '') ?>">
    </div>

    <div style="margin-top: 32px;">
      <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">Register Patient</button>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
