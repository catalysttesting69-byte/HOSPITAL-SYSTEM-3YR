<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$user = currentUser();
$records = getReceivedRecords($user['hospital_id'], 100, 0);
$pageTitle = 'Incoming Records';
require_once __DIR__ . '/../includes/layout_head.php';
?>

<div class="topbar">
  <div>
    <p style="font-size:.78rem;color:var(--muted);font-weight:600;letter-spacing:.06em;text-transform:uppercase;margin-bottom:6px">Inbox</p>
    <h1 style="font-size:1.6rem;font-weight:700;letter-spacing:-.03em">Incoming Patient Records</h1>
    <p style="font-size:.875rem;color:var(--muted);margin-top:4px">Transferred to <?= htmlspecialchars($user['hospital_name']) ?></p>
  </div>
  <div style="display:flex;align-items:center;gap:12px">
    <?php if (!empty($records)): ?>
      <span class="badge badge-blue"><?= count($records) ?> records</span>
    <?php endif; ?>
  </div>
</div>

<!-- Search -->
<div class="glass-card" style="padding:16px;margin-bottom:20px;display:flex;align-items:center;gap:12px">
  <svg width="18" height="18" fill="none" stroke="var(--muted)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="flex-shrink:0"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
  <input type="text" id="table-search" placeholder="Search by patient name..." style="background:transparent;border:none;outline:none;color:var(--txt);font-size:.875rem;width:100%;font-family:inherit"/>
</div>

<div class="glass-card" style="overflow:hidden">
  <?php if (empty($records)): ?>
    <div class="empty-state">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
      <h3>No incoming records</h3>
      <p>Your hospital hasn't received any patient transfers yet.</p>
    </div>
  <?php else: ?>
    <table class="data-table">
      <thead>
        <tr>
          <th>Patient Name</th>
          <th>Transferred From</th>
          <th>Date Received</th>
          <th>Encryption</th>
          <th>Status</th>
          <th style="text-align:right">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($records as $r): ?>
          <tr>
            <td>
              <div style="font-weight:600"><?= htmlspecialchars($r['patient_name']) ?></div>
            </td>
            <td>
              <div style="font-size:.875rem">Dr. <?= htmlspecialchars($r['sender_name']) ?></div>
              <div style="font-size:.75rem;color:var(--muted)"><?= htmlspecialchars($r['sender_hospital_name']) ?></div>
            </td>
            <td style="color:var(--muted);font-size:.8rem"><?= formatDate($r['created_at']) ?></td>
            <td>
              <?php if ($r['is_encrypted']): ?>
                <span class="badge badge-green">
                  <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                  AES-256
                </span>
              <?php else: ?>
                <span class="badge badge-amber">Plaintext</span>
              <?php endif; ?>
            </td>
            <td>
              <?php $sc = ['pending'=>'badge-amber','received'=>'badge-blue','rejected'=>'badge-red'][$r['status']] ?? 'badge-purple'; ?>
              <span class="badge <?= $sc ?>"><?= ucfirst($r['status']) ?></span>
            </td>
            <td style="text-align:right">
              <a href="view_record.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-primary">
                Review
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<script>
document.getElementById('table-search').addEventListener('keyup', function() {
  const q = this.value.toLowerCase();
  const rows = document.querySelectorAll('tbody tr');
  rows.forEach(row => {
    const text = row.textContent.toLowerCase();
    row.style.display = text.includes(q) ? '' : 'none';
  });
});
</script>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
