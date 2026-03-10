<?php
require_once APP_ROOT . '/inc/place_types.php';
require_once APP_ROOT . '/inc/place_helpers.php';
$appName = $appName ?? ($_ENV['APP_NAME'] ?? 'Beach Finder');
$currentLang = $currentLang ?? getCurrentLanguage();
$user = $user ?? currentUser();
$localizedHome = routeUrl('home', $currentLang);
$localizedQuiz = routeUrl('quiz', $currentLang);
$localizedProfile = routeUrl('profile', $currentLang);
$localizedLogout = routeUrl('logout', $currentLang);
$localizedLogin = routeUrl('login', $currentLang);
$langSwitchEnUrl = getLocalizedUrlForCurrentRequest('en');
$langSwitchEsUrl = getLocalizedUrlForCurrentRequest('es');

$homeFilterHref = static function (string $tag) use ($localizedHome): string {
    return $localizedHome . '?' . http_build_query(['tags' => [$tag]]) . '#beaches';
};
$homeAnchorHref = static function (string $anchor) use ($localizedHome): string {
    return $localizedHome . '#' . ltrim($anchor, '#');
};

$navMapHref = $navMapHref ?? null;
if (!is_string($navMapHref) || $navMapHref === '') {
    $requestUri = (string)($_SERVER['REQUEST_URI'] ?? '/');
    $currentPath = (string)(parse_url($requestUri, PHP_URL_PATH) ?? '/');
    if ($currentPath === '') {
        $currentPath = $localizedHome;
    }
    $queryParams = $_GET;
    $queryParams['view'] = 'map';
    $queryString = http_build_query($queryParams);
    $navMapHref = $currentPath . ($queryString !== '' ? '?' . $queryString : '?view=map');
}
?>

<!-- Skip Links for Accessibility -->
<a href="#main-content" class="skip-link sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:bg-cyan-500 focus:text-white focus:px-4 focus:py-2 focus:rounded-lg focus:shadow-lg focus:outline-none">
    <?= h(__('nav.skip_main')) ?>
</a>
<a href="#beach-grid" class="skip-link sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-48 focus:z-50 focus:bg-cyan-500 focus:text-white focus:px-4 focus:py-2 focus:rounded-lg focus:shadow-lg focus:outline-none">
    <?= h(__('nav.skip_beaches')) ?>
</a>

<!-- Navigation -->
<nav id="main-nav" class="fixed top-0 w-full z-50 px-4 sm:px-6 py-4 transition-all duration-300" role="navigation" aria-label="<?= h(__('nav.main_navigation')) ?>">
    <div class="max-w-7xl mx-auto flex items-center justify-between">
        <!-- Logo with rotating sun -->
        <a href="<?= h($localizedHome) ?>" class="flex items-center gap-2 focus:outline-none focus:ring-2 focus:ring-brand-yellow focus:ring-offset-2 focus:ring-offset-brand-darker rounded-lg p-1" aria-label="<?= h($appName) ?> - <?= h(__('nav.home')) ?>">
            <i data-lucide="sun" class="w-7 h-7 text-brand-yellow hover-spin transition-all" aria-hidden="true"></i>
            <span class="text-xl font-bold text-white"><?= h($appName) ?></span>
        </a>

        <!-- Center Navigation (Desktop) -->
        <div class="hidden md:flex items-center px-4 py-2" role="menubar">
            <!-- Beaches Dropdown -->
            <div class="relative" id="beaches-dropdown">
                <button type="button"
                        data-action="toggleBeachesDropdown"
                        class="flex items-center gap-1 text-sm text-white/80 hover:text-brand-yellow px-4 py-1 transition-colors"
                        role="menuitem"
                        aria-expanded="false"
                        aria-haspopup="true">
                    <span><?= h(__('nav.beaches')) ?></span>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
                </button>
                <div id="beaches-dropdown-menu" class="hidden absolute left-0 top-full mt-3 w-56 bg-brand-dark/95 backdrop-blur-md rounded-xl shadow-glass border border-white/10 py-2 z-50">
                    <div class="px-3 py-2 text-xs text-white/40 uppercase tracking-wider"><?= h(__('nav.find_by_activity')) ?></div>
                    <a href="<?= h($homeFilterHref('surfing')) ?>" class="flex items-center gap-3 px-4 py-2.5 text-sm text-white/80 hover:text-brand-yellow hover:bg-white/5 transition-colors">
                        <span class="text-lg">🏄‍♂️</span>
                        <span><?= h(__('tags.surfing')) ?></span>
                    </a>
                    <a href="<?= h($homeFilterHref('snorkeling')) ?>" class="flex items-center gap-3 px-4 py-2.5 text-sm text-white/80 hover:text-brand-yellow hover:bg-white/5 transition-colors">
                        <span class="text-lg">🤿</span>
                        <span><?= h(__('tags.snorkeling')) ?></span>
                    </a>
                    <a href="<?= h($homeFilterHref('family-friendly')) ?>" class="flex items-center gap-3 px-4 py-2.5 text-sm text-white/80 hover:text-brand-yellow hover:bg-white/5 transition-colors">
                        <span class="text-lg">👨‍👩‍👧</span>
                        <span><?= h(__('tags.family-friendly')) ?></span>
                    </a>
                    <a href="<?= h($homeFilterHref('secluded')) ?>" class="flex items-center gap-3 px-4 py-2.5 text-sm text-white/80 hover:text-brand-yellow hover:bg-white/5 transition-colors">
                        <span class="text-lg">🌴</span>
                        <span><?= h(__('tags.secluded')) ?></span>
                    </a>
                    <a href="<?= h($homeFilterHref('swimming')) ?>" class="flex items-center gap-3 px-4 py-2.5 text-sm text-white/80 hover:text-brand-yellow hover:bg-white/5 transition-colors">
                        <span class="text-lg">🏊</span>
                        <span><?= h(__('tags.swimming')) ?></span>
                    </a>
                    <div class="border-t border-white/10 mt-2 pt-2">
                        <a href="<?= h($homeAnchorHref('beaches')) ?>" class="flex items-center gap-3 px-4 py-2.5 text-sm text-brand-yellow hover:bg-white/5 transition-colors">
                            <i data-lucide="compass" class="w-4 h-4"></i>
                            <span><?= h(__('nav.view_all_beaches')) ?></span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Explore Dropdown -->
            <div class="relative" id="explore-dropdown">
                <button type="button"
                        data-action="toggleExploreDropdown"
                        class="flex items-center gap-1 text-sm text-white/80 hover:text-brand-yellow px-4 py-1 transition-colors"
                        role="menuitem"
                        aria-expanded="false"
                        aria-haspopup="true">
                    <span><?= h(__('nav.explore', [], 'Explore')) ?></span>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
                </button>
                <div id="explore-dropdown-menu" class="hidden absolute left-0 top-full mt-3 w-56 bg-brand-dark/95 backdrop-blur-md rounded-xl shadow-glass border border-white/10 py-2 z-50">
                    <a href="<?= h(routeUrl('best_rivers', $currentLang)) ?>" class="flex items-center gap-3 px-4 py-2.5 text-sm text-white/80 hover:text-brand-yellow hover:bg-white/5 transition-colors">
                        <span class="text-lg"><?= PLACE_TYPES['river']['emoji'] ?></span>
                        <span><?= h(getPlaceLabelPlural('river', $currentLang)) ?></span>
                    </a>
                    <a href="<?= h(routeUrl('best_waterfalls', $currentLang)) ?>" class="flex items-center gap-3 px-4 py-2.5 text-sm text-white/80 hover:text-brand-yellow hover:bg-white/5 transition-colors">
                        <span class="text-lg"><?= PLACE_TYPES['waterfall']['emoji'] ?></span>
                        <span><?= h(getPlaceLabelPlural('waterfall', $currentLang)) ?></span>
                    </a>
                    <a href="<?= h(routeUrl('best_trails', $currentLang)) ?>" class="flex items-center gap-3 px-4 py-2.5 text-sm text-white/80 hover:text-brand-yellow hover:bg-white/5 transition-colors">
                        <span class="text-lg"><?= PLACE_TYPES['trail']['emoji'] ?></span>
                        <span><?= h(getPlaceLabelPlural('trail', $currentLang)) ?></span>
                    </a>
                    <a href="<?= h(routeUrl('best_restaurants', $currentLang)) ?>" class="flex items-center gap-3 px-4 py-2.5 text-sm text-white/80 hover:text-brand-yellow hover:bg-white/5 transition-colors">
                        <span class="text-lg"><?= PLACE_TYPES['restaurant']['emoji'] ?></span>
                        <span><?= h(getPlaceLabelPlural('restaurant', $currentLang)) ?></span>
                    </a>
                    <a href="<?= h(routeUrl('best_photo_spots', $currentLang)) ?>" class="flex items-center gap-3 px-4 py-2.5 text-sm text-white/80 hover:text-brand-yellow hover:bg-white/5 transition-colors">
                        <span class="text-lg"><?= PLACE_TYPES['photo_spot']['emoji'] ?></span>
                        <span><?= h(getPlaceLabelPlural('photo_spot', $currentLang)) ?></span>
                    </a>
                    <div class="border-t border-white/10 mt-2 pt-2">
                        <a href="<?= h(routeUrl('explore', $currentLang)) ?>" class="flex items-center gap-3 px-4 py-2.5 text-sm text-brand-yellow hover:bg-white/5 transition-colors">
                            <i data-lucide="compass" class="w-4 h-4"></i>
                            <span><?= h(__('nav.explore_all', [], 'Explore All')) ?></span>
                        </a>
                    </div>
                </div>
            </div>

            <a href="<?= h($localizedQuiz) ?>" class="text-sm text-white/80 hover:text-brand-yellow px-4 py-1 transition-colors" role="menuitem"><?= h(__('nav.quiz')) ?></a>
            <a href="<?= h($navMapHref) ?>"
               class="text-sm text-white/80 hover:text-brand-yellow px-4 py-1 transition-colors"
               role="menuitem"
               data-context-map-link><?= h(__('nav.map')) ?></a>
        </div>

        <!-- Right Side - Auth & Language -->
        <div class="hidden md:flex items-center gap-4">
            <!-- Language Switcher -->
            <div class="relative" id="lang-dropdown">
                <button type="button"
                        data-action="toggleLangDropdown"
                        class="flex items-center gap-1 px-2 py-1.5 text-sm text-white/70 hover:text-white rounded-lg transition-colors"
                        aria-label="<?= h(__('nav.language')) ?>"
                        aria-expanded="false"
                        aria-haspopup="true">
                    <span><?= getLanguageFlag($currentLang) ?></span>
                    <span class="hidden sm:inline"><?= strtoupper($currentLang) ?></span>
                    <i data-lucide="chevron-down" class="w-3 h-3"></i>
                </button>
                <div id="lang-dropdown-menu" class="hidden absolute right-0 mt-1 w-32 bg-brand-dark/95 backdrop-blur-md rounded-lg shadow-glass border border-white/10 py-1 z-50">
                    <button type="button" data-target-url="<?= h($langSwitchEnUrl) ?>" data-action="setLanguage" data-action-args='["en","__this__"]' class="flex items-center gap-2 w-full px-3 py-2 text-sm text-left hover:bg-white/10 <?= $currentLang === 'en' ? 'text-brand-yellow' : 'text-white/80' ?>">
                        <span>🇺🇸</span> <?= h(__('nav.language_english')) ?>
                    </button>
                    <button type="button" data-target-url="<?= h($langSwitchEsUrl) ?>" data-action="setLanguage" data-action-args='["es","__this__"]' class="flex items-center gap-2 w-full px-3 py-2 text-sm text-left hover:bg-white/10 <?= $currentLang === 'es' ? 'text-brand-yellow' : 'text-white/80' ?>">
                        <span>🇵🇷</span> <?= h(__('nav.language_spanish')) ?>
                    </button>
                </div>
            </div>

            <?php if ($user): ?>
                <div class="flex items-center gap-3">
                    <a href="<?= h($localizedProfile) ?>?tab=favorites" class="text-white/70 hover:text-brand-yellow transition-colors">
                        <i data-lucide="heart" class="w-5 h-5"></i>
                    </a>
                    <a href="<?= h($localizedProfile) ?>" class="flex items-center gap-2 hover:opacity-80 transition-opacity">
                        <?php if (!empty($user['avatar_url'])): ?>
                        <img src="<?= h($user['avatar_url']) ?>" alt="" class="w-8 h-8 rounded-full border border-white/20">
                        <?php else: ?>
                        <div class="w-8 h-8 rounded-full bg-brand-yellow/20 flex items-center justify-center text-brand-yellow font-medium text-sm border border-brand-yellow/30">
                            <?= strtoupper(substr($user['name'] ?? $user['email'], 0, 1)) ?>
                        </div>
                        <?php endif; ?>
                    </a>
                    <a href="<?= h($localizedLogout) ?>" class="text-sm text-white/60 hover:text-white transition-colors"><?= h(__('nav.logout')) ?></a>
                </div>
            <?php else: ?>
                <a href="<?= h($localizedLogin) ?>" class="border border-brand-yellow text-brand-yellow hover:bg-brand-yellow hover:text-brand-darker px-5 py-2 rounded-full text-sm font-semibold transition-colors">
                    <?= h(__('nav.sign_in')) ?>
                </a>
            <?php endif; ?>
        </div>

        <!-- Mobile menu button -->
        <div class="flex items-center gap-2 md:hidden">
            <button type="button"
                    id="mobile-menu-button"
                    data-action="toggleMobileMenu"
                    class="p-2 rounded-lg text-white/80 hover:text-white focus:outline-none focus:ring-2 focus:ring-brand-yellow"
                    aria-expanded="false"
                    aria-controls="mobile-menu"
                    aria-label="<?= h(__('nav.open_main_menu')) ?>">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Mobile menu -->
    <div id="mobile-menu" class="hidden md:hidden mt-4 bg-brand-dark/95 backdrop-blur-md rounded-2xl border border-white/10 overflow-hidden" role="menu" aria-labelledby="mobile-menu-button">
        <div class="px-4 py-4 space-y-1">
            <!-- Beaches Section -->
            <div class="text-xs text-white/40 uppercase tracking-wider px-3 pt-2 pb-1"><?= h(__('nav.find_beaches')) ?></div>
            <a href="<?= h($homeFilterHref('surfing')) ?>" class="flex items-center gap-3 text-white/80 hover:text-brand-yellow py-2.5 px-3 rounded-lg hover:bg-white/5 transition-colors" role="menuitem">
                <span class="text-lg">🏄‍♂️</span>
                <span><?= h(__('tags.surfing')) ?></span>
            </a>
            <a href="<?= h($homeFilterHref('snorkeling')) ?>" class="flex items-center gap-3 text-white/80 hover:text-brand-yellow py-2.5 px-3 rounded-lg hover:bg-white/5 transition-colors" role="menuitem">
                <span class="text-lg">🤿</span>
                <span><?= h(__('tags.snorkeling')) ?></span>
            </a>
            <a href="<?= h($homeFilterHref('family-friendly')) ?>" class="flex items-center gap-3 text-white/80 hover:text-brand-yellow py-2.5 px-3 rounded-lg hover:bg-white/5 transition-colors" role="menuitem">
                <span class="text-lg">👨‍👩‍👧</span>
                <span><?= h(__('tags.family-friendly')) ?></span>
            </a>
            <a href="<?= h($homeFilterHref('secluded')) ?>" class="flex items-center gap-3 text-white/80 hover:text-brand-yellow py-2.5 px-3 rounded-lg hover:bg-white/5 transition-colors" role="menuitem">
                <span class="text-lg">🌴</span>
                <span><?= h(__('tags.secluded')) ?></span>
            </a>
            <a href="<?= h($homeAnchorHref('beaches')) ?>" class="flex items-center gap-3 text-brand-yellow py-2.5 px-3 rounded-lg hover:bg-white/5 transition-colors" role="menuitem">
                <i data-lucide="compass" class="w-5 h-5" aria-hidden="true"></i>
                <span><?= h(__('nav.view_all_beaches')) ?></span>
            </a>

            <!-- Tools Section -->
            <div class="border-t border-white/10 mt-3 pt-3">
                <div class="text-xs text-white/40 uppercase tracking-wider px-3 pt-1 pb-1"><?= h(__('nav.tools')) ?></div>
                <a href="<?= h($localizedQuiz) ?>" class="flex items-center gap-3 text-white/80 hover:text-brand-yellow py-2.5 px-3 rounded-lg hover:bg-white/5 transition-colors" role="menuitem">
                    <i data-lucide="sparkles" class="w-5 h-5" aria-hidden="true"></i>
                    <span><?= h(__('nav.find_my_beach_quiz')) ?></span>
                </a>
                <a href="<?= h($navMapHref) ?>"
                   class="flex items-center gap-3 text-white/80 hover:text-brand-yellow py-2.5 px-3 rounded-lg hover:bg-white/5 transition-colors"
                   role="menuitem"
                   data-context-map-link>
                    <i data-lucide="map" class="w-5 h-5" aria-hidden="true"></i>
                    <span><?= h(__('nav.map_view')) ?></span>
                </a>
            </div>
            <?php if ($user): ?>
                <a href="<?= h($localizedProfile) ?>" class="flex items-center gap-3 text-white/80 hover:text-brand-yellow py-3 px-3 rounded-lg hover:bg-white/5 transition-colors" role="menuitem">
                    <i data-lucide="user" class="w-5 h-5"></i>
                    <span><?= h(__('nav.my_profile')) ?></span>
                </a>
                <a href="<?= h($localizedProfile) ?>?tab=favorites" class="flex items-center gap-3 text-white/80 hover:text-brand-yellow py-3 px-3 rounded-lg hover:bg-white/5 transition-colors" role="menuitem">
                    <i data-lucide="heart" class="w-5 h-5 text-red-400 fill-red-400"></i>
                    <span><?= h(__('nav.favorites')) ?></span>
                </a>
                <div class="pt-3 mt-3 border-t border-white/10">
                    <div class="flex items-center gap-3 py-2 px-3">
                        <?php if (!empty($user['avatar_url'])): ?>
                        <img src="<?= h($user['avatar_url']) ?>" alt="" class="w-8 h-8 rounded-full border border-white/20">
                        <?php else: ?>
                        <div class="w-8 h-8 rounded-full bg-brand-yellow/20 flex items-center justify-center text-brand-yellow font-medium text-sm">
                            <?= strtoupper(substr($user['name'] ?? $user['email'], 0, 1)) ?>
                        </div>
                        <?php endif; ?>
                        <span class="text-sm text-white/70"><?= h($user['name'] ?? __('nav.user_fallback')) ?></span>
                    </div>
                    <a href="<?= h($localizedLogout) ?>" class="block text-red-400 hover:text-red-300 py-2 px-3"><?= h(__('nav.logout')) ?></a>
                </div>
            <?php else: ?>
                <a href="<?= h($localizedLogin) ?>" class="block bg-brand-yellow text-brand-darker text-center py-3 rounded-lg mt-3 font-semibold"><?= h(__('nav.sign_in')) ?></a>
            <?php endif; ?>

            <!-- Mobile Language Switcher -->
            <div class="pt-3 mt-3 border-t border-white/10">
                <label class="block text-xs text-white/50 uppercase tracking-wide mb-2 px-3"><?= h(__('nav.language')) ?></label>
                <div class="flex gap-2 px-3">
                    <button type="button" data-target-url="<?= h($langSwitchEnUrl) ?>" data-action="setLanguage" data-action-args='["en","__this__"]' class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-lg text-sm font-medium transition-colors <?= $currentLang === 'en' ? 'bg-brand-yellow text-brand-darker' : 'bg-white/10 text-white/80 hover:bg-white/20' ?>">
                        <span>🇺🇸</span> <?= h(__('nav.language_english')) ?>
                    </button>
                    <button type="button" data-target-url="<?= h($langSwitchEsUrl) ?>" data-action="setLanguage" data-action-args='["es","__this__"]' class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-lg text-sm font-medium transition-colors <?= $currentLang === 'es' ? 'bg-brand-yellow text-brand-darker' : 'bg-white/10 text-white/80 hover:bg-white/20' ?>">
                        <span>🇵🇷</span> <?= h(__('nav.language_spanish')) ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</nav>

<script <?= cspNonceAttr() ?>>
function closeMobileMenu() {
    const menu = document.getElementById('mobile-menu');
    const button = document.getElementById('mobile-menu-button');
    if (!menu || !button) return;
    menu.classList.add('hidden');
    button.setAttribute('aria-expanded', 'false');
}

function closeBeachesDropdown() {
    const menu = document.getElementById('beaches-dropdown-menu');
    const button = document.querySelector('#beaches-dropdown button');
    if (menu) {
        menu.classList.add('hidden');
    }
    if (button) {
        button.setAttribute('aria-expanded', 'false');
    }
}

function closeLangDropdown() {
    const menu = document.getElementById('lang-dropdown-menu');
    const button = document.querySelector('#lang-dropdown button');
    if (menu) {
        menu.classList.add('hidden');
    }
    if (button) {
        button.setAttribute('aria-expanded', 'false');
    }
}

function toggleMobileMenu() {
    const menu = document.getElementById('mobile-menu');
    const button = document.getElementById('mobile-menu-button');
    if (!menu || !button) return;
    const isOpen = !menu.classList.contains('hidden');
    menu.classList.toggle('hidden');
    button.setAttribute('aria-expanded', (!isOpen).toString());
}

function toggleBeachesDropdown() {
    const menu = document.getElementById('beaches-dropdown-menu');
    const button = document.querySelector('#beaches-dropdown button');
    if (!menu || !button) return;
    const isOpen = !menu.classList.contains('hidden');
    menu.classList.toggle('hidden');
    button.setAttribute('aria-expanded', (!isOpen).toString());
    closeLangDropdown();
}

function toggleLangDropdown() {
    const menu = document.getElementById('lang-dropdown-menu');
    const button = document.querySelector('#lang-dropdown button');
    if (!menu || !button) return;
    const isOpen = !menu.classList.contains('hidden');
    menu.classList.toggle('hidden');
    button.setAttribute('aria-expanded', (!isOpen).toString());
    closeBeachesDropdown();
}

function setLanguage(lang, targetUrl) {
    const redirectUrl = targetUrl || window.location.pathname + window.location.search;
    const body = new URLSearchParams({
        lang: lang,
        redirect: redirectUrl
    });

    fetch('/api/set-language.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        credentials: 'same-origin',
        body: body.toString()
    })
        .then((response) => response.ok ? response.json() : null)
        .then((data) => {
            const nextUrl = data && data.redirect_url ? data.redirect_url : redirectUrl;
            window.location.assign(nextUrl);
        })
        .catch(() => {
            window.location.assign(redirectUrl);
        });
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('#beaches-dropdown')) {
        closeBeachesDropdown();
    }
    if (!e.target.closest('#lang-dropdown')) {
        closeLangDropdown();
    }
    if (!e.target.closest('#mobile-menu') && !e.target.closest('#mobile-menu-button')) {
        closeMobileMenu();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeBeachesDropdown();
        closeLangDropdown();
        closeMobileMenu();
    }
});
</script>
