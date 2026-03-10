<?php if (isLoggedIn()): ?>
    </main>
    
    <!-- Footer -->
    <footer class="border-t border-dark-700 mt-auto">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex flex-col md:flex-row justify-between items-center gap-2 text-xs text-gray-500">
                <div>&copy; <?php echo date('Y'); ?> Hammadde Takip Sistemi</div>
                <div>Oturum: <?php echo $user['full_name'] ?? 'Kullanici'; ?></div>
            </div>
        </div>
    </footer>
<?php endif; ?>

<script>
// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let valid = true;
            const required = form.querySelectorAll('[required]');
            required.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    field.classList.add('border-red-500');
                } else {
                    field.classList.remove('border-red-500');
                }
            });
            if (!valid) {
                e.preventDefault();
                alert('Lutfen tum zorunlu alanlari doldurun.');
            }
        });
    });
    
    // Auto-hide flash messages
    const flashMessages = document.querySelectorAll('.flash-message');
    flashMessages.forEach(msg => {
        setTimeout(() => {
            msg.style.opacity = '0';
            setTimeout(() => msg.remove(), 300);
        }, 3000);
    });
});
</script>
</body>
</html>
