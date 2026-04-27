<?php
// includes/layout_head.php — Shared layout: head + sidebar
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title><?= htmlspecialchars($pageTitle ?? 'RUCU') ?> — RUCU</title>
  <meta name="description" content="RUCU: Secure Digital Exchange System for Inter-Hospital Patient Transfer."/>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
  <link rel="stylesheet" href="assets/css/style.css"/>
  <script>
    // Immediate theme check (Priority: Database User Setting > LocalStorage > Default Light)
    (function() {
      const userTheme = '<?= $user['theme'] ?? '' ?>';
      const savedTheme = userTheme || localStorage.getItem('rucu-theme') || 'light';
      document.documentElement.setAttribute('data-theme', savedTheme);
    })();
  </script>
  <style>
    /* Smooth Theme Transition */
    *, *::before, *::after {
        transition: background-color 0.4s cubic-bezier(0.4, 0, 0.2, 1), 
                    border-color 0.4s cubic-bezier(0.4, 0, 0.2, 1), 
                    color 0.3s ease;
    }
    
    /* Animation for the sun/moon icon */
    .theme-toggle-animation {
        animation: rotateIcon 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    }
    @keyframes rotateIcon {
        from { transform: rotate(-90deg) scale(0); opacity: 0; }
        to { transform: rotate(0) scale(1); opacity: 1; }
    }
    .hidden { display: none !important; }
  </style>
</head>
<body>

<!-- Bio Background Elements (subtle) -->
<div id="global-bg-container" style="position: fixed; inset: 0; pointer-events: none; z-index: 0; opacity: 0.04;">
  <div class="bio-element" style="position: absolute; bottom: -80px; right: -80px; transition: transform 0.5s ease-out;">
    <svg style="width: 420px; height: 420px; transform: rotate(-15deg); animation: floatDNA 22s ease-in-out infinite alternate;" viewBox="0 0 200 400" fill="none" stroke="#f97316" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
      <path stroke-dasharray="1000" stroke-dashoffset="1000" style="animation: dashDNA 28s linear infinite;" d="M50,0 Q150,50 50,100 T50,200 T50,300 T50,400" />
      <path stroke-dasharray="1000" stroke-dashoffset="1000" style="animation: dashDNA 28s linear infinite reverse;" d="M150,0 Q50,50 150,100 T150,200 T150,300 T150,400" />
    </svg>
  </div>
  <div class="bio-element" style="position: absolute; top: 10%; right: 8%; transition: transform 0.5s ease-out;">
    <svg style="width: 150px; height: 190px; animation: floatCell 28s infinite ease-in-out;" viewBox="0 0 100 120" fill="none" stroke="#f97316" stroke-width="1.2">
      <path d="M50,10 L70,30 L70,50 L50,70 L30,50 L30,30 Z" />
      <path d="M30,30 L70,30 M70,50 L30,50 M50,10 L50,70" opacity="0.3" />
      <line x1="50" y1="70" x2="50" y2="90" />
      <line x1="45" y1="75" x2="55" y2="75" opacity="0.5" />
      <line x1="45" y1="82" x2="55" y2="82" opacity="0.5" />
      <path d="M50,90 L75,108 M50,90 L25,108 M50,90 L85,94 M50,90 L15,94" />
    </svg>
  </div>
  <div class="bio-element" style="position: absolute; top: 60%; left: 5%; transition: transform 0.45s ease-out;">
    <svg style="width: 70px; height: 110px; animation: floatCell 22s infinite ease-in-out;" viewBox="0 0 100 150" fill="none" stroke="#14b8a6" stroke-width="1.5">
      <ellipse cx="50" cy="75" rx="28" ry="58" />
      <path d="M50,25 Q32,40 50,55 T50,85 T50,115" opacity="0.5" />
    </svg>
  </div>
</div>
<script>
document.addEventListener('mousemove', (e) => {
  const mouseX = e.clientX, mouseY = e.clientY;
  document.querySelectorAll('.bio-element').forEach(el => {
    const r = el.getBoundingClientRect();
    const dx = (r.left + r.width/2) - mouseX;
    const dy = (r.top + r.height/2) - mouseY;
    const dist = Math.hypot(dx, dy);
    if (dist < 320) {
      const f = (320 - dist) / 320;
      el.style.transform = `translate(${(dx/dist)*f*45}px, ${(dy/dist)*f*45}px)`;
    } else {
      el.style.transform = 'translate(0,0)';
    }
  });
});
</script>

<?php $unreadNotifs = isset($user) ? getUnreadNotifications($user['id']) : []; ?>

<!-- ══ SIDEBAR ══ -->
<aside id="sidebar" class="sidebar">

  <!-- Logo -->
  <div class="sidebar-logo">
    <div style="display:flex;align-items:center;gap:12px">
      <div style="width:38px;height:38px;background:var(--orange);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 12px rgba(249,115,22,0.3)">
        <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
      </div>
      <div>
        <div style="font-size:.95rem;font-weight:800;color:var(--txt);letter-spacing:-.03em">RUCU</div>
        <div style="font-size:.68rem;color:var(--muted);font-weight:500">Hospital Exchange</div>
      </div>
    </div>
  </div>

  <!-- Nav -->
  <nav class="sidebar-nav">
    <div class="nav-section-label">Main</div>

    <a href="dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
      <div class="nav-icon">
        <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/></svg>
      </div>
      Dashboard
    </a>

    <a href="upload.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'upload.php' ? 'active' : '' ?>">
      <div class="nav-icon">
        <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
      </div>
      Upload Record
    </a>

    <a href="transfer.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'transfer.php' ? 'active' : '' ?>">
      <div class="nav-icon">
        <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>
      </div>
      Transfer Record
    </a>

    <a href="patients.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'patients.php' ? 'active' : '' ?>">
      <div class="nav-icon">
        <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
      </div>
      Patients
    </a>

    <div class="nav-section-label" style="margin-top:6px">Receive</div>

    <a href="records.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'records.php' ? 'active' : '' ?>">
      <div class="nav-icon">
        <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      </div>
      Incoming Records
      <?php if (!empty($unreadNotifs)): ?>
        <span class="nav-badge"><?= count($unreadNotifs) ?></span>
      <?php endif; ?>
    </a>

    <div class="nav-section-label" style="margin-top:6px">Security</div>

    <a href="audit_logs.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'audit_logs.php' ? 'active' : '' ?>">
      <div class="nav-icon">
        <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      </div>
      System Activity
    </a>

    <a href="read_notifications.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'read_notifications.php' ? 'active' : '' ?>">
      <div class="nav-icon">
        <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
      </div>
      Alerts
      <?php if (!empty($unreadNotifs)): ?>
        <span class="nav-badge"><?= count($unreadNotifs) ?></span>
      <?php endif; ?>
    </a>

  </nav>

  <!-- Sidebar Footer Info -->
  <div style="padding: 14px; margin-top: auto;">
    <div style="background: var(--bg-hover); border-radius: var(--r-lg); padding: 16px; text-align: left; border: 1px solid var(--border);">
      <div style="font-size: .75rem; font-weight: 700; color: var(--txt); margin-bottom: 4px;">Verified System</div>
      <div style="font-size: .68rem; color: var(--muted);">Certified secure by Tanzania Health Information Authority (THIA)</div>
    </div>
  </div>

  <!-- Bottom User -->
  <div class="sidebar-user">
    <div style="display:flex;align-items:center;gap:10px">
      <div class="sidebar-avatar"><?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?></div>
      <div style="min-width:0;flex:1">
        <div style="font-size:.82rem;font-weight:600;color:var(--txt);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">Dr. <?= htmlspecialchars($user['name'] ?? '') ?></div>
        <div style="font-size:.7rem;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($user['hospital_name'] ?? '') ?></div>
      </div>
      <a href="logout.php" title="Sign out" style="color:var(--muted);flex-shrink:0;display:flex;align-items:center;padding:4px;">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      </a>
    </div>
  </div>
</aside>

<div class="main-content">
