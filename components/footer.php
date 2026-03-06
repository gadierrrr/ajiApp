<?php
$currentLang = $currentLang ?? getCurrentLanguage();
$homePath = routeUrl('home', $currentLang);
$homeAnchorHref = static function (string $anchor) use ($homePath): string {
    return $homePath . '#' . ltrim($anchor, '#');
};
?>

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
                        <span class="text-xl font-bold text-white"><?= h($_ENV['APP_NAME'] ?? 'Beach Finder') ?></span>
                    </div>
                    <p class="text-gray-400 text-sm mb-4">
                        <?= h(__('footer.about', ['count' => '300+'])) ?>
                    </p>
                    <!-- Tools -->
                    <div class="space-y-2 mt-6">
                        <h5 class="text-xs font-semibold text-gray-400 uppercase tracking-wider"><?= h(__('footer.tools')) ?></h5>
                        <ul class="space-y-2 text-sm">
                            <li><a href="<?= h(routeUrl('quiz', $currentLang)) ?>" class="text-gray-400 hover:text-brand-yellow transition-colors flex items-center gap-2">
                                <i data-lucide="compass" class="w-4 h-4"></i>
                                <?= h(__('footer.beach_match_quiz')) ?>
                            </a></li>
                            <li><a href="<?= h(routeUrl('compare', $currentLang)) ?>" class="text-gray-400 hover:text-brand-yellow transition-colors flex items-center gap-2">
                                <i data-lucide="git-compare" class="w-4 h-4"></i>
                                <?= h(__('footer.compare_beaches')) ?>
                            </a></li>
                            <li><a href="<?= h($homeAnchorHref('map')) ?>" class="text-gray-400 hover:text-brand-yellow transition-colors flex items-center gap-2">
                                <i data-lucide="map" class="w-4 h-4"></i>
                                <?= h(__('footer.interactive_map')) ?>
                            </a></li>
                        </ul>
                    </div>
                </div>

                <!-- Beaches by Activity -->
                <div>
                    <h4 class="font-bold text-white mb-4 flex items-center gap-2">
                        <i data-lucide="waves" class="w-5 h-5 text-brand-yellow"></i>
                        <?= h(__('footer.beaches_by_activity')) ?>
                    </h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="<?= h(routeUrl('best_beaches', $currentLang)) ?>" class="text-gray-400 hover:text-brand-yellow transition-colors"><?= h(__('footer.best_beaches')) ?></a></li>
                        <li><a href="<?= h(routeUrl('best_beaches_san_juan', $currentLang)) ?>" class="text-gray-400 hover:text-brand-yellow transition-colors"><?= h(__('footer.san_juan_beaches')) ?></a></li>
                        <li><a href="<?= h(routeUrl('best_surfing_beaches', $currentLang)) ?>" class="text-gray-400 hover:text-brand-yellow transition-colors"><?= h(__('footer.surfing_beaches')) ?></a></li>
                        <li><a href="<?= h(routeUrl('best_snorkeling_beaches', $currentLang)) ?>" class="text-gray-400 hover:text-brand-yellow transition-colors"><?= h(__('footer.snorkeling_beaches')) ?></a></li>
                        <li><a href="<?= h(routeUrl('best_family_beaches', $currentLang)) ?>" class="text-gray-400 hover:text-brand-yellow transition-colors"><?= h(__('footer.family_beaches')) ?></a></li>
                        <li><a href="<?= h(routeUrl('hidden_beaches', $currentLang)) ?>" class="text-gray-400 hover:text-brand-yellow transition-colors"><?= h(__('footer.hidden_beaches')) ?></a></li>
                    </ul>
                    <!-- Beaches by Location -->
                    <div class="mt-6">
                        <h5 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3"><?= h(__('footer.by_location')) ?></h5>
                        <ul class="space-y-2 text-sm">
                            <li><a href="<?= h(routeUrl('beaches_near_san_juan', $currentLang)) ?>" class="text-gray-400 hover:text-brand-yellow transition-colors"><?= h(__('footer.near_san_juan')) ?></a></li>
                            <li><a href="<?= h(routeUrl('beaches_near_airport', $currentLang)) ?>" class="text-gray-400 hover:text-brand-yellow transition-colors"><?= h(__('footer.near_airport')) ?></a></li>
                        </ul>
                    </div>
                    <!-- Popular Municipalities -->
                    <div class="mt-6">
                        <h5 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3"><?= h(__('footer.popular_municipalities')) ?></h5>
                        <ul class="grid grid-cols-2 gap-2 text-sm">
                            <?php
                            $topMunicipalities = [
                                'Cabo Rojo', 'Vieques', 'Aguadilla', 'Rincon',
                                'Isabela', 'Arecibo', 'Manati', 'Culebra',
                                'San Juan', 'Guanica', 'Dorado', 'Ponce',
                            ];
                            foreach ($topMunicipalities as $muni):
                                $muniSlug = strtolower(str_replace(' ', '-', stripAccents($muni)));
                            ?>
                            <li><a href="<?= h(routeUrl('municipality', $currentLang, ['municipality' => $muniSlug])) ?>" class="block text-gray-400 hover:text-brand-yellow transition-colors"><?= h($muni) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- Planning Resources -->
                <div>
                    <h4 class="font-bold text-white mb-4 flex items-center gap-2">
                        <i data-lucide="book-open" class="w-5 h-5 text-brand-yellow"></i>
                        <?= h(__('footer.planning_resources')) ?>
                    </h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="<?= h(routeUrl('guides_index', $currentLang)) ?>" class="text-gray-400 hover:text-brand-yellow transition-colors font-medium"><?= h(__('footer.all_guides')) ?> →</a></li>
                        <li><a href="<?= h(routeUrl('guide_transportation', $currentLang)) ?>" class="text-gray-400 hover:text-brand-yellow transition-colors"><?= h(__('footer.transportation')) ?></a></li>
                        <li><a href="<?= h(routeUrl('guide_safety', $currentLang)) ?>" class="text-gray-400 hover:text-brand-yellow transition-colors"><?= h(__('footer.safety_tips')) ?></a></li>
                        <li><a href="<?= h(routeUrl('guide_best_time', $currentLang)) ?>" class="text-gray-400 hover:text-brand-yellow transition-colors"><?= h(__('footer.best_times_to_visit')) ?></a></li>
                        <li><a href="<?= h(routeUrl('guide_packing', $currentLang)) ?>" class="text-gray-400 hover:text-brand-yellow transition-colors"><?= h(__('footer.packing_list')) ?></a></li>
                        <li><a href="<?= h(routeUrl('guide_culebra_vieques', $currentLang)) ?>" class="text-gray-400 hover:text-brand-yellow transition-colors"><?= h(__('footer.culebra_vs_vieques')) ?></a></li>
                        <li><a href="<?= h(routeUrl('guide_bio_bays', $currentLang)) ?>" class="text-gray-400 hover:text-brand-yellow transition-colors"><?= h(__('footer.bio_bays')) ?></a></li>
                        <li><a href="<?= h(routeUrl('guide_snorkeling', $currentLang)) ?>" class="text-gray-400 hover:text-brand-yellow transition-colors"><?= h(__('footer.snorkeling_guide')) ?></a></li>
                        <li><a href="<?= h(routeUrl('guide_surfing', $currentLang)) ?>" class="text-gray-400 hover:text-brand-yellow transition-colors"><?= h(__('footer.surfing_guide')) ?></a></li>
                    </ul>
                </div>

                <!-- More Guides & Account -->
                <div>
                    <h4 class="font-bold text-white mb-4 flex items-center gap-2">
                        <i data-lucide="lightbulb" class="w-5 h-5 text-brand-yellow"></i>
                        <?= h(__('footer.more_guides')) ?>
                    </h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="<?= h(routeUrl('guide_photography', $currentLang)) ?>" class="text-gray-400 hover:text-brand-yellow transition-colors"><?= h(__('footer.photography_tips')) ?></a></li>
                        <li><a href="<?= h(routeUrl('guide_family_planning', $currentLang)) ?>" class="text-gray-400 hover:text-brand-yellow transition-colors"><?= h(__('footer.family_planning')) ?></a></li>
                    </ul>

                    <?php if (isAuthenticated()): ?>
                    <!-- Authenticated User -->
                    <div class="mt-6">
                        <h5 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3"><?= h(__('footer.your_account')) ?></h5>
                        <ul class="space-y-2 text-sm">
                            <li><a href="<?= h(routeUrl('favorites', $currentLang)) ?>" class="text-gray-400 hover:text-brand-yellow transition-colors flex items-center gap-2">
                                <i data-lucide="heart" class="w-4 h-4"></i>
                                <?= h(__('footer.my_favorites')) ?>
                            </a></li>
                            <li><a href="<?= h(routeUrl('profile', $currentLang)) ?>" class="text-gray-400 hover:text-brand-yellow transition-colors flex items-center gap-2">
                                <i data-lucide="user" class="w-4 h-4"></i>
                                <?= h(__('nav.profile')) ?>
                            </a></li>
                        </ul>
                    </div>
                    <?php else: ?>
                    <!-- Guest User -->
                    <div class="mt-6">
                        <a href="<?= h(routeUrl('login', $currentLang)) ?>" class="inline-flex items-center gap-2 bg-brand-yellow hover:bg-yellow-300 text-brand-darker px-4 py-2 rounded-lg font-medium text-sm transition-colors">
                            <i data-lucide="log-in" class="w-4 h-4"></i>
                            <?= h(__('nav.sign_in')) ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Copyright -->
            <div class="border-t border-white/5 pt-8 mt-12">
                <p class="text-xs text-gray-400 text-center">
                    &copy; <?= date('Y') ?> <?= h($_ENV['APP_NAME'] ?? 'Beach Finder') ?>. <?= h(__('footer.copyright')) ?>
                </p>
            </div>
        </div>
    </footer>

    <?php if (!isset($skipMapScripts) || !$skipMapScripts): ?>
    <!-- MapLibre GL JS (defer for non-blocking load) -->
    <script defer
            src="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.js"
            integrity="sha384-3WUbXI7T+/GIrWP/5MDMjhzLyHQ+0utF3PnJ7ozD7UeN1/bbZ96Hk+Vvd024VYfW"
            crossorigin="anonymous" <?= cspNonceAttr() ?>></script>
    <script defer src="/assets/js/context-map-view.js" <?= cspNonceAttr() ?>></script>
    <?php endif; ?>

    <?php if (!isset($skipAppScripts) || !$skipAppScripts): ?>
    <script <?= cspNonceAttr() ?>>
    window.BF_STRINGS = <?= json_encode([
        'error_generic' => __('js.error_generic'),
        'added_favorite' => __('js.added_favorite'),
        'removed_favorite' => __('js.removed_favorite'),
        'filters_cleared' => __('js.filters_cleared'),
        'location_enabled' => __('js.location_enabled'),
        'loading_details' => __('js.loading_details'),
        'beach_singular' => __('js.beach_singular'),
        'beaches_plural' => __('js.beaches_plural'),
        'found' => __('js.found'),
        'found_for' => __('js.found_for'),
        'near_you' => __('js.near_you'),
        'lifeguard' => __('js.lifeguard'),
        'within_km' => __('js.within_km'),
        'clear_all' => __('js.clear_all'),
        'submitting' => __('js.submitting'),
        'submit_review' => __('js.submit_review'),
        'review_success' => __('js.review_success'),
        'review_error' => __('js.review_error'),
        'review_network_error' => __('js.review_network_error'),
        'compare_max' => __('js.compare_max'),
        'add_comparison' => __('js.add_comparison'),
        'remove_comparison' => __('js.remove_comparison'),
        'share_check_out' => __('js.share_check_out'),
        'share_copied' => __('js.share_copied'),
        'share_copy_failed' => __('js.share_copy_failed'),
        'share_title' => __('js.share_title'),
        'share_copy_link' => __('js.share_copy_link'),
        'share_facebook' => __('js.share_facebook'),
        'share_twitter' => __('js.share_twitter'),
        'share_whatsapp' => __('js.share_whatsapp'),
        'share_email' => __('js.share_email'),
        'geo_getting' => __('js.geo_getting'),
        'geo_getting_short' => __('js.geo_getting_short'),
        'geo_denied' => __('js.geo_denied'),
        'geo_denied_short' => __('js.geo_denied_short'),
        'geo_unavailable' => __('js.geo_unavailable'),
        'geo_timeout' => __('js.geo_timeout'),
        'geo_error' => __('js.geo_error'),
        'geo_enabled' => __('js.geo_enabled'),
        'geo_use_location' => __('js.geo_use_location'),
        'geo_near_me' => __('js.geo_near_me'),
        'share_copied_short' => __('js.share_copied_short'),
        'map_loading' => __('js.map_loading'),
        'map_error' => __('js.map_error'),
        'map_refresh' => __('js.map_refresh'),
        'map_refresh_btn' => __('js.map_refresh_btn'),
        'map_legend' => __('js.map_legend'),
        'map_your_location' => __('js.map_your_location'),
        'map_beaches_shown' => __('js.map_beaches_shown'),
        'map_view_details' => __('js.map_view_details'),
        'map_directions' => __('js.map_directions'),
        'sw_update' => __('js.sw_update'),
        'email_subscribed' => __('js.email_subscribed'),
        'email_error' => __('js.email_error'),
        'close_notification' => __('js.close_notification'),
        'clear_search' => __('js.clear_search'),
        'share_label' => __('js.share_label'),
        'share_brand' => __('js.share_brand'),
        'map_away' => __('js.map_away'),
        'fav_remove_aria' => __('js.fav_remove_aria'),
        'fav_add_aria' => __('js.fav_add_aria'),
    ], JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script <?= cspNonceAttr() ?>>
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
    <script defer src="/assets/js/app.min.js" <?= cspNonceAttr() ?>></script>
    <script defer src="/assets/js/geolocation.js" <?= cspNonceAttr() ?>></script>
    <script defer src="/assets/js/filters.js" <?= cspNonceAttr() ?>></script>
    <script defer src="/assets/js/analytics.js" <?= cspNonceAttr() ?>></script>
    <script defer src="/assets/js/plunk-client.js" <?= cspNonceAttr() ?>></script>
    <script defer src="/assets/js/share.js" <?= cspNonceAttr() ?>></script>
    <?php endif; ?>

    <!-- Initialize Lucide Icons -->
    <script <?= cspNonceAttr() ?>>
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
    <script <?= cspNonceAttr() ?>>
    // Register service worker
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', async () => {
            try {
                const registration = await navigator.serviceWorker.register('/sw.js');

                // Check for updates
                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing;
                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            // New version available
                            if (confirm((window.BF_STRINGS || {}).sw_update || 'A new version is available! Reload to update?')) {
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
        deferredPrompt.userChoice.then(() => {
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
                <h3 class="font-semibold text-white"><?= h(__('pwa.install_title')) ?></h3>
                <p class="text-sm text-gray-400 mt-1"><?= h(__('pwa.install_message')) ?></p>
                <div class="flex gap-2 mt-3">
                    <button type="button" data-action="installPWA" class="bg-brand-yellow hover:bg-yellow-300 text-brand-darker px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        <?= h(__('pwa.install')) ?>
                    </button>
                    <button type="button" data-action="dismissInstall" class="text-gray-400 hover:text-white px-4 py-2 text-sm font-medium transition-colors">
                        <?= h(__('pwa.not_now')) ?>
                    </button>
                </div>
            </div>
            <button type="button" data-action="dismissInstall" class="text-gray-500 hover:text-white transition-colors" aria-label="<?= h(__('common.close')) ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- Sign-Up Prompt Modal -->
    <div id="signup-prompt-modal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 hidden items-center justify-center p-4"
         role="dialog" aria-modal="true" aria-labelledby="signup-prompt-title" data-action="closeSignupPrompt">
        <div class="bg-brand-dark rounded-xl shadow-glass max-w-md w-full border border-white/10 overflow-hidden" data-action-stop data-action="noop" data-on="click">
            <!-- Header with icon -->
            <div class="bg-gradient-to-r from-brand-yellow/20 to-brand-yellow/5 px-6 py-5 border-b border-white/10">
                <div class="flex items-center gap-3">
                    <div id="signup-prompt-icon" class="w-12 h-12 rounded-full bg-brand-yellow/20 flex items-center justify-center">
                        <i data-lucide="heart" class="w-6 h-6 text-brand-yellow"></i>
                    </div>
                    <div>
                        <h2 id="signup-prompt-title" class="text-lg font-semibold text-white"><?= h(__('footer.signup_title')) ?></h2>
                        <p id="signup-prompt-subtitle" class="text-sm text-gray-400"><?= h(__('footer.signup_subtitle')) ?></p>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="p-6">
                <p id="signup-prompt-description" class="text-gray-300 text-sm mb-5">
                    <?= h(__('footer.signup_description')) ?>
                </p>

                <!-- Benefits list -->
                <ul class="space-y-2 mb-6">
                    <li class="flex items-center gap-2 text-sm text-gray-300">
                        <i data-lucide="heart" class="w-4 h-4 text-brand-yellow"></i>
                        <span><?= h(__('footer.signup_benefit_1')) ?></span>
                    </li>
                    <li class="flex items-center gap-2 text-sm text-gray-300">
                        <i data-lucide="star" class="w-4 h-4 text-brand-yellow"></i>
                        <span><?= h(__('footer.signup_benefit_2')) ?></span>
                    </li>
                    <li class="flex items-center gap-2 text-sm text-gray-300">
                        <i data-lucide="map-pin" class="w-4 h-4 text-brand-yellow"></i>
                        <span><?= h(__('footer.signup_benefit_3')) ?></span>
                    </li>
                </ul>

                <!-- Actions -->
                <div class="flex flex-col gap-3">
                    <a href="<?= h(routeUrl('login', $currentLang)) ?>" id="signup-prompt-cta" class="flex items-center justify-center gap-2 bg-brand-yellow hover:bg-yellow-300 text-brand-darker py-3 rounded-lg font-semibold transition-colors">
                        <i data-lucide="log-in" class="w-5 h-5"></i>
                        <?= h(__('footer.signup_cta')) ?>
                    </a>
                    <button type="button" data-action="closeSignupPrompt" class="text-gray-400 hover:text-white py-2 text-sm font-medium transition-colors">
                        <?= h(__('footer.signup_dismiss')) ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Sign-Up Prompt JavaScript -->
    <script <?= cspNonceAttr() ?>>
    function showSignupPrompt(context = 'favorites', redirectUrl = null) {
        const modal = document.getElementById('signup-prompt-modal');
        const title = document.getElementById('signup-prompt-title');
        const subtitle = document.getElementById('signup-prompt-subtitle');
        const description = document.getElementById('signup-prompt-description');
        const icon = document.getElementById('signup-prompt-icon');
        const cta = document.getElementById('signup-prompt-cta');

        // Customize content based on context
        const contexts = <?= json_encode([
            'favorites' => [
                'title' => __('footer.signup_ctx_favorites_title'),
                'subtitle' => __('footer.signup_ctx_favorites_subtitle'),
                'description' => __('footer.signup_ctx_favorites_desc'),
                'icon' => 'heart',
            ],
            'reviews' => [
                'title' => __('footer.signup_ctx_reviews_title'),
                'subtitle' => __('footer.signup_ctx_reviews_subtitle'),
                'description' => __('footer.signup_ctx_reviews_desc'),
                'icon' => 'message-circle',
            ],
            'photos' => [
                'title' => __('footer.signup_ctx_photos_title'),
                'subtitle' => __('footer.signup_ctx_photos_subtitle'),
                'description' => __('footer.signup_ctx_photos_desc'),
                'icon' => 'camera',
            ],
        ], JSON_UNESCAPED_UNICODE) ?>;

        const config = contexts[context] || contexts.favorites;

        title.textContent = config.title;
        subtitle.textContent = config.subtitle;
        description.textContent = config.description;
        icon.innerHTML = `<i data-lucide="${config.icon}" class="w-6 h-6 text-brand-yellow"></i>`;

        // Set redirect URL
        const loginBasePath = <?= json_encode(routeUrl('login', $currentLang)) ?>;
        const loginUrl = redirectUrl ? `${loginBasePath}?redirect=${encodeURIComponent(redirectUrl)}` : loginBasePath;
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
            <button type="button" data-action="dismissWelcomePopup" class="welcome-popup-close" aria-label="<?= __('aria.close') ?>">
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
                    <span>🏝️</span> <?= h(__('footer.welcome_title')) ?>
                </h2>
                <p class="welcome-popup-subtitle">
                    <?= h(__('footer.welcome_subtitle', ['count' => number_format(getSiteStats()['total_users'] ?: 500)])) ?>
                </p>

                <!-- Benefits -->
                <ul class="welcome-popup-benefits">
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                        </svg>
                        <span><?= h(__('footer.welcome_benefit_1')) ?></span>
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
                        </svg>
                        <span><?= h(__('footer.welcome_benefit_2')) ?></span>
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                        </svg>
                        <span><?= h(__('footer.welcome_benefit_3')) ?></span>
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
                        <?= h(__('footer.welcome_google')) ?>
                    </a>
                    <?php endif; ?>

                    <a href="<?= h(routeUrl('login', $currentLang)) ?>?method=email" class="welcome-popup-btn-email">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                        </svg>
                        <?= h(__('footer.welcome_email')) ?>
                    </a>

                    <div class="welcome-popup-divider">
                        <span><?= h(__('footer.welcome_or')) ?></span>
                    </div>

                    <button type="button" data-action="dismissWelcomePopup" class="welcome-popup-btn-dismiss">
                        <?= h(__('footer.welcome_dismiss')) ?>
                    </button>
                </div>

                <!-- Trust signal -->
                <div class="welcome-popup-trust">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <span><?= h(__('footer.welcome_trust')) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Welcome Popup JavaScript -->
    <script <?= cspNonceAttr() ?>>
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

            if (refPath === '/login' || refPath === '/login.php' || refPath === '/es/iniciar-sesion') {
                return false;
            }

            if (refPath === '/verify' || refPath === '/verify.php' || refPath === '/es/verificar') {
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
            if (window.location.pathname !== '/' &&
                window.location.pathname !== '/index.php' &&
                window.location.pathname !== '/es' &&
                window.location.pathname !== '/es/') {
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
         role="dialog" aria-modal="true" aria-labelledby="review-modal-title" data-action="closeReviewForm">
        <div class="bg-brand-dark rounded-xl shadow-glass max-w-lg w-full max-h-[90vh] overflow-y-auto border border-white/10" data-action-stop data-action="noop" data-on="click">
            <div class="sticky top-0 bg-brand-dark border-b border-white/10 px-6 py-4 flex items-center justify-between">
                <h2 id="review-modal-title" class="text-xl font-semibold text-white"><?= h(__('footer.review_title')) ?></h2>
                <button data-action="closeReviewForm" class="text-gray-400 hover:text-white p-1 transition-colors" aria-label="<?= __('aria.close_review') ?>">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>

            <form id="review-form" class="p-6 space-y-5" data-action="submitReview" data-action-args='["__event__"]' data-on="submit">
                <input type="hidden" name="beach_id" id="review-beach-id">
                <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">

                <!-- Beach Name (display only) -->
                <div>
                    <div class="text-sm text-gray-500 mb-1"><?= h(__('footer.review_reviewing')) ?></div>
                    <div id="review-beach-name" class="font-semibold text-white"></div>
                </div>

                <!-- Star Rating -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2"><?= h(__('footer.review_your_rating')) ?> <span class="text-red-400">*</span></label>
                    <div class="flex gap-1" id="star-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <button type="button" class="star-btn text-3xl text-gray-600 hover:text-yellow-400 transition-colors" data-rating="<?= $i ?>" data-action="setRating" data-action-args='[<?= $i ?>]'>★</button>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="rating" id="review-rating" value="0" required>
                    <p class="text-red-400 text-sm mt-1 hidden" id="rating-error"><?= h(__('footer.review_rating_required')) ?></p>
                </div>

                <!-- Title -->
                <div>
                    <label for="review-title" class="block text-sm font-medium text-gray-300 mb-1"><?= h(__('footer.review_title_label')) ?></label>
                    <input type="text" name="title" id="review-title" maxlength="100" placeholder="<?= h(__('footer.review_title_placeholder')) ?>"
                           class="w-full px-3 py-2 bg-white/5 border border-white/20 rounded-lg text-white placeholder-gray-500 focus:ring-2 focus:ring-brand-yellow/50 focus:border-brand-yellow/50">
                </div>

                <!-- Review Text -->
                <div>
                    <label for="review-text" class="block text-sm font-medium text-gray-300 mb-1"><?= h(__('footer.review_text_label')) ?></label>
                    <textarea name="review_text" id="review-text" rows="4" maxlength="2000" placeholder="<?= h(__('footer.review_text_placeholder')) ?>"
                              class="w-full px-3 py-2 bg-white/5 border border-white/20 rounded-lg text-white placeholder-gray-500 focus:ring-2 focus:ring-brand-yellow/50 focus:border-brand-yellow/50 resize-none"></textarea>
                    <div class="text-xs text-gray-500 text-right mt-1"><span id="char-count">0</span>/2000</div>
                </div>

                <!-- Visit Details -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="review-visit-date" class="block text-sm font-medium text-gray-300 mb-1"><?= h(__('footer.review_visit_date')) ?></label>
                        <input type="month" name="visit_date" id="review-visit-date"
                               class="w-full px-3 py-2 bg-white/5 border border-white/20 rounded-lg text-white focus:ring-2 focus:ring-brand-yellow/50 focus:border-brand-yellow/50">
                    </div>
                    <div>
                        <label for="review-visit-type" class="block text-sm font-medium text-gray-300 mb-1"><?= h(__('footer.review_trip_type')) ?></label>
                        <select name="visit_type" id="review-visit-type"
                                class="w-full px-3 py-2 bg-white/5 border border-white/20 rounded-lg text-white focus:ring-2 focus:ring-brand-yellow/50 focus:border-brand-yellow/50">
                            <option value="" class="bg-brand-dark"><?= h(__('footer.review_select')) ?></option>
                            <option value="solo" class="bg-brand-dark"><?= h(__('footer.review_solo')) ?></option>
                            <option value="couple" class="bg-brand-dark"><?= h(__('footer.review_couple')) ?></option>
                            <option value="family" class="bg-brand-dark"><?= h(__('footer.review_family')) ?></option>
                            <option value="friends" class="bg-brand-dark"><?= h(__('footer.review_friends')) ?></option>
                            <option value="group" class="bg-brand-dark"><?= h(__('footer.review_group')) ?></option>
                        </select>
                    </div>
                </div>

                <!-- Would Recommend -->
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="would_recommend" id="review-recommend" checked
                           class="w-4 h-4 text-brand-yellow bg-white/5 border-white/20 rounded focus:ring-brand-yellow/50">
                    <label for="review-recommend" class="text-sm text-gray-300"><?= h(__('footer.review_recommend')) ?></label>
                </div>

                <!-- Submit -->
                <div class="flex gap-3 pt-2">
                    <button type="submit" id="review-submit-btn"
                            class="flex-1 bg-brand-yellow hover:bg-yellow-300 text-brand-darker py-3 rounded-lg font-medium transition-colors">
                        <?= h(__('footer.review_submit')) ?>
                    </button>
                    <button type="button" data-action="closeReviewForm"
                            class="px-6 py-3 border border-white/20 text-gray-300 rounded-lg font-medium hover:bg-white/5 hover:text-white transition-colors">
                        <?= h(__('common.cancel')) ?>
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
    <script <?= cspNonceAttr() ?>>
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
        const _S = window.BF_STRINGS || {};
        submitBtn.textContent = _S.submitting || 'Submitting...';
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
                successDiv.textContent = data.message || _S.review_success || 'Review submitted successfully!';
                successDiv.classList.remove('hidden');

                // Close modal after delay and reload
                setTimeout(() => {
                    closeReviewForm();
                    window.location.reload();
                }, 1500);
            } else {
                errorDiv.textContent = data.error || _S.review_error || 'Failed to submit review';
                errorDiv.classList.remove('hidden');
                submitBtn.disabled = false;
                submitBtn.textContent = _S.submit_review || 'Submit Review';
            }
        } catch (error) {
            console.error('Review submission error:', error);
            errorDiv.textContent = _S.review_network_error || 'Network error. Please try again.';
            errorDiv.classList.remove('hidden');
            submitBtn.disabled = false;
            submitBtn.textContent = _S.submit_review || 'Submit Review';
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
    <div id="compare-bar" class="fixed bottom-0 left-0 right-0 bg-brand-dark/95 backdrop-blur-md border-t border-white/10 shadow-glass transform translate-y-full transition-transform duration-300 z-40" role="region" aria-label="<?= __('aria.comparison') ?>">
        <div class="max-w-7xl mx-auto px-4 py-3">
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <i data-lucide="git-compare" class="w-5 h-5 text-brand-yellow" aria-hidden="true"></i>
                    <span class="font-medium text-white"><?= h(__('footer.compare_label')) ?></span>
                    <span id="compare-count" class="bg-brand-yellow text-brand-darker text-xs font-bold px-2 py-0.5 rounded-full">0</span>
                </div>

                <div id="compare-beaches" class="flex-1 flex gap-2 overflow-x-auto" role="list">
                    <!-- Beach thumbnails added here by JS -->
                </div>

                <div class="flex gap-2 flex-shrink-0">
                    <button type="button"
                            data-action="goToCompare"
                            id="compare-go-btn"
                            disabled
                            class="bg-brand-yellow hover:bg-yellow-300 disabled:bg-gray-600 disabled:cursor-not-allowed text-brand-darker px-4 py-2 rounded-lg font-medium transition-colors text-sm">
                        <?= h(__('footer.compare_now')) ?>
                    </button>
                    <button type="button"
                            data-action="clearCompareSelection"
                            class="text-gray-400 hover:text-white p-2 transition-colors"
                            aria-label="<?= __('aria.clear_comparison') ?>">
                        <i data-lucide="x" class="w-5 h-5" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Comparison JavaScript -->
    <script <?= cspNonceAttr() ?>>
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
                const _S = window.BF_STRINGS || {};
                showToast((_S.compare_max || 'Maximum :count beaches can be compared').replace(':count', MAX_COMPARE), 'warning', 3000);
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
                <button data-action="removeFromCompareBar" data-action-args='["${beach.id}"]' class="text-gray-400 hover:text-red-400 p-0.5 transition-colors" aria-label="Remove ${escapeHtmlCompare(beach.name)} from comparison">
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
