<?php
/**
 * Shareable Quiz Results Page
 * URL: /quiz-results?token=...
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/session.php';
if (isset($_COOKIE['BEACH_FINDER_SESSION']) && session_status() === PHP_SESSION_NONE) {
    session_cache_limiter('');
    session_start();
}

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/constants.php';
require_once APP_ROOT . '/inc/locale_routes.php';
require_once APP_ROOT . '/inc/i18n.php';

$token = trim((string)($_GET['token'] ?? ''));
if ($token === '') {
    $pageTitle = __('quiz_results.title');
    $pageDescription = __('quiz_results.no_quiz_desc');
    include APP_ROOT . '/components/header.php';
    ?>
    <section class="hero-gradient-dark text-white py-12 md:py-16">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-3xl md:text-5xl font-bold mb-4"><?= h(__('quiz_results.title')) ?></h1>
            <p class="text-lg md:text-xl opacity-90"><?= h(__('quiz_results.no_quiz_desc')) ?></p>
            <div class="mt-6 flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="/quiz" class="inline-flex items-center justify-center bg-brand-yellow hover:bg-yellow-300 text-brand-darker px-5 py-2.5 rounded-lg font-semibold transition-colors">
                    <?= h(__('quiz_results.take_quiz')) ?>
                </a>
                <a href="/best-beaches" class="inline-flex items-center justify-center bg-white/10 hover:bg-white/20 border border-white/20 px-5 py-2.5 rounded-lg font-medium transition-colors">
                    <?= h(__('quiz_results.browse_best')) ?>
                </a>
            </div>
        </div>
    </section>
    <?php
    include APP_ROOT . '/components/footer.php';
    exit;
}

$robotsOverride = 'noindex, nofollow, noarchive';
$row = queryOne('SELECT * FROM quiz_results WHERE token = :token', [':token' => $token]);
if (!$row) {
    http_response_code(404);
    $pageTitle = __('quiz_results.not_found_title');
    $pageDescription = __('quiz_results.not_found_desc');
    include APP_ROOT . '/components/header.php';
    echo '<div class="max-w-2xl mx-auto px-4 py-16 text-center"><h1 class="text-2xl font-bold text-white mb-3">' . h(__('quiz_results.not_found_heading')) . '</h1><p class="text-gray-400">' . h(__('quiz_results.not_found_expired')) . '</p></div>';
    include APP_ROOT . '/components/footer.php';
    exit;
}

$matches = json_decode((string)($row['matched_beaches'] ?? '[]'), true);
if (!is_array($matches) || empty($matches)) {
    http_response_code(404);
    $pageTitle = __('quiz_results.not_found_title');
    $pageDescription = __('quiz_results.not_found_desc');
    include APP_ROOT . '/components/header.php';
    echo '<div class="max-w-2xl mx-auto px-4 py-16 text-center"><h1 class="text-2xl font-bold text-white mb-3">' . h(__('quiz_results.not_found_heading')) . '</h1><p class="text-gray-400">' . h(__('quiz_results.not_found_empty')) . '</p></div>';
    include APP_ROOT . '/components/footer.php';
    exit;
}

$beachIds = [];
foreach ($matches as $m) {
    if (is_array($m) && !empty($m['id'])) {
        $beachIds[] = (string)$m['id'];
    }
}
$beachIds = array_values(array_unique(array_filter($beachIds)));

$beachesById = [];
if (!empty($beachIds)) {
    $placeholders = implode(',', array_fill(0, count($beachIds), '?'));
    $rows = query("SELECT * FROM beaches WHERE id IN ($placeholders) AND publish_status = 'published'", $beachIds) ?: [];
    foreach ($rows as $b) {
        $beachesById[$b['id']] = $b;
    }
}

$pageTitle = __('quiz_results.your_matches');
$pageDescription = __('quiz_results.matches_desc');
include APP_ROOT . '/components/header.php';
?>

<section class="hero-gradient-dark text-white py-12 md:py-16">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-3xl md:text-5xl font-bold mb-4"><?= h(__('quiz_results.your_matches')) ?></h1>
        <p class="text-lg md:text-xl opacity-90"><?= h(__('quiz_results.save_link')) ?></p>
        <div class="mt-6 flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="/quiz" class="inline-flex items-center justify-center bg-white/10 hover:bg-white/20 border border-white/20 px-5 py-2.5 rounded-lg font-medium transition-colors">
                <?= h(__('quiz_results.retake')) ?>
            </a>
            <button type="button"
                    data-action="bfShareCurrentQuizResults"
                    data-bf-source="quiz_results"
                    class="inline-flex items-center justify-center bg-brand-yellow hover:bg-yellow-300 text-brand-darker px-5 py-2.5 rounded-lg font-semibold transition-colors">
                <?= h(__('quiz_results.share')) ?>
            </button>
        </div>
    </div>
</section>

<section class="py-10 bg-brand-dark">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white/5 border border-white/10 rounded-2xl p-6">
            <h2 class="text-xl font-bold text-white mb-4"><?= h(__('quiz_results.top_matches')) ?></h2>

            <div class="space-y-4">
                <?php foreach ($matches as $m):
                    $id = (string)($m['id'] ?? '');
                    $score = (int)($m['score'] ?? 0);
                    $reasons = $m['match_reasons'] ?? [];
                    $b = $id && isset($beachesById[$id]) ? $beachesById[$id] : null;
                    $slug = (string)($b['slug'] ?? ($m['slug'] ?? ''));
                    $name = (string)($b['name'] ?? ($m['name'] ?? __('beach.beach')));
                    $muni = (string)($b['municipality'] ?? ($m['municipality'] ?? ''));
                    $cover = (string)($b['cover_image'] ?? ($m['cover_image'] ?? '/images/beaches/placeholder-beach.webp'));
                ?>
                <div class="flex gap-4 bg-white/5 border border-white/10 rounded-xl p-4"
                     data-bf-beach-id="<?= h($id) ?>"
                     data-bf-beach-slug="<?= h($slug) ?>"
                     data-bf-municipality="<?= h($muni) ?>"
                     data-bf-source="quiz_results">
                    <img src="<?= h($cover) ?>" alt="<?= h($name) ?>" class="w-20 h-20 rounded-lg object-cover shrink-0">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-sm text-gray-400"><?= h($muni) ?></div>
                                <div class="text-lg font-semibold text-white"><?= h($name) ?></div>
                            </div>
                            <div class="text-sm font-bold text-brand-yellow"><?= $score ?>%</div>
                        </div>
                        <?php if (is_array($reasons) && !empty($reasons)): ?>
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            <?php foreach (array_slice($reasons, 0, 4) as $reason): ?>
                            <span class="text-xs bg-brand-yellow/10 text-brand-yellow px-2 py-0.5 rounded-full border border-brand-yellow/20"><?= h((string)$reason) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <a href="/beach/<?= h($slug) ?>"
                               class="inline-flex items-center gap-2 bg-white/10 hover:bg-white/20 text-white px-3 py-2 rounded-lg text-sm border border-white/10 transition-colors">
                                <?= h(__('quiz_results.view_details')) ?>
                            </a>
                            <?php if ($b): ?>
                            <a href="<?= h(getDirectionsUrl($b)) ?>" target="_blank" rel="noopener noreferrer"
                               data-bf-track="directions"
                               class="inline-flex items-center gap-2 bg-brand-yellow hover:bg-yellow-300 text-brand-darker px-3 py-2 rounded-lg text-sm font-semibold transition-colors">
                                <?= h(__('quiz_results.directions')) ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<?php include APP_ROOT . '/components/footer.php'; ?>

<script <?= cspNonceAttr() ?>>
window.QR_STRINGS = <?= json_encode([
    'share_title' => __('quiz_results.share_title'),
    'share_text' => __('quiz_results.share_text'),
    'link_copied' => __('quiz_results.link_copied'),
    'share_error' => __('quiz_results.share_error'),
]) ?>;
async function bfShareCurrentQuizResults() {
    const url = window.location.href;
    const title = QR_STRINGS.share_title;
    const text = QR_STRINGS.share_text;

    if (typeof window.bfTrack === 'function') {
        window.bfTrack('share_click', { source: 'quiz_results' });
    }

    if (navigator.share) {
        try {
            await navigator.share({ title, text, url });
            return;
        } catch (e) {
            if (e && e.name === 'AbortError') return;
        }
    }

    try {
        await navigator.clipboard.writeText(url);
        if (typeof window.showToast === 'function') {
            window.showToast(QR_STRINGS.link_copied, 'success', 2500);
        }
    } catch (e) {
        if (typeof window.showToast === 'function') {
            window.showToast(QR_STRINGS.share_error, 'warning', 3500);
        }
    }
}
</script>
