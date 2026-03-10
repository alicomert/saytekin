            </div><!-- /content-area -->
        </div><!-- /main-content -->
    </div><!-- /app-container -->
    
    <!-- Footer -->
    <div style="border-top:1px solid #1e2430;margin-top:40px;padding:16px 28px;background:#0d1017;margin-left:240px;">
        <div style="display:flex;justify-content:space-between;align-items:center;font-size:11px;color:#475569;">
            <div>&copy; <?php echo date('Y'); ?> Hammadde Takip Sistemi</div>
            <div>Oturum: <?php echo $user['full_name'] ?? 'Kullanici'; ?></div>
        </div>
    </div>
<?php endif; ?>

<!-- Flash Messages -->
<?php if (isset($flashMessage) && $flashMessage): ?>
<div id="flash-message" class="flash-message flash-<?php echo $flashMessage['type']; ?>">
    <?php echo $flashMessage['message']; ?>
</div>
<script>
    setTimeout(() => {
        const el = document.getElementById('flash-message');
        if (el) {
            el.style.opacity = '0';
            el.style.transition = 'opacity 0.3s';
            setTimeout(() => el.remove(), 300);
        }
    }, 3000);
</script>
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
