<?php
// public/read_notifications.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$user = currentUser();

// Mark all as read
markNotificationsRead($user['id']);

$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$user['id']]);
$notifications = $stmt->fetchAll();

$pageTitle = 'Notifications';
require_once __DIR__ . '/../includes/layout_head.php';
?>

<div class="topbar">
  <div>
    <p style="font-size:.78rem;color:var(--muted);font-weight:600;letter-spacing:.06em;text-transform:uppercase;margin-bottom:6px">Inbox</p>
    <h1 style="font-size:1.6rem;font-weight:700;letter-spacing:-.03em">Notifications</h1>
    <p style="font-size:.875rem;color:var(--muted);margin-top:4px">System alerts and transfer notifications</p>
  </div>
  <a href="dashboard.php" class="btn btn-ghost">
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
    Back to Dashboard
  </a>
</div>

<div class="glass-card" style="overflow:hidden; max-width: 800px; padding:0;">
  <?php if (empty($notifications)): ?>
    <div class="empty-state">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
      <h3>No recent notifications</h3>
      <p>You're all caught up.</p>
    </div>
  <?php else: ?>
    <div style="display:flex;flex-direction:column;">
      <?php foreach ($notifications as $n): ?>
        <div style="padding:20px; border-bottom:1px solid var(--border); display:flex; align-items:flex-start; gap:16px; transition:background-color .2s;" onmouseover="this.style.backgroundColor='var(--bg-hover)'" onmouseout="this.style.backgroundColor='transparent'">
          <div style="width:40px;height:40px;border-radius:var(--r-md);background:rgba(59,130,246,.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
             <svg style="width:20px;height:20px;color:var(--blue);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                 <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
             </svg>
          </div>
          <div style="flex:1;">
            <p style="font-size:.95rem;color:var(--txt);font-weight:500;margin-bottom:6px;"><?= htmlspecialchars($n['message']) ?></p>
            <div style="display:flex;align-items:center;gap:12px;">
               <span style="font-size:.78rem;color:var(--muted);"><?= formatDate($n['created_at']) ?></span>
               <?php if ($n['link']): ?>
                 <a href="<?= htmlspecialchars($n['link']) ?>" style="font-size:.78rem;color:var(--blue);display:flex;align-items:center;gap:4px;font-weight:500;">
                   View Details
                   <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:12px;height:12px"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                 </a>
               <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
