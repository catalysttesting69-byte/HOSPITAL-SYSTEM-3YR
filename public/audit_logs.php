<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';

requireLogin();
if (!isAdmin()) {
    setFlash('error', 'Unauthorized access to security logs.');
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_chain'])) {
    if (!verifyCsrfToken()) {
        setFlash('error', 'Security token mismatch.');
    } else {
        $verification = verifyAuditChain();
        if ($verification['valid']) {
            setFlash('success', 'Blockchain audit chain verified successfully. No tampering detected.');
        } else {
            setFlash('error', 'Audit chain verification failed at entry #' . $verification['broken_at_id']);
        }
    }
}

$verification = verifyAuditChain();
$chainValid   = $verification['valid'];
$brokenAt     = $verification['broken_at_id'] ?? null;

$pdo  = getDB();
$stmt = $pdo->query("SELECT al.*, u.name AS user_name, h.name AS hospital_name FROM audit_logs al JOIN users u ON u.id=al.performed_by JOIN hospitals h ON h.id=u.hospital_id ORDER BY al.id DESC LIMIT 100");
$logs = $stmt->fetchAll();

$pageTitle = 'Audit Logs';
require_once __DIR__ . '/../includes/layout_head.php';
?>

<div class="topbar">
  <div>
    <p style="font-size:.78rem;color:var(--muted);font-weight:600;letter-spacing:.06em;text-transform:uppercase;margin-bottom:6px">Security</p>
    <h1 style="font-size:1.6rem;font-weight:700;letter-spacing:-.03em">Audit Logchain</h1>
    <p style="font-size:.875rem;color:var(--muted);margin-top:4px">SHA-256 hash-chained record of all system actions</p>
  </div>
  <div style="display:flex;align-items:center;gap:12px">
    <form method="POST" style="margin:0">
      <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
      <button type="submit" name="verify_chain" class="btn btn-primary btn-sm">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        Re-Verify Chain
      </button>
    </form>
    <?php if ($chainValid): ?>
      <span class="badge badge-green" style="font-size:.8rem;padding:6px 14px">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" style="width:12px;height:12px"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        Chain Intact
      </span>
    <?php else: ?>
      <span class="badge badge-red" style="font-size:.8rem;padding:6px 14px">⚠ Tampered at #<?= $brokenAt ?></span>
    <?php endif; ?>
    <button onclick="exportAuditLog()" class="btn btn-ghost">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
      Export PDF
    </button>
  </div>
</div>

<?php if (!$chainValid): ?>
  <div class="alert alert-error" style="margin-bottom:24px">
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
    <div><strong>Security Alert:</strong> Chain broken at entry #<?= $brokenAt ?>. The audit data may have been tampered with.</div>
  </div>
<?php endif; ?>

<!-- Hash Formula Banner -->
<div class="glass-card" style="padding:16px 20px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
  <div style="display:flex;align-items:center;gap:14px">
    <div style="width:36px;height:36px;background:rgba(59,130,246,.1);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
      <svg width="18" height="18" fill="none" stroke="var(--blue)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
    </div>
    <div>
      <div style="font-size:.72rem;color:var(--muted);margin-bottom:4px">Hash Formula</div>
      <code style="font-size:.8rem;color:var(--blue-hi);font-family:'Courier New',monospace">current_hash = SHA256(action + record_id + timestamp + previous_hash)</code>
    </div>
  </div>
  <div style="text-align:right">
    <div style="font-size:.72rem;color:var(--muted)">Total Entries</div>
    <div style="font-size:1.4rem;font-weight:700;letter-spacing:-.03em"><?= count($logs) ?></div>
  </div>
</div>

<!-- Log Entries -->
<div id="audit-export-target" style="display:flex;flex-direction:column;gap:12px">
  <?php if (empty($logs)): ?>
    <div class="glass-card empty-state">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
      <h3>No audit logs yet</h3>
      <p>Every action in the system generates an immutable log entry here.</p>
    </div>
  <?php else: ?>
    <?php foreach ($logs as $log):
      $colors = ['UPLOAD'=>'var(--blue)','TRANSFER'=>'var(--teal)','VIEW / RECEIVED'=>'var(--purple)','VIEW / DECRYPTED'=>'var(--green)','LOGIN'=>'var(--amber)'];
      $color = $colors[$log['action']] ?? 'var(--muted)';
    ?>
      <div class="glass-card chain-entry" style="padding:20px 20px 20px 32px">
        <div class="chain-node" style="background:<?= $color ?>"></div>
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap">
          <div>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
              <span style="font-size:.875rem;font-weight:700;color:<?= $color ?>"><?= htmlspecialchars($log['action']) ?></span>
              <?php if ($log['record_id']): ?>
                <span class="badge badge-blue">Record #<?= $log['record_id'] ?></span>
              <?php endif; ?>
            </div>
            <div style="font-size:.8rem;color:var(--muted)">
              Dr. <strong style="color:var(--txt)"><?= htmlspecialchars($log['user_name']) ?></strong>
              · <?= htmlspecialchars($log['hospital_name']) ?>
            </div>
            <div style="display:flex;align-items:center;gap:16px;margin-top:12px;flex-wrap:wrap">
              <div style="display:flex;align-items:center;gap:6px">
                <span style="font-size:.72rem;color:var(--muted);font-weight:600">PREV</span>
                <span class="hash-chip" title="<?= htmlspecialchars($log['previous_hash']) ?>"><?= htmlspecialchars($log['previous_hash']) ?></span>
              </div>
              <div style="display:flex;align-items:center;gap:6px">
                <span style="font-size:.72rem;color:var(--blue);font-weight:600">HASH</span>
                <span class="hash-chip" style="border-color:rgba(59,130,246,.3);color:var(--blue-hi)" title="<?= htmlspecialchars($log['current_hash']) ?>"><?= htmlspecialchars($log['current_hash']) ?></span>
              </div>
            </div>
          </div>
          <div style="text-align:right;flex-shrink:0">
            <div style="font-size:.8rem;font-weight:600;color:var(--txt)"><?= formatDate($log['timestamp']) ?></div>
            <div style="font-size:.72rem;color:var(--muted);margin-top:2px">Entry #<?= $log['id'] ?></div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<script>
function exportAuditLog() {
  const el  = document.getElementById('audit-export-target');
  const opt = { margin:.5, filename:'RUCU_Blockchain_Audit_Trail.pdf',
    image:{type:'jpeg',quality:.98}, html2canvas:{scale:2,useCORS:true},
    jsPDF:{unit:'in',format:'letter',orientation:'portrait'} };
  html2pdf().set(opt).from(el).save();
}
</script>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
