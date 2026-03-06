            </main>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-50 space-y-2"></div>

    <script <?= cspNonceAttr() ?>>
    function showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');

        const colors = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            warning: 'bg-yellow-500',
            info: 'bg-blue-500'
        };

        toast.className = `${colors[type] || colors.info} text-white px-4 py-3 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full`;
        toast.textContent = message;

        container.appendChild(toast);

        // Animate in
        requestAnimationFrame(() => {
            toast.classList.remove('translate-x-full');
        });

        // Remove after 3 seconds
        setTimeout(() => {
            toast.classList.add('translate-x-full');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // Handle HTMX events
    document.body.addEventListener('htmx:afterSwap', function(evt) {
        // Re-initialize any dynamic components after HTMX swap
    });
    </script>

    <?php if (isset($extraScripts)) echo $extraScripts; ?>
</body>
</html>
