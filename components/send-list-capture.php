<?php
/**
 * Send-list capture module.
 *
 * Required:
 * - $contextType (string): municipality|collection
 * - $contextKey (string): municipality slug OR collection key
 *
 * Optional:
 * - $filtersQuery (string): URL query string (without leading '?')
 * - $title (string)
 * - $subtitle (string)
 */

require_once __DIR__ . '/../inc/helpers.php';

$_t = function_exists('__');

$contextType = isset($contextType) ? (string) $contextType : '';
$contextKey = isset($contextKey) ? (string) $contextKey : '';
$filtersQuery = isset($filtersQuery) ? (string) $filtersQuery : '';
$pagePath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$defaultTitle = $_t ? __('collection.send_list_title') : 'Send me this list';
$defaultSubtitle = $_t ? __('collection.send_list_subtitle') : 'Get the beaches + Google Maps links in your inbox (no account required).';
$title = isset($title) && (string) $title !== '' ? (string) $title : $defaultTitle;
$subtitle = isset($subtitle) && (string) $subtitle !== '' ? (string) $subtitle : $defaultSubtitle;
$sendLabel = $_t ? __('collection.send_button') : 'Send';

if ($contextType === '' || $contextKey === '') {
    return;
}
?>

<section class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-5 md:p-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-lg font-bold text-white"><?= h($title) ?></h2>
            <p class="text-sm text-gray-400 mt-1"><?= h($subtitle) ?></p>
        </div>

        <form data-bf-form="send-list" class="w-full md:w-auto flex flex-col sm:flex-row gap-2 sm:items-center">
            <input type="hidden" name="context_type" value="<?= h($contextType) ?>">
            <input type="hidden" name="context_key" value="<?= h($contextKey) ?>">
            <input type="hidden" name="filters_query" value="<?= h($filtersQuery) ?>">
            <input type="hidden" name="page_path" value="<?= h($pagePath) ?>">
            <input type="hidden" name="website" value="" autocomplete="off">

            <input type="email"
                   name="email"
                   required
                   placeholder="you@email.com"
                   class="w-full sm:w-64 px-3 h-11 rounded-lg bg-white/5 border border-white/20 text-white placeholder-gray-500 focus:ring-2 focus:ring-brand-yellow/50 focus:border-brand-yellow/50">

            <button type="submit"
                    class="h-11 px-5 rounded-lg bg-brand-yellow hover:bg-yellow-300 text-brand-darker font-semibold transition-colors">
                <?= h($sendLabel) ?>
            </button>
        </form>
    </div>
</section>
