<?php
// public/patients.php — Patient Directory
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$user = currentUser();
$patients = getAllPatients($user['hospital_id']);

$pageTitle = 'Patient Directory';
require_once __DIR__ . '/../includes/layout_head.php';
?>

<div class="topbar">
  <div>
    <h1 style="font-size:1.6rem;font-weight:800;letter-spacing:-.03em;color:var(--txt)">Patients</h1>
    <p style="font-size:.875rem;color:var(--muted);margin-top:4px">Manage and view patient demographics for <?= h($user['hospital_name']) ?></p>
  </div>
  <a href="add_patient.php" class="btn btn-primary">
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
    Register Patient
  </a>
</div>

<div class="glass-card" style="padding: 0; overflow: hidden;">
    <?php if (empty($patients)): ?>
      <div class="empty-state" style="padding: 60px;">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        <h3>No patients registered yet</h3>
        <p>Start by adding patients from your hospital to the network.</p>
        <a href="add_patient.php" class="btn btn-ghost" style="margin-top: 20px;">Register your first patient</a>
      </div>
    <?php else: ?>
      <table class="data-table">
        <thead>
          <tr>
            <th>MRN</th>
            <th>Full Name</th>
            <th>Gender</th>
            <th>Date of Birth</th>
            <th>Contact</th>
            <th style="text-align: right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($patients as $p): ?>
          <tr>
            <td><code style="font-weight:700; color:var(--blue-hi)"><?= h($p['mrn']) ?></code></td>
            <td><span style="font-weight:600;color:var(--txt)"><?= h($p['full_name']) ?></span></td>
            <td><span class="badge <?= $p['gender'] === 'Male' ? 'badge-blue' : 'badge-purple' ?>"><?= h($p['gender']) ?></span></td>
            <td style="color:var(--muted);font-size:.82rem"><?= h($p['dob']) ?></td>
            <td style="color:var(--muted);font-size:.82rem"><?= h($p['contact'] ?: '—') ?></td>
            <td style="text-align: right;">
                <a href="upload.php?patient_id=<?= $p['id'] ?>" class="btn btn-ghost btn-sm" title="New Record">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
                </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
