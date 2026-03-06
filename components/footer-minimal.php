    </main>

    <!-- Toast Container (for notifications) -->
    <div class="toast-container" aria-live="polite" aria-atomic="true" role="status"></div>

    <!-- Minimal Footer for Auth Pages -->
    <footer class="bg-brand-darker border-t border-brand-yellow/80 py-8 px-4 sm:px-6 mt-auto">
        <div class="max-w-7xl mx-auto text-center">
            <p class="text-xs text-gray-600">
                &copy; <?= date('Y') ?> <?= h($_ENV['APP_NAME'] ?? 'Beach Finder') ?>. <?= h(__('footer.copyright')) ?>
            </p>
        </div>
    </footer>

    <!-- Minimal JS - only what's needed for auth pages -->
    <script <?= cspNonceAttr() ?>>
    // Toast notifications
    function showToast(message, type = 'info', duration = 4000) {
        let container = document.querySelector('.toast-container');
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.setAttribute('role', 'alert');
        const icons = { success: '✓', error: '✕', warning: '⚠', info: 'ℹ' };
        toast.innerHTML = `
            <span class="toast-icon">${icons[type] || icons.info}</span>
            <span class="toast-message">${message}</span>
            <button class="toast-close" aria-label="<?= h(__('common.close')) ?>">✕</button>
        `;
        toast.querySelector('.toast-close').onclick = () => removeToast(toast);
        container.appendChild(toast);
        requestAnimationFrame(() => toast.classList.add('show'));
        if (duration > 0) setTimeout(() => removeToast(toast), duration);
    }
    function removeToast(toast) {
        toast.classList.remove('show');
        toast.addEventListener('transitionend', () => toast.remove(), { once: true });
    }

    // Initialize Lucide icons
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
    </script>
</body>
</html>
