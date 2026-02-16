<?php
$appName = $appName ?? ($_ENV['APP_NAME'] ?? 'Beach Finder');
$currentLang = $currentLang ?? getCurrentLanguage();
$user = $user ?? currentUser();
$navMapHref = $navMapHref ?? null;
if (!is_string($navMapHref) || $navMapHref === '') {
    $requestUri = (string)($_SERVER['REQUEST_URI'] ?? '/');
    $currentPath = (string)(parse_url($requestUri, PHP_URL_PATH) ?? '/');
    if ($currentPath === '') {
        $currentPath = '/';
    }
    $queryParams = $_GET;
    $queryParams['view'] = 'map';
    $queryString = http_build_query($queryParams);
    $navMapHref = $currentPath . ($queryString !== '' ? '?' . $queryString : '?view=map');
}
?>

<!-- Skip Links for Accessibility -->
<a href="#main-content" class="skip-link sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:bg-cyan-500 focus:text-white focus:px-4 focus:py-2 focus:rounded-lg focus:shadow-lg focus:outline-none">
    Skip to main content
</a>
<a href="#beach-grid" class="skip-link sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-48 focus:z-50 focus:bg-cyan-500 focus:text-white focus:px-4 focus:py-2 focus:rounded-lg focus:shadow-lg focus:outline-none">
    Skip to beaches
</a>

<!-- Navigation -->
<nav id="main-nav" class="fixed top-0 w-full z-50 px-4 sm:px-6 py-4 transition-all duration-300" role="navigation" aria-label="Main navigation">
    <div class="max-w-7xl mx-auto flex items-center justify-between">
        <!-- Logo with rotating sun -->
        <a href="/" class="flex items-center gap-2 focus:outline-none focus:ring-2 focus:ring-brand-yellow focus:ring-offset-2 focus:ring-offset-brand-darker rounded-lg p-1" aria-label="<?= h($appName) ?> - Home">
            <i data-lucide="sun" class="w-7 h-7 text-brand-yellow hover-spin transition-all" aria-hidden="true"></i>
            <span class="text-xl font-bold text-white"><?= h($appName) ?></span>
        </a>

        <!-- Center Navigation (Desktop) -->
        <div class="hidden md:flex items-center bg-brand-darker/50 backdrop-blur-md px-4 py-2 rounded-full border border-white/10" role="menubar">
            <!-- Beaches Dropdown -->
            <div class="relative" id="beaches-dropdown">
                <button type="button"
                        onclick="toggleBeachesDropdown()"
                        class="flex items-center gap-1 text-sm text-white/80 hover:text-brand-yellow px-4 py-1 transition-colors"
                        role="menuitem"
                        aria-expanded="false"
                        aria-haspopup="true">
                    <span>Beaches</span>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
                </button>
                <div id="beaches-dropdown-menu" class="hidden absolute left-0 top-full mt-3 w-56 bg-brand-dark/95 backdrop-blur-md rounded-xl shadow-glass border border-white/10 py-2 z-50">
                    <div class="px-3 py-2 text-xs text-white/40 uppercase tracking-wider">Find by Activity</div>
                    <a href="/?tags[]=surfing#beaches" class="flex items-center gap-3 px-4 py-2.5 text-sm text-white/80 hover:text-brand-yellow hover:bg-white/5 transition-colors">
                        <span class="text-lg">🏄‍♂️</span>
                        <span>Surfing</span>
                    </a>
                    <a href="/?tags[]=snorkeling#beaches" class="flex items-center gap-3 px-4 py-2.5 text-sm text-white/80 hover:text-brand-yellow hover:bg-white/5 transition-colors">
                        <span class="text-lg">🤿</span>
                        <span>Snorkeling</span>
                    </a>
                    <a href="/?tags[]=family-friendly#beaches" class="flex items-center gap-3 px-4 py-2.5 text-sm text-white/80 hover:text-brand-yellow hover:bg-white/5 transition-colors">
                        <span class="text-lg">👨‍👩‍👧</span>
                        <span>Family Friendly</span>
                    </a>
                    <a href="/?tags[]=secluded#beaches" class="flex items-center gap-3 px-4 py-2.5 text-sm text-white/80 hover:text-brand-yellow hover:bg-white/5 transition-colors">
                        <span class="text-lg">🌴</span>
                        <span>Secluded</span>
                    </a>
                    <a href="/?tags[]=swimming#beaches" class="flex items-center gap-3 px-4 py-2.5 text-sm text-white/80 hover:text-brand-yellow hover:bg-white/5 transition-colors">
                        <span class="text-lg">🏊</span>
                        <span>Swimming</span>
                    </a>
                    <div class="border-t border-white/10 mt-2 pt-2">
                        <a href="/#beaches" class="flex items-center gap-3 px-4 py-2.5 text-sm text-brand-yellow hover:bg-white/5 transition-colors">
                            <i data-lucide="compass" class="w-4 h-4"></i>
                            <span>View All Beaches</span>
                        </a>
                    </div>
                </div>
            </div>

            <a href="/quiz" class="text-sm text-white/80 hover:text-brand-yellow px-4 py-1 transition-colors" role="menuitem">Quiz</a>
            <a href="<?= h($navMapHref) ?>"
               class="text-sm text-white/80 hover:text-brand-yellow px-4 py-1 transition-colors"
               role="menuitem"
               data-context-map-link>Map</a>
        </div>

        <!-- Right Side - Auth & Language -->
        <div class="hidden md:flex items-center gap-4">
            <!-- Language Switcher -->
            <div class="relative" id="lang-dropdown">
                <button type="button"
                        onclick="toggleLangDropdown()"
                        class="flex items-center gap-1 px-2 py-1.5 text-sm text-white/70 hover:text-white rounded-lg transition-colors"
                        aria-label="<?= __('nav.language') ?>"
                        aria-expanded="false"
                        aria-haspopup="true">
                    <span><?= getLanguageFlag($currentLang) ?></span>
                    <span class="hidden sm:inline"><?= strtoupper($currentLang) ?></span>
                    <i data-lucide="chevron-down" class="w-3 h-3"></i>
                </button>
                <div id="lang-dropdown-menu" class="hidden absolute right-0 mt-1 w-32 bg-brand-dark/95 backdrop-blur-md rounded-lg shadow-glass border border-white/10 py-1 z-50">
                    <button type="button" onclick="setLanguage('en')" class="flex items-center gap-2 w-full px-3 py-2 text-sm text-left hover:bg-white/10 <?= $currentLang === 'en' ? 'text-brand-yellow' : 'text-white/80' ?>">
                        <span>🇺🇸</span> English
                    </button>
                    <button type="button" onclick="setLanguage('es')" class="flex items-center gap-2 w-full px-3 py-2 text-sm text-left hover:bg-white/10 <?= $currentLang === 'es' ? 'text-brand-yellow' : 'text-white/80' ?>">
                        <span>🇵🇷</span> Español
                    </button>
                </div>
            </div>

            <?php if ($user): ?>
                <div class="flex items-center gap-3">
                    <a href="/profile?tab=favorites" class="text-white/70 hover:text-brand-yellow transition-colors">
                        <i data-lucide="heart" class="w-5 h-5"></i>
                    </a>
                    <a href="/profile" class="flex items-center gap-2 hover:opacity-80 transition-opacity">
                        <?php if (!empty($user['avatar_url'])): ?>
                        <img src="<?= h($user['avatar_url']) ?>" alt="" class="w-8 h-8 rounded-full border border-white/20">
                        <?php else: ?>
                        <div class="w-8 h-8 rounded-full bg-brand-yellow/20 flex items-center justify-center text-brand-yellow font-medium text-sm border border-brand-yellow/30">
                            <?= strtoupper(substr($user['name'] ?? $user['email'], 0, 1)) ?>
                        </div>
                        <?php endif; ?>
                    </a>
                    <a href="/logout" class="text-sm text-white/60 hover:text-white transition-colors">Logout</a>
                </div>
            <?php else: ?>
                <a href="/login" class="bg-brand-yellow hover:bg-yellow-300 text-brand-darker px-5 py-2 rounded-full text-sm font-semibold transition-colors">
                    Sign In
                </a>
            <?php endif; ?>
        </div>

        <!-- Mobile menu button -->
        <div class="flex items-center gap-2 md:hidden">
            <button type="button"
                    id="mobile-menu-button"
                    onclick="toggleMobileMenu()"
                    class="p-2 rounded-lg text-white/80 hover:text-white focus:outline-none focus:ring-2 focus:ring-brand-yellow"
                    aria-expanded="false"
                    aria-controls="mobile-menu"
                    aria-label="Open main menu">
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
            <div class="text-xs text-white/40 uppercase tracking-wider px-3 pt-2 pb-1">Find Beaches</div>
            <a href="/?tags[]=surfing#beaches" class="flex items-center gap-3 text-white/80 hover:text-brand-yellow py-2.5 px-3 rounded-lg hover:bg-white/5 transition-colors" role="menuitem">
                <span class="text-lg">🏄‍♂️</span>
                <span>Surfing</span>
            </a>
            <a href="/?tags[]=snorkeling#beaches" class="flex items-center gap-3 text-white/80 hover:text-brand-yellow py-2.5 px-3 rounded-lg hover:bg-white/5 transition-colors" role="menuitem">
                <span class="text-lg">🤿</span>
                <span>Snorkeling</span>
            </a>
            <a href="/?tags[]=family-friendly#beaches" class="flex items-center gap-3 text-white/80 hover:text-brand-yellow py-2.5 px-3 rounded-lg hover:bg-white/5 transition-colors" role="menuitem">
                <span class="text-lg">👨‍👩‍👧</span>
                <span>Family Friendly</span>
            </a>
            <a href="/?tags[]=secluded#beaches" class="flex items-center gap-3 text-white/80 hover:text-brand-yellow py-2.5 px-3 rounded-lg hover:bg-white/5 transition-colors" role="menuitem">
                <span class="text-lg">🌴</span>
                <span>Secluded</span>
            </a>
            <a href="/#beaches" class="flex items-center gap-3 text-brand-yellow py-2.5 px-3 rounded-lg hover:bg-white/5 transition-colors" role="menuitem">
                <i data-lucide="compass" class="w-5 h-5" aria-hidden="true"></i>
                <span>All Beaches</span>
            </a>

            <!-- Tools Section -->
            <div class="border-t border-white/10 mt-3 pt-3">
                <div class="text-xs text-white/40 uppercase tracking-wider px-3 pt-1 pb-1">Tools</div>
                <a href="/quiz" class="flex items-center gap-3 text-white/80 hover:text-brand-yellow py-2.5 px-3 rounded-lg hover:bg-white/5 transition-colors" role="menuitem">
                    <i data-lucide="sparkles" class="w-5 h-5" aria-hidden="true"></i>
                    <span>Find My Beach Quiz</span>
                </a>
                <a href="<?= h($navMapHref) ?>"
                   class="flex items-center gap-3 text-white/80 hover:text-brand-yellow py-2.5 px-3 rounded-lg hover:bg-white/5 transition-colors"
                   role="menuitem"
                   data-context-map-link>
                    <i data-lucide="map" class="w-5 h-5" aria-hidden="true"></i>
                    <span>Map View</span>
                </a>
            </div>
            <?php if ($user): ?>
                <a href="/profile" class="flex items-center gap-3 text-white/80 hover:text-brand-yellow py-3 px-3 rounded-lg hover:bg-white/5 transition-colors" role="menuitem">
                    <i data-lucide="user" class="w-5 h-5"></i>
                    <span>My Profile</span>
                </a>
                <a href="/profile?tab=favorites" class="flex items-center gap-3 text-white/80 hover:text-brand-yellow py-3 px-3 rounded-lg hover:bg-white/5 transition-colors" role="menuitem">
                    <i data-lucide="heart" class="w-5 h-5 text-red-400 fill-red-400"></i>
                    <span>Favorites</span>
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
                        <span class="text-sm text-white/70"><?= h($user['name'] ?? 'User') ?></span>
                    </div>
                    <a href="/logout" class="block text-red-400 hover:text-red-300 py-2 px-3">Logout</a>
                </div>
            <?php else: ?>
                <a href="/login" class="block bg-brand-yellow text-brand-darker text-center py-3 rounded-lg mt-3 font-semibold">Sign In</a>
            <?php endif; ?>

            <!-- Mobile Language Switcher -->
            <div class="pt-3 mt-3 border-t border-white/10">
                <label class="block text-xs text-white/50 uppercase tracking-wide mb-2 px-3">Language</label>
                <div class="flex gap-2 px-3">
                    <button type="button" onclick="setLanguage('en')" class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-lg text-sm font-medium transition-colors <?= $currentLang === 'en' ? 'bg-brand-yellow text-brand-darker' : 'bg-white/10 text-white/80 hover:bg-white/20' ?>">
                        <span>🇺🇸</span> English
                    </button>
                    <button type="button" onclick="setLanguage('es')" class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-lg text-sm font-medium transition-colors <?= $currentLang === 'es' ? 'bg-brand-yellow text-brand-darker' : 'bg-white/10 text-white/80 hover:bg-white/20' ?>">
                        <span>🇵🇷</span> Español
                    </button>
                </div>
            </div>
        </div>
    </div>
</nav>

<script>
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
    // Close lang dropdown if open
    closeLangDropdown();
}

function toggleLangDropdown() {
    const menu = document.getElementById('lang-dropdown-menu');
    const button = document.querySelector('#lang-dropdown button');
    if (!menu || !button) return;
    const isOpen = !menu.classList.contains('hidden');
    menu.classList.toggle('hidden');
    button.setAttribute('aria-expanded', (!isOpen).toString());
    // Close beaches dropdown if open
    closeBeachesDropdown();
}

function setLanguage(lang) {
    fetch('/api/set-language.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'lang=' + encodeURIComponent(lang)
    }).then(() => location.reload());
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
