    </main>

    <!-- Toast Container (for notifications) -->
    <div class="toast-container" aria-live="polite" aria-atomic="true" role="status">
        <!-- Toasts will be dynamically added here -->
    </div>

    <!-- Footer - Dark Glassmorphism -->
    <footer class="bg-brand-darker border-t border-brand-yellow/80 pt-16 pb-8 px-4 sm:px-6 mt-auto relative overflow-hidden">
        <!-- Decorative palm tree (bottom right) -->
        <div class="absolute bottom-0 right-0 w-64 h-64 opacity-5 pointer-events-none">
            <svg viewBox="0 0 100 100" fill="currentColor" class="text-brand-yellow w-full h-full">
                <path d="M48 95V55M52 95V55M50 55C50 55 35 45 20 50C25 45 40 35 50 40C40 35 25 25 15 25C30 25 45 35 50 40M50 55C50 55 65 45 80 50C75 45 60 35 50 40C60 35 75 25 85 25C70 25 55 35 50 40"/>
            </svg>
        </div>

        <div class="max-w-7xl mx-auto relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12">
                <!-- Brand Column -->
                <div>
                    <div class="flex items-center gap-2 mb-6">
                        <i data-lucide="sun" class="w-6 h-6 text-brand-yellow"></i>
                        <span class="text-xl font-bold text-white">Beach Finder</span>
                    </div>
                    <p class="text-gray-400 text-sm mb-4">
                        Curating the most breathtaking coastal experiences Puerto Rico has to offer. Find your paradise today.
                    </p>
                    <!-- Tools -->
                    <div class="space-y-2 mt-6">
                        <h5 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Tools</h5>
                        <ul class="space-y-2 text-sm">
                            <li><a href="/quiz" class="text-gray-400 hover:text-brand-yellow transition-colors flex items-center gap-2">
                                <i data-lucide="compass" class="w-4 h-4"></i>
                                Beach Match Quiz
                            </a></li>
                            <li><a href="/compare" class="text-gray-400 hover:text-brand-yellow transition-colors flex items-center gap-2">
                                <i data-lucide="git-compare" class="w-4 h-4"></i>
                                Compare Beaches
                            </a></li>
                            <li><a href="/#map" class="text-gray-400 hover:text-brand-yellow transition-colors flex items-center gap-2">
                                <i data-lucide="map" class="w-4 h-4"></i>
                                Interactive Map
                            </a></li>
                        </ul>
                    </div>
                </div>

                <!-- Beaches by Activity -->
                <div>
                    <h4 class="font-bold text-white mb-4 flex items-center gap-2">
                        <i data-lucide="waves" class="w-5 h-5 text-brand-yellow"></i>
                        Beaches by Activity
                    </h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="/best-beaches" class="text-gray-400 hover:text-brand-yellow transition-colors">Best Beaches</a></li>
                        <li><a href="/best-beaches-san-juan" class="text-gray-400 hover:text-brand-yellow transition-colors">San Juan Beaches</a></li>
                        <li><a href="/best-surfing-beaches" class="text-gray-400 hover:text-brand-yellow transition-colors">Surfing Beaches</a></li>
                        <li><a href="/best-snorkeling-beaches" class="text-gray-400 hover:text-brand-yellow transition-colors">Snorkeling Beaches</a></li>
                        <li><a href="/best-family-beaches" class="text-gray-400 hover:text-brand-yellow transition-colors">Family Beaches</a></li>
                        <li><a href="/hidden-beaches-puerto-rico" class="text-gray-400 hover:text-brand-yellow transition-colors">Hidden Beaches</a></li>
                    </ul>
                    <!-- Beaches by Location -->
                    <div class="mt-6">
                        <h5 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">By Location</h5>
                        <ul class="space-y-2 text-sm">
                            <li><a href="/beaches-near-san-juan" class="text-gray-400 hover:text-brand-yellow transition-colors">Near San Juan</a></li>
                            <li><a href="/beaches-near-san-juan-airport" class="text-gray-400 hover:text-brand-yellow transition-colors">Near Airport</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Planning Resources -->
                <div>
                    <h4 class="font-bold text-white mb-4 flex items-center gap-2">
                        <i data-lucide="book-open" class="w-5 h-5 text-brand-yellow"></i>
                        Planning Resources
                    </h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="/guides" class="text-gray-400 hover:text-brand-yellow transition-colors font-medium">All Guides →</a></li>
                        <li><a href="/guides/getting-to-puerto-rico-beaches" class="text-gray-400 hover:text-brand-yellow transition-colors">Transportation</a></li>
                        <li><a href="/guides/beach-safety-tips" class="text-gray-400 hover:text-brand-yellow transition-colors">Safety Tips</a></li>
                        <li><a href="/guides/best-time-visit-puerto-rico-beaches" class="text-gray-400 hover:text-brand-yellow transition-colors">Best Times to Visit</a></li>
                        <li><a href="/guides/beach-packing-list" class="text-gray-400 hover:text-brand-yellow transition-colors">Packing List</a></li>
                        <li><a href="/guides/culebra-vs-vieques" class="text-gray-400 hover:text-brand-yellow transition-colors">Culebra vs Vieques</a></li>
                        <li><a href="/guides/bioluminescent-bays" class="text-gray-400 hover:text-brand-yellow transition-colors">Bio Bays</a></li>
                        <li><a href="/guides/snorkeling-guide" class="text-gray-400 hover:text-brand-yellow transition-colors">Snorkeling Guide</a></li>
                        <li><a href="/guides/surfing-guide" class="text-gray-400 hover:text-brand-yellow transition-colors">Surfing Guide</a></li>
                    </ul>
                </div>

                <!-- More Guides & Account -->
                <div>
                    <h4 class="font-bold text-white mb-4 flex items-center gap-2">
                        <i data-lucide="lightbulb" class="w-5 h-5 text-brand-yellow"></i>
                        More Guides
                    </h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="/guides/beach-photography-tips" class="text-gray-400 hover:text-brand-yellow transition-colors">Photography Tips</a></li>
                        <li><a href="/guides/family-beach-vacation-planning" class="text-gray-400 hover:text-brand-yellow transition-colors">Family Planning</a></li>
                    </ul>

                    <?php if (isAuthenticated()): ?>
                    <!-- Authenticated User -->
                    <div class="mt-6">
                        <h5 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Your Account</h5>
                        <ul class="space-y-2 text-sm">
                            <li><a href="/favorites" class="text-gray-400 hover:text-brand-yellow transition-colors flex items-center gap-2">
                                <i data-lucide="heart" class="w-4 h-4"></i>
                                My Favorites
                            </a></li>
                            <li><a href="/profile" class="text-gray-400 hover:text-brand-yellow transition-colors flex items-center gap-2">
                                <i data-lucide="user" class="w-4 h-4"></i>
                                Profile
                            </a></li>
                        </ul>
                    </div>
                    <?php else: ?>
                    <!-- Guest User -->
                    <div class="mt-6">
                        <a href="/login" class="inline-flex items-center gap-2 bg-brand-yellow hover:bg-yellow-300 text-brand-darker px-4 py-2 rounded-lg font-medium text-sm transition-colors">
                            <i data-lucide="log-in" class="w-4 h-4"></i>
                            Sign In
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Copyright -->
            <div class="border-t border-white/5 pt-8 mt-12">
                <p class="text-xs text-gray-400 text-center">
                    &copy; <?= date('Y') ?> Beach Finder. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    <?php if (!isset($skipMapScripts) || !$skipMapScripts): ?>
    <!-- MapLibre GL JS (defer for non-blocking load) -->
    <script defer
            src="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.js"
            integrity="sha384-3WUbXI7T+/GIrWP/5MDMjhzLyHQ+0utF3PnJ7ozD7UeN1/bbZ96Hk+Vvd024VYfW"
            crossorigin="anonymous"></script>
    <?php endif; ?>

    <?php if (!isset($skipAppScripts) || !$skipAppScripts): ?>
    <script>
    window.BeachFinderMeta = {
        authenticated: <?= isAuthenticated() ? '1' : '0' ?>,
        user_id: <?= isAuthenticated() ? json_encode((string)($_SESSION['user_id'] ?? '')) : 'null' ?>
    };
    window.BF_CONFIG = Object.assign({}, window.BF_CONFIG || {}, {
        plunkPublicKey: <?= json_encode((string) env('PLUNK_PUBLIC_KEY', '')) ?>,
        plunkBaseUrl: <?= json_encode((string) env('PLUNK_BASE_URL', 'https://next-api.useplunk.com')) ?>,
        appEnv: <?= json_encode((string) appEnv()) ?>
    });
    </script>
    <!-- App JavaScript (defer for non-blocking load) -->
    <script defer src="/assets/js/app.min.js"></script>
    <script defer src="/assets/js/geolocation.js"></script>
    <script defer src="/assets/js/filters.js"></script>
    <script defer src="/assets/js/analytics.js"></script>
    <script defer src="/assets/js/plunk-client.js"></script>
    <script defer src="/assets/js/share.js"></script>
    <?php endif; ?>

    <!-- Initialize Lucide Icons -->
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
    // Re-initialize after HTMX swaps
    document.body.addEventListener('htmx:afterSwap', () => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
    </script>

    <!-- PWA Service Worker & Install Prompt -->
    <script>
    // Register service worker
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', async () => {
            try {
                const registration = await navigator.serviceWorker.register('/sw.js');
                console.log('SW registered:', registration.scope);

                // Check for updates
                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing;
                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            // New version available
                            if (confirm('A new version is available! Reload to update?')) {
                                newWorker.postMessage('skipWaiting');
                                window.location.reload();
                            }
                        }
                    });
                });
            } catch (error) {
                console.error('SW registration failed:', error);
            }
        });
    }

    // PWA Install Prompt
    let deferredPrompt;
    const installBanner = document.getElementById('pwa-install-banner');

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;

        // Show install banner if not dismissed before
        if (!localStorage.getItem('pwa-install-dismissed') && installBanner) {
            installBanner.classList.remove('hidden');
        }
    });

    function installPWA() {
        if (!deferredPrompt) return;

        deferredPrompt.prompt();
        deferredPrompt.userChoice.then((result) => {
            if (result.outcome === 'accepted') {
                console.log('PWA installed');
            }
            deferredPrompt = null;
            if (installBanner) installBanner.classList.add('hidden');
        });
    }

    function dismissInstall() {
        localStorage.setItem('pwa-install-dismissed', 'true');
        if (installBanner) installBanner.classList.add('hidden');
    }

    // Detect if running as PWA
    if (window.matchMedia('(display-mode: standalone)').matches) {
        document.body.classList.add('pwa-standalone');
    }
    </script>

    <!-- PWA Install Banner (hidden by default) -->
    <div id="pwa-install-banner" class="hidden fixed bottom-4 left-4 right-4 md:left-auto md:right-4 md:w-96 bg-brand-dark/95 backdrop-blur-md rounded-xl shadow-glass border border-white/10 p-4 z-50">
        <div class="flex items-start gap-3">
            <img src="/assets/icons/icon-72x72.png" alt="" class="w-12 h-12 rounded-lg">
            <div class="flex-1">
                <h3 class="font-semibold text-white">Install Beach Finder</h3>
                <p class="text-sm text-gray-400 mt-1">Add to your home screen for quick access and offline features.</p>
                <div class="flex gap-2 mt-3">
                    <button type="button" onclick="installPWA()" class="bg-brand-yellow hover:bg-yellow-300 text-brand-darker px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        Install
                    </button>
                    <button type="button" onclick="dismissInstall()" class="text-gray-400 hover:text-white px-4 py-2 text-sm font-medium transition-colors">
                        Not now
                    </button>
                </div>
            </div>
            <button type="button" onclick="dismissInstall()" class="text-gray-500 hover:text-white transition-colors" aria-label="Dismiss install banner">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- Sign-Up Prompt Modal -->
    <div id="signup-prompt-modal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 hidden items-center justify-center p-4"
         role="dialog" aria-modal="true" aria-labelledby="signup-prompt-title" onclick="closeSignupPrompt()">
        <div class="bg-brand-dark rounded-xl shadow-glass max-w-md w-full border border-white/10 overflow-hidden" onclick="event.stopPropagation()">
            <!-- Header with icon -->
            <div class="bg-gradient-to-r from-brand-yellow/20 to-brand-yellow/5 px-6 py-5 border-b border-white/10">
                <div class="flex items-center gap-3">
                    <div id="signup-prompt-icon" class="w-12 h-12 rounded-full bg-brand-yellow/20 flex items-center justify-center">
                        <i data-lucide="heart" class="w-6 h-6 text-brand-yellow"></i>
                    </div>
                    <div>
                        <h2 id="signup-prompt-title" class="text-lg font-semibold text-white">Create Free Account</h2>
                        <p id="signup-prompt-subtitle" class="text-sm text-gray-400">Save beaches and get personalized picks</p>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="p-6">
                <p id="signup-prompt-description" class="text-gray-300 text-sm mb-5">
                    Create a free account to save beaches, write reviews, and get personalized recommendations.
                </p>

                <!-- Benefits list -->
                <ul class="space-y-2 mb-6">
                    <li class="flex items-center gap-2 text-sm text-gray-300">
                        <i data-lucide="heart" class="w-4 h-4 text-brand-yellow"></i>
                        <span>Save your favorite beaches</span>
                    </li>
                    <li class="flex items-center gap-2 text-sm text-gray-300">
                        <i data-lucide="star" class="w-4 h-4 text-brand-yellow"></i>
                        <span>Write reviews and help others</span>
                    </li>
                    <li class="flex items-center gap-2 text-sm text-gray-300">
                        <i data-lucide="map-pin" class="w-4 h-4 text-brand-yellow"></i>
                        <span>Track beaches you've visited</span>
                    </li>
                </ul>

                <!-- Actions -->
                <div class="flex flex-col gap-3">
                    <a href="/login" id="signup-prompt-cta" class="flex items-center justify-center gap-2 bg-brand-yellow hover:bg-yellow-300 text-brand-darker py-3 rounded-lg font-semibold transition-colors">
                        <i data-lucide="log-in" class="w-5 h-5"></i>
                        Sign Up Free
                    </a>
                    <button type="button" onclick="closeSignupPrompt()" class="text-gray-400 hover:text-white py-2 text-sm font-medium transition-colors">
                        Continue browsing
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Sign-Up Prompt JavaScript -->
    <script>
    function showSignupPrompt(context = 'favorites', redirectUrl = null) {
        const modal = document.getElementById('signup-prompt-modal');
        const title = document.getElementById('signup-prompt-title');
        const subtitle = document.getElementById('signup-prompt-subtitle');
        const description = document.getElementById('signup-prompt-description');
        const icon = document.getElementById('signup-prompt-icon');
        const cta = document.getElementById('signup-prompt-cta');

        // Customize content based on context
        const contexts = {
            favorites: {
                title: 'Create Free Account',
                subtitle: 'Save beaches and get personalized picks',
                description: 'Create a free account to save beaches, write reviews, and get personalized recommendations.',
                icon: 'heart'
            },
            reviews: {
                title: 'Share Your Experience',
                subtitle: 'Help the community discover great beaches',
                description: 'Sign in to write reviews, rate beaches, and share tips with fellow beach lovers.',
                icon: 'message-circle'
            },
            photos: {
                title: 'Share Your Photos',
                subtitle: 'Show others what this beach looks like',
                description: 'Sign in to upload photos and help others see the real beach conditions.',
                icon: 'camera'
            }
        };

        const config = contexts[context] || contexts.favorites;

        title.textContent = config.title;
        subtitle.textContent = config.subtitle;
        description.textContent = config.description;
        icon.innerHTML = `<i data-lucide="${config.icon}" class="w-6 h-6 text-brand-yellow"></i>`;

        // Set redirect URL
        const loginUrl = redirectUrl ? `/login?redirect=${encodeURIComponent(redirectUrl)}` : '/login';
        cta.href = loginUrl;

        // Show modal
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';

        // Re-init lucide icons
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function closeSignupPrompt() {
        const modal = document.getElementById('signup-prompt-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }

    // Close on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeSignupPrompt();
        }
    });
    </script>

    <!-- Welcome Popup (Registration CTA for non-authenticated visitors) -->
    <?php
    // Include Google OAuth helper if not already loaded
    if (!function_exists('isGoogleOAuthEnabled')) {
        require_once __DIR__ . '/../inc/google-oauth.php';
    }
    ?>
    <?php if (!isAuthenticated()): ?>
    <div id="welcome-popup-overlay" class="welcome-popup-overlay" role="dialog" aria-modal="true" aria-labelledby="welcome-popup-title" style="position:fixed;inset:0;opacity:0;visibility:hidden;">
        <div class="welcome-popup">
            <!-- Close button -->
            <button type="button" onclick="dismissWelcomePopup()" class="welcome-popup-close" aria-label="Close">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
                </svg>
            </button>

            <!-- Hero image -->
            <div class="welcome-popup-hero">
                <img src="/images/beaches/flamenco-beach-culebra.webp"
                     alt="Beautiful Flamenco Beach in Culebra"
                     loading="lazy">
            </div>

            <!-- Content -->
            <div class="welcome-popup-body">
                <h2 id="welcome-popup-title" class="welcome-popup-title">
                    <span>🏝️</span> Welcome, Explorer!
                </h2>
                <p class="welcome-popup-subtitle">
                    Join <?= number_format(getSiteStats()['total_users'] ?: 500) ?>+ beach lovers discovering Puerto Rico's hidden gems
                </p>

                <!-- Benefits -->
                <ul class="welcome-popup-benefits">
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                        </svg>
                        <span>Save your favorite beaches</span>
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
                        </svg>
                        <span>Get personalized recommendations</span>
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                        </svg>
                        <span>Leave reviews & share photos</span>
                    </li>
                </ul>

                <!-- Actions -->
                <div class="welcome-popup-actions">
                    <?php if (isGoogleOAuthEnabled()): ?>
                    <a href="/auth/google/" class="welcome-popup-btn-google">
                        <svg viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        Continue with Google
                    </a>
                    <?php endif; ?>

                    <a href="/login?method=email" class="welcome-popup-btn-email">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                        </svg>
                        Sign in with Email
                    </a>

                    <div class="welcome-popup-divider">
                        <span>or</span>
                    </div>

                    <button type="button" onclick="dismissWelcomePopup()" class="welcome-popup-btn-dismiss">
                        Maybe Later
                    </button>
                </div>

                <!-- Trust signal -->
                <div class="welcome-popup-trust">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <span>We never post without your permission</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Welcome Popup JavaScript -->
    <script>
    (function() {
        const POPUP_DELAY = 12000;  // 12 seconds
        const DISMISS_DURATION = 7 * 24 * 60 * 60 * 1000;  // 7 days in ms
        const STORAGE_KEY = 'welcome_popup_dismissed';

        let popupShown = false;
        let popupTimer = null;
        let scrollObserver = null;

        function shouldShowWelcomePopup() {
            // Skip if already shown this session
            if (popupShown) return false;

            // Skip if dismissed recently
            const dismissed = localStorage.getItem(STORAGE_KEY);
            if (dismissed) {
                const dismissedAt = parseInt(dismissed, 10);
                if (Date.now() - dismissedAt < DISMISS_DURATION) {
                    return false;
                }
            }

            // Skip if user came from login/verify pages (avoid immediate re-prompt).
            let refPath = '';
            if (document.referrer) {
                try {
                    refPath = new URL(document.referrer).pathname || '';
                } catch (e) {
                    refPath = '';
                }
            }

            if (refPath === '/login' || refPath === '/login.php') {
                return false;
            }

            if (refPath === '/verify' || refPath === '/verify.php') {
                return false;
            }

            return true;
        }

        function showWelcomePopup() {
            if (popupShown || !shouldShowWelcomePopup()) return;

            popupShown = true;
            const overlay = document.getElementById('welcome-popup-overlay');
            if (overlay) {
                // Clear inline fallback styles
                overlay.style.opacity = '';
                overlay.style.visibility = '';
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden';

                // Clean up timer and observer
                if (popupTimer) clearTimeout(popupTimer);
                if (scrollObserver) scrollObserver.disconnect();
            }
        }

        window.dismissWelcomePopup = function() {
            const overlay = document.getElementById('welcome-popup-overlay');
            if (overlay) {
                overlay.classList.remove('active');
                // Restore inline fallback styles
                overlay.style.opacity = '0';
                overlay.style.visibility = 'hidden';
                document.body.style.overflow = '';
            }
            // Store dismissal timestamp
            localStorage.setItem(STORAGE_KEY, Date.now().toString());
        };

        function initWelcomePopup() {
            if (!shouldShowWelcomePopup()) return;

            // Only show on homepage
            if (window.location.pathname !== '/' && window.location.pathname !== '/index.php') {
                return;
            }

            // Trigger 1: Timer after delay
            popupTimer = setTimeout(showWelcomePopup, POPUP_DELAY);

            // Trigger 2: User scrolls past hero section
            const heroSection = document.querySelector('header.min-h-screen');
            if (heroSection && 'IntersectionObserver' in window) {
                scrollObserver = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        // When hero is no longer visible (user scrolled past)
                        if (!entry.isIntersecting && entry.boundingClientRect.top < 0) {
                            showWelcomePopup();
                        }
                    });
                }, {
                    threshold: 0,
                    rootMargin: '-100px 0px 0px 0px'
                });
                scrollObserver.observe(heroSection);
            }
        }

        // Close on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const overlay = document.getElementById('welcome-popup-overlay');
                if (overlay && overlay.classList.contains('active')) {
                    dismissWelcomePopup();
                }
            }
        });

        // Close on overlay click
        document.getElementById('welcome-popup-overlay')?.addEventListener('click', function(e) {
            if (e.target === this) {
                dismissWelcomePopup();
            }
        });

        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initWelcomePopup);
        } else {
            initWelcomePopup();
        }
    })();
    </script>
    <?php endif; ?>

    <!-- Review Form Modal -->
    <div id="review-modal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 hidden items-center justify-center p-4"
         role="dialog" aria-modal="true" aria-labelledby="review-modal-title" onclick="closeReviewForm()">
        <div class="bg-brand-dark rounded-xl shadow-glass max-w-lg w-full max-h-[90vh] overflow-y-auto border border-white/10" onclick="event.stopPropagation()">
            <div class="sticky top-0 bg-brand-dark border-b border-white/10 px-6 py-4 flex items-center justify-between">
                <h2 id="review-modal-title" class="text-xl font-semibold text-white">Write a Review</h2>
                <button onclick="closeReviewForm()" class="text-gray-400 hover:text-white p-1 transition-colors" aria-label="Close review form">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>

            <form id="review-form" class="p-6 space-y-5" onsubmit="submitReview(event)">
                <input type="hidden" name="beach_id" id="review-beach-id">
                <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">

                <!-- Beach Name (display only) -->
                <div>
                    <div class="text-sm text-gray-500 mb-1">Reviewing</div>
                    <div id="review-beach-name" class="font-semibold text-white"></div>
                </div>

                <!-- Star Rating -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Your Rating <span class="text-red-400">*</span></label>
                    <div class="flex gap-1" id="star-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <button type="button" class="star-btn text-3xl text-gray-600 hover:text-yellow-400 transition-colors" data-rating="<?= $i ?>" onclick="setRating(<?= $i ?>)">★</button>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="rating" id="review-rating" value="0" required>
                    <p class="text-red-400 text-sm mt-1 hidden" id="rating-error">Please select a rating</p>
                </div>

                <!-- Title -->
                <div>
                    <label for="review-title" class="block text-sm font-medium text-gray-300 mb-1">Title (optional)</label>
                    <input type="text" name="title" id="review-title" maxlength="100" placeholder="Summarize your experience"
                           class="w-full px-3 py-2 bg-white/5 border border-white/20 rounded-lg text-white placeholder-gray-500 focus:ring-2 focus:ring-brand-yellow/50 focus:border-brand-yellow/50">
                </div>

                <!-- Review Text -->
                <div>
                    <label for="review-text" class="block text-sm font-medium text-gray-300 mb-1">Your Review</label>
                    <textarea name="review_text" id="review-text" rows="4" maxlength="2000" placeholder="Share your experience at this beach..."
                              class="w-full px-3 py-2 bg-white/5 border border-white/20 rounded-lg text-white placeholder-gray-500 focus:ring-2 focus:ring-brand-yellow/50 focus:border-brand-yellow/50 resize-none"></textarea>
                    <div class="text-xs text-gray-500 text-right mt-1"><span id="char-count">0</span>/2000</div>
                </div>

                <!-- Visit Details -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="review-visit-date" class="block text-sm font-medium text-gray-300 mb-1">When did you visit?</label>
                        <input type="month" name="visit_date" id="review-visit-date"
                               class="w-full px-3 py-2 bg-white/5 border border-white/20 rounded-lg text-white focus:ring-2 focus:ring-brand-yellow/50 focus:border-brand-yellow/50">
                    </div>
                    <div>
                        <label for="review-visit-type" class="block text-sm font-medium text-gray-300 mb-1">Trip type</label>
                        <select name="visit_type" id="review-visit-type"
                                class="w-full px-3 py-2 bg-white/5 border border-white/20 rounded-lg text-white focus:ring-2 focus:ring-brand-yellow/50 focus:border-brand-yellow/50">
                            <option value="" class="bg-brand-dark">Select...</option>
                            <option value="solo" class="bg-brand-dark">Solo</option>
                            <option value="couple" class="bg-brand-dark">Couple</option>
                            <option value="family" class="bg-brand-dark">Family</option>
                            <option value="friends" class="bg-brand-dark">Friends</option>
                            <option value="group" class="bg-brand-dark">Group</option>
                        </select>
                    </div>
                </div>

                <!-- Would Recommend -->
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="would_recommend" id="review-recommend" checked
                           class="w-4 h-4 text-brand-yellow bg-white/5 border-white/20 rounded focus:ring-brand-yellow/50">
                    <label for="review-recommend" class="text-sm text-gray-300">I would recommend this beach</label>
                </div>

                <!-- Submit -->
                <div class="flex gap-3 pt-2">
                    <button type="submit" id="review-submit-btn"
                            class="flex-1 bg-brand-yellow hover:bg-yellow-300 text-brand-darker py-3 rounded-lg font-medium transition-colors">
                        Submit Review
                    </button>
                    <button type="button" onclick="closeReviewForm()"
                            class="px-6 py-3 border border-white/20 text-gray-300 rounded-lg font-medium hover:bg-white/5 hover:text-white transition-colors">
                        Cancel
                    </button>
                </div>

                <!-- Error Message -->
                <div id="review-error" class="hidden bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-lg text-sm"></div>
                <!-- Success Message -->
                <div id="review-success" class="hidden bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg text-sm"></div>
            </form>
        </div>
    </div>

    <!-- Review Form JavaScript -->
    <script>
    let currentReviewBeachId = null;

    function openReviewForm(beachId, beachName) {
        currentReviewBeachId = beachId;
        document.getElementById('review-beach-id').value = beachId;
        document.getElementById('review-beach-name').textContent = beachName || 'this beach';
        document.getElementById('review-modal').classList.remove('hidden');
        document.getElementById('review-modal').classList.add('flex');
        document.body.style.overflow = 'hidden';

        // Reset form
        document.getElementById('review-form').reset();
        document.getElementById('review-rating').value = '0';
        document.querySelectorAll('.star-btn').forEach(btn => btn.classList.remove('text-yellow-400'));
        document.querySelectorAll('.star-btn').forEach(btn => btn.classList.add('text-gray-600'));
        document.getElementById('review-error').classList.add('hidden');
        document.getElementById('review-success').classList.add('hidden');
        document.getElementById('char-count').textContent = '0';
    }

    function closeReviewForm() {
        document.getElementById('review-modal').classList.add('hidden');
        document.getElementById('review-modal').classList.remove('flex');
        document.body.style.overflow = '';
    }

    function setRating(rating) {
        document.getElementById('review-rating').value = rating;
        document.getElementById('rating-error').classList.add('hidden');

        document.querySelectorAll('.star-btn').forEach((btn, index) => {
            if (index < rating) {
                btn.classList.add('text-yellow-400');
                btn.classList.remove('text-gray-600');
            } else {
                btn.classList.remove('text-yellow-400');
                btn.classList.add('text-gray-600');
            }
        });
    }

    // Character counter
    document.getElementById('review-text')?.addEventListener('input', function() {
        document.getElementById('char-count').textContent = this.value.length;
    });

    async function submitReview(event) {
        event.preventDefault();

        const form = document.getElementById('review-form');
        const submitBtn = document.getElementById('review-submit-btn');
        const errorDiv = document.getElementById('review-error');
        const successDiv = document.getElementById('review-success');

        // Validate rating
        const rating = document.getElementById('review-rating').value;
        if (!rating || rating === '0') {
            document.getElementById('rating-error').classList.remove('hidden');
            return;
        }

        // Disable submit button
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';
        errorDiv.classList.add('hidden');
        successDiv.classList.add('hidden');

        try {
            const formData = new FormData(form);
            const response = await fetch('/api/submit-review.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                successDiv.textContent = data.message || 'Review submitted successfully!';
                successDiv.classList.remove('hidden');

                // Close modal after delay and reload
                setTimeout(() => {
                    closeReviewForm();
                    window.location.reload();
                }, 1500);
            } else {
                errorDiv.textContent = data.error || 'Failed to submit review';
                errorDiv.classList.remove('hidden');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Review';
            }
        } catch (error) {
            console.error('Review submission error:', error);
            errorDiv.textContent = 'Network error. Please try again.';
            errorDiv.classList.remove('hidden');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Review';
        }
    }

    // Close on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeReviewForm();
        }
    });
    </script>

    <?php if (isset($extraScripts)) echo $extraScripts; ?>

    <!-- Beach Comparison Bar -->
    <div id="compare-bar" class="fixed bottom-0 left-0 right-0 bg-brand-dark/95 backdrop-blur-md border-t border-white/10 shadow-glass transform translate-y-full transition-transform duration-300 z-40" role="region" aria-label="Beach comparison selection">
        <div class="max-w-7xl mx-auto px-4 py-3">
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <i data-lucide="git-compare" class="w-5 h-5 text-brand-yellow" aria-hidden="true"></i>
                    <span class="font-medium text-white">Compare</span>
                    <span id="compare-count" class="bg-brand-yellow text-brand-darker text-xs font-bold px-2 py-0.5 rounded-full">0</span>
                </div>

                <div id="compare-beaches" class="flex-1 flex gap-2 overflow-x-auto" role="list">
                    <!-- Beach thumbnails added here by JS -->
                </div>

                <div class="flex gap-2 flex-shrink-0">
                    <button type="button"
                            onclick="goToCompare()"
                            id="compare-go-btn"
                            disabled
                            class="bg-brand-yellow hover:bg-yellow-300 disabled:bg-gray-600 disabled:cursor-not-allowed text-brand-darker px-4 py-2 rounded-lg font-medium transition-colors text-sm">
                        Compare Now
                    </button>
                    <button type="button"
                            onclick="clearCompareSelection()"
                            class="text-gray-400 hover:text-white p-2 transition-colors"
                            aria-label="Clear comparison selection">
                        <i data-lucide="x" class="w-5 h-5" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Comparison JavaScript -->
    <script>
    // Comparison state stored in localStorage
    const COMPARE_KEY = 'beach-compare';
    const MAX_COMPARE = 3;

    function getCompareBeaches() {
        try {
            return JSON.parse(localStorage.getItem(COMPARE_KEY)) || [];
        } catch {
            return [];
        }
    }

    function setCompareBeaches(beaches) {
        localStorage.setItem(COMPARE_KEY, JSON.stringify(beaches));
        updateCompareBar();
        updateCompareButtons();
    }

    function toggleCompare(beachId, beachName, coverImage, btn) {
        let beaches = getCompareBeaches();
        const existing = beaches.findIndex(b => b.id === beachId);

        if (existing >= 0) {
            // Remove from comparison
            beaches.splice(existing, 1);
            btn.classList.remove('bg-cyan-500', 'text-white');
            btn.classList.add('bg-amber-100', 'text-stone-700');
            btn.setAttribute('aria-label', `Add ${beachName} to comparison`);
        } else {
            // Add to comparison
            if (beaches.length >= MAX_COMPARE) {
                showToast(`Maximum ${MAX_COMPARE} beaches can be compared`, 'warning', 3000);
                return;
            }
            beaches.push({ id: beachId, name: beachName, image: coverImage });
            btn.classList.remove('bg-amber-100', 'text-stone-700');
            btn.classList.add('bg-cyan-500', 'text-white');
            btn.setAttribute('aria-label', `Remove ${beachName} from comparison`);
        }

        setCompareBeaches(beaches);
    }

    function removeFromCompareBar(beachId) {
        let beaches = getCompareBeaches();
        beaches = beaches.filter(b => b.id !== beachId);
        setCompareBeaches(beaches);
    }

    function clearCompareSelection() {
        setCompareBeaches([]);
    }

    function goToCompare() {
        const beaches = getCompareBeaches();
        if (beaches.length >= 2) {
            window.location.href = '/compare?beaches=' + beaches.map(b => b.id).join(',');
        }
    }

    function updateCompareBar() {
        const beaches = getCompareBeaches();
        const bar = document.getElementById('compare-bar');
        const container = document.getElementById('compare-beaches');
        const countEl = document.getElementById('compare-count');
        const goBtn = document.getElementById('compare-go-btn');

        if (!bar || !container) return;

        // Update count
        countEl.textContent = beaches.length;

        // Show/hide bar
        if (beaches.length > 0) {
            bar.classList.remove('translate-y-full');
        } else {
            bar.classList.add('translate-y-full');
        }

        // Enable/disable compare button
        goBtn.disabled = beaches.length < 2;

        // Render beach thumbnails
        container.innerHTML = beaches.map(beach => `
            <div class="flex items-center gap-2 bg-white/10 rounded-lg px-2 py-1.5 flex-shrink-0 border border-white/10" role="listitem">
                <img src="${beach.image || '/images/beaches/placeholder-beach.webp'}" alt="" class="w-8 h-8 rounded object-cover">
                <span class="text-sm font-medium text-white max-w-24 truncate">${escapeHtmlCompare(beach.name)}</span>
                <button onclick="removeFromCompareBar('${beach.id}')" class="text-gray-400 hover:text-red-400 p-0.5 transition-colors" aria-label="Remove ${escapeHtmlCompare(beach.name)} from comparison">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        `).join('');

        // Re-init icons
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function updateCompareButtons() {
        const beaches = getCompareBeaches();
        const beachIds = beaches.map(b => b.id);

        document.querySelectorAll('.compare-btn').forEach(btn => {
            const beachId = btn.dataset.beachId;
            if (beachIds.includes(beachId)) {
                btn.classList.remove('bg-amber-100', 'text-stone-700');
                btn.classList.add('bg-cyan-500', 'text-white');
            } else {
                btn.classList.remove('bg-cyan-500', 'text-white');
                btn.classList.add('bg-amber-100', 'text-stone-700');
            }
        });
    }

    function escapeHtmlCompare(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', () => {
        updateCompareBar();
        updateCompareButtons();
    });

    // Update after HTMX swaps (new beach cards loaded)
    document.body.addEventListener('htmx:afterSwap', () => {
        updateCompareButtons();
    });
    </script>
</body>
</html>
