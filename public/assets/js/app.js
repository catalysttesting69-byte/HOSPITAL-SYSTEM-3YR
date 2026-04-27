// ============================================================
// RUCU System — Main JavaScript (public/assets/js/app.js)
// ============================================================

document.addEventListener('DOMContentLoaded', () => {

  // ── Flash message auto-dismiss ────────────────────────────
  const flashEls = document.querySelectorAll('.alert[data-auto-dismiss]');
  flashEls.forEach(el => {
    setTimeout(() => {
      el.style.transition = 'opacity 0.5s ease';
      el.style.opacity    = '0';
      setTimeout(() => el.remove(), 500);
    }, 4000);
  });

  // ── Active nav link highlight ─────────────────────────────
  const currentPage = window.location.pathname.split('/').pop();
  document.querySelectorAll('.nav-link').forEach(link => {
    const href = link.getAttribute('href');
    if (href && href.endsWith(currentPage)) {
      link.classList.add('active');
    }
  });

  // ── PDF Drop-zone ─────────────────────────────────────────
  const dropZone  = document.getElementById('drop-zone');
  const fileInput = document.getElementById('file-input');
  const fileLabel = document.getElementById('file-label');

  if (dropZone && fileInput) {
    dropZone.addEventListener('click', () => fileInput.click());

    dropZone.addEventListener('dragover', e => {
      e.preventDefault();
      dropZone.classList.add('dragover');
    });

    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));

    dropZone.addEventListener('drop', e => {
      e.preventDefault();
      dropZone.classList.remove('dragover');
      if (e.dataTransfer.files.length) {
        fileInput.files = e.dataTransfer.files;
        updateFileLabel(e.dataTransfer.files[0]);
      }
    });

    fileInput.addEventListener('change', () => {
      if (fileInput.files.length) updateFileLabel(fileInput.files[0]);
    });

    function updateFileLabel(file) {
      if (fileLabel) {
        fileLabel.textContent = `📄 ${file.name} (${(file.size / 1024).toFixed(1)} KB)`;
        fileLabel.classList.add('text-sky-400');
      }
    }
  }

  // ── Confirm dangerous actions ─────────────────────────────
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
      const msg = el.dataset.confirm || 'Are you sure?';
      if (!confirm(msg)) e.preventDefault();
    });
  });

  // ── Mobile sidebar toggle ─────────────────────────────────
  const sidebarToggle = document.getElementById('sidebar-toggle');
  const sidebar       = document.getElementById('sidebar');
  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', () => sidebar.classList.toggle('open'));
  }

  // ── Hash truncation + copy-to-clipboard ──────────────────
  document.querySelectorAll('.hash-chip').forEach(chip => {
    const full = chip.title || chip.textContent;
    chip.style.cursor = 'pointer';
    chip.addEventListener('click', () => {
      navigator.clipboard.writeText(full).then(() => {
        const orig = chip.textContent;
        chip.textContent = '✓ Copied!';
        setTimeout(() => (chip.textContent = orig), 1500);
      });
    });
  });

  // ── Search filter (client-side for table rows) ────────────
  const searchInput = document.getElementById('table-search');
  if (searchInput) {
    searchInput.addEventListener('input', () => {
      const q     = searchInput.value.toLowerCase();
      const rows  = document.querySelectorAll('tbody tr');
      rows.forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  }

  // ── Animated counter for stat cards ──────────────────────
  document.querySelectorAll('[data-counter]').forEach(el => {
    const target = parseInt(el.dataset.counter, 10);
    let   current = 0;
    const step    = Math.max(1, Math.ceil(target / 40));
    const timer   = setInterval(() => {
      current = Math.min(current + step, target);
      el.textContent = current;
      if (current >= target) clearInterval(timer);
    }, 20);
  });

  // ── Theme Toggle (Light/Dark) ─────────────────────────────
  const themeBtn = document.getElementById('theme-toggle');
  const htmlDoc  = document.documentElement;
  
  function updateThemeIcon(theme) {
    if (!themeBtn) return;
    const sunIcon = themeBtn.querySelector('.sun-icon');
    const moonIcon = themeBtn.querySelector('.moon-icon');
    
    if (theme === 'dark') {
      sunIcon?.classList.remove('hidden');
      sunIcon?.classList.add('theme-toggle-animation');
      moonIcon?.classList.add('hidden');
    } else {
      sunIcon?.classList.add('hidden');
      moonIcon?.classList.remove('hidden');
      moonIcon?.classList.add('theme-toggle-animation');
    }
  }

  if (themeBtn) {
    // Initial icon state
    updateThemeIcon(htmlDoc.getAttribute('data-theme'));

    themeBtn.addEventListener('click', () => {
      const currentTheme = htmlDoc.getAttribute('data-theme');
      const newTheme = currentTheme === 'light' ? 'dark' : 'light';
      
      // 1. Update UI immediately
      htmlDoc.setAttribute('data-theme', newTheme);
      localStorage.setItem('rucu-theme', newTheme);
      updateThemeIcon(newTheme);
      
      // 2. Persist to Database if logged in
      fetch('update_theme.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ theme: newTheme })
      })
      .then(r => r.json())
      .then(data => {
        if (!data.success) console.error('Failed to save theme preference');
      })
      .catch(err => console.warn('Theme persistence unavailable (guest mode?)'));
    });
  }
});
