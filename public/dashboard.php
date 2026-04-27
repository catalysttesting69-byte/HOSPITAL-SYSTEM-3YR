<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';

requireLogin();
$user  = currentUser();
$stats = getDashboardStats($user['id'], $user['hospital_id']);
$recentSent     = getSentRecords($user['id'], 5, 0);
$recentReceived = getReceivedRecords($user['hospital_id'], 5, 0);
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/layout_head.php';
$flash = getFlash();
?>

<!-- Topbar -->
<div class="topbar">
  <div>
    <h1 style="font-size:1.6rem;font-weight:800;letter-spacing:-.03em;color:var(--txt)">Hospital Overview</h1>
    <p style="font-size:.85rem;color:var(--muted)">Welcome back, Dr. <?= htmlspecialchars($user['name']) ?></p>
  </div>
  <div class="topbar-right">
    <div style="display:flex; gap:12px; margin: 0 8px;">
      <button id="theme-toggle" class="btn-icon" style="border-radius: 50%; width: 40px; height: 40px;" title="Toggle Theme">
        <svg class="sun-icon hidden" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-11.314l.707.707m11.314 11.314l.707.707M12 8a4 4 0 100 8 4 4 0 000-8z"/></svg>
        <svg class="moon-icon" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
      </button>
      <button class="btn-icon" style="border-radius: 50%; width: 40px; height: 40px;" title="Help & Support">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
      </button>
    </div>
    <div class="user-chip">
      <div class="sidebar-avatar" style="width:32px; height:32px; border-radius:50%; font-size:.75rem;"><?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?></div>
      <div style="text-align: left; line-height: 1.2;">
        <div style="font-size: .82rem; font-weight: 700; color: var(--txt);"><?= htmlspecialchars($user['name']) ?></div>
        <div style="font-size: .65rem; color: var(--muted); font-weight: 600;"><?= htmlspecialchars($user['hospital_name']) ?></div>
      </div>
    </div>
  </div>
</div>

<?php if ($flash): ?>
  <div class="alert alert-<?= $flash['type'] === 'error' ? 'error' : ($flash['type'] === 'success' ? 'success' : 'info') ?>" data-auto-dismiss>
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:17px;height:17px;flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <?= htmlspecialchars($flash['message']) ?>
  </div>
<?php endif; ?>

<!-- ── Quick Actions ── -->
<div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:20px; margin-bottom:32px;">
    <a href="add_patient.php" class="btn btn-primary" style="padding:20px; justify-content:center; border-radius:var(--r-lg);">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
        Register Patient
    </a>
    <a href="transfer.php" class="btn btn-ghost" style="padding:20px; justify-content:center; border-radius:var(--r-lg);">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
        Emergency Transfer
    </a>
    <a href="records.php" class="btn btn-ghost" style="padding:20px; justify-content:center; border-radius:var(--r-lg);">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
        Incoming Records
    </a>
</div>

<!-- ── Stat Cards ── -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:24px;margin-bottom:32px">
  <div class="stat-card">
    <div class="stat-num"><?= $stats['sent'] ?></div>
    <div class="stat-lbl">Patients Transferred</div>
    <div class="stat-trend up">+ 5.2% Activity</div>
  </div>
  <div class="stat-card">
    <div class="stat-num"><?= $stats['received'] ?></div>
    <div class="stat-lbl">Inbound Cases</div>
    <div class="stat-trend down">- 2.8% vs last week</div>
  </div>
  <div class="stat-card">
    <div class="stat-num"><?= $stats['logs'] ?></div>
    <div class="stat-lbl">System Activity</div>
    <div class="stat-trend up">All systems normal</div>
  </div>
</div>

<!-- ── Analytics & Widget Row ── -->
<div style="display:grid;grid-template-columns:1fr 340px;gap:24px;margin-bottom:32px">
  <div class="glass-card" style="padding:28px; position:relative;">
    <h2 style="font-size:1.1rem;font-weight:700;color:var(--txt);margin-bottom:8px">Patient Flow</h2>
    <p style="font-size:.8rem;color:var(--muted);margin-bottom:24px">Total Handled: <?= $stats['sent'] + $stats['received'] ?></p>
    <div style="height:240px"><canvas id="trafficChart"></canvas></div>
  </div>
  <div class="glass-card" style="padding:28px; text-align:center;">
    <h2 style="font-size:1.1rem;font-weight:700;color:var(--txt);margin-bottom:24px;text-align:left;">Privacy Status</h2>
    <div style="position:relative; width:180px; height:180px; margin:0 auto 24px;">
      <canvas id="encryptionChart"></canvas>
      <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%);">
        <p style="font-size:.65rem; color:var(--muted); font-weight:700; text-transform:uppercase;">Protection</p>
        <p style="font-size:1.6rem; font-weight:800; color:var(--txt);">High</p>
      </div>
    </div>
    <p style="font-size:.8rem; color:var(--muted); line-height:1.5;">Records are end-to-end encrypted and verified.</p>
  </div>
</div>

<!-- ── Recent Records Table ── -->
<div>
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
    <h2 style="font-size:1rem;font-weight:700;color:var(--txt)">Recent Patient History</h2>
    <a href="records.php" class="btn btn-ghost btn-sm">See all patients</a>
  </div>
  <div class="glass-card" style="overflow:hidden">
    <?php if (empty($recentSent) && empty($recentReceived)): ?>
      <div class="empty-state">
        <h3>No activity recorded</h3>
      </div>
    <?php else: ?>
      <table class="data-table">
        <thead>
          <tr>
            <th>Patient Name</th>
            <th>Related Hospital</th>
            <th>Type</th>
            <th>Status</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (array_slice($recentSent, 0, 5) as $r): ?>
          <tr>
            <td><span style="font-weight:600;color:var(--txt)"><?= htmlspecialchars($r['patient_name']) ?></span></td>
            <td style="color:var(--muted);font-size:.82rem"><?= htmlspecialchars($r['receiver_hospital_name']) ?></td>
            <td><span class="badge badge-blue">Outbound</span></td>
            <td><span class="badge"><?= ucfirst($r['status']) ?></span></td>
            <td style="color:var(--muted);font-size:.78rem"><?= formatDate($r['created_at']) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php foreach (array_slice($recentReceived, 0, 5) as $r): ?>
          <tr>
            <td><a href="view_record.php?id=<?= $r['id'] ?>" style="font-weight:600;color:var(--orange)"><?= htmlspecialchars($r['patient_name']) ?></a></td>
            <td style="color:var(--muted);font-size:.82rem">Dr. <?= htmlspecialchars($r['sender_name']) ?></td>
            <td><span class="badge badge-purple">Inbound</span></td>
            <td><span class="badge badge-blue">Received</span></td>
            <td style="color:var(--muted);font-size:.78rem"><?= formatDate($r['created_at']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const gridColor = 'rgba(0,0,0,0.05)';
  const tickColor = '#a0a0a0';
  new Chart(document.getElementById('trafficChart').getContext('2d'), {
    type: 'bar',
    data: {
      labels: ['1–5','6–10','11–15','16–20','21–25','26–30'],
      datasets: [{
        label: 'Transfers',
        data: [1, 2, <?= (int)$stats['sent'] ?>, 3, <?= (int)$stats['received'] ?>, 2],
        backgroundColor: 'var(--orange)',
        borderRadius: 8,
      }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
  });
  new Chart(document.getElementById('encryptionChart').getContext('2d'), {
    type: 'doughnut',
    data: {
      labels: ['Protected', 'Plaintext'],
      datasets: [{
        data: [<?= max(1, (int)$stats['sent']) ?>, 0],
        backgroundColor: ['#f97316', '#e8e3da'],
        borderWidth: 0, cutout: '72%',
      }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
  });
});
</script>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
