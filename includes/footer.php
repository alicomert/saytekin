<?php if (isLoggedIn()): ?>
    </div>
    
    <!-- Footer -->
    <div style="border-top:1px solid #1e2430;margin-top:40px;padding:16px 28px;background:#0d1017;">
        <div style="max-width:1400px;margin:0 auto;display:flex;justify-content:space-between;align-items:center;font-size:11px;color:#475569;">
            <div>&copy; <?php echo date('Y'); ?> Hammadde Takip Sistemi</div>
            <div>Oturum: <?php echo $user['full_name'] ?? 'Kullanici'; ?></div>
        </div>
    </div>
<?php else: ?>
    </div>
<?php endif; ?>

<script>
// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let valid = true;
            const required = form.querySelectorAll('[required]');
            required.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    field.style.borderColor = '#ef4444';
                } else {
                    field.style.borderColor = '#1e2430';
                }
            });
            if (!valid) {
                e.preventDefault();
                alert('Lutfen tum zorunlu alanlari doldurun.');
            }
        });
    });
});
</script>
</body>
</html>