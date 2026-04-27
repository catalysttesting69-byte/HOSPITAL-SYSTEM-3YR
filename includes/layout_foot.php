  <!-- End of page padding div -->
</div><!-- .main-content -->

<script src="assets/js/app.js"></script>
<?php if (isset($_SESSION['user_id'])): ?>
<script>
    // Real-time Notification Polling
    function checkNotifications() {
        fetch('get_notifications.php')
            .then(response => response.json())
            .then(data => {
                const badges = document.querySelectorAll('.nav-badge');
                badges.forEach(badge => {
                    if (data.count > 0) {
                        badge.textContent = data.count;
                        badge.style.display = 'flex';
                    } else {
                        badge.style.display = 'none';
                    }
                });
            })
            .catch(err => console.error('Notification check failed:', err));
    }
    
    // Poll every 10 seconds
    setInterval(checkNotifications, 10000);
</script>
<?php endif; ?>
</body>
</html>
