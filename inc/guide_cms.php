<?php
/**
 * Guide CMS rendering and data access.
 */

if (defined('GUIDE_CMS_INCLUDED')) {
    return;
}
define('GUIDE_CMS_INCLUDED', true);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/referrals.php';
require_once APP_ROOT . '/components/seo-schemas.php';

function guideCmsLoadArticleBySlug(string $slug, bool $publishedOnly = true): ?array
{
    $slug = trim($slug);
    if ($slug === '') {
        return null;
    }

    $sql = 'SELECT * FROM guide_articles WHERE slug = :slug';
    $params = [':slug' => $slug];

    if ($publishedOnly) {
        $sql .= ' AND status = "published"';
    }

    $sql .= ' LIMIT 1';

    $row = queryOne($sql, $params);
    return $row ?: null;
}

function guideCmsLoadArticleById(string $id): ?array
{
    $id = trim($id);
    if ($id === '') {
        return null;
    }

    $row = queryOne('SELECT * FROM guide_articles WHERE id = :id LIMIT 1', [':id' => $id]);
    return $row ?: null;
}

function guideCmsLoadBlocks(string $guideId, bool $publishedOnly = true): array
{
    $guideId = trim($guideId);
    if ($guideId === '') {
        return [];
    }

    $sql = 'SELECT * FROM guide_blocks WHERE guide_id = :guide_id';
    if ($publishedOnly) {
        $sql .= ' AND status = "published"';
    }
    $sql .= ' ORDER BY block_order ASC, created_at ASC';

    $rows = query($sql, [':guide_id' => $guideId]);
    return is_array($rows) ? $rows : [];
}

function guideCmsLocalizedText(array $payload, string $locale, string $enKey, string $esKey = ''): string
{
    $locale = referralNormalizeLocale($locale);
    $esKey = $esKey !== '' ? $esKey : $enKey . '_es';

    if ($locale === 'es') {
        $candidate = trim((string) ($payload[$esKey] ?? ''));
        if ($candidate !== '') {
            return $candidate;
        }
    }

    $fallback = trim((string) ($payload[$enKey] ?? ''));
    return $fallback;
}

function guideCmsRenderBlock(array $block, array $article, string $locale = 'en'): string
{
    $locale = referralNormalizeLocale($locale);
    $kind = trim((string) ($block['block_kind'] ?? ''));
    $payload = referralJsonDecode((string) ($block['payload_json'] ?? ''), []);

    if ($kind === 'rich_text') {
        $html = guideCmsLocalizedText($payload, $locale, 'html_en', 'html_es');
        if ($html === '') {
            return '';
        }
        return '<div class="guide-cms-block guide-cms-richtext prose prose-lg max-w-none">' . $html . '</div>';
    }

    if ($kind === 'heading') {
        $text = guideCmsLocalizedText($payload, $locale, 'text_en', 'text_es');
        if ($text === '') {
            return '';
        }

        $level = (int) ($payload['level'] ?? 2);
        if ($level < 2 || $level > 4) {
            $level = 2;
        }

        return '<h' . $level . ' class="text-2xl font-bold text-gray-900 mt-8 mb-4">' . h($text) . '</h' . $level . '>';
    }

    if ($kind === 'image') {
        $src = trim((string) ($payload['src'] ?? ''));
        if ($src === '') {
            return '';
        }

        $alt = guideCmsLocalizedText($payload, $locale, 'alt_en', 'alt_es');
        $caption = guideCmsLocalizedText($payload, $locale, 'caption_en', 'caption_es');

        $out = '<figure class="guide-cms-block my-6">';
        $out .= '<img src="' . h($src) . '" alt="' . h($alt) . '" loading="lazy" class="w-full rounded-xl border border-gray-200">';
        if ($caption !== '') {
            $out .= '<figcaption class="text-sm text-gray-600 mt-2">' . h($caption) . '</figcaption>';
        }
        $out .= '</figure>';

        return $out;
    }

    if ($kind === 'referral_block') {
        $campaignSlug = trim((string) ($payload['campaign_slug'] ?? ''));
        if ($campaignSlug === '') {
            return '';
        }

        $campaign = referralGetCampaignBySlug($campaignSlug, true);
        if (!$campaign) {
            return '';
        }

        $title = guideCmsLocalizedText($payload, $locale, 'title_en', 'title_es');
        $description = guideCmsLocalizedText($payload, $locale, 'description_en', 'description_es');
        $buttonLabel = guideCmsLocalizedText($payload, $locale, 'button_label_en', 'button_label_es');
        $style = trim((string) ($payload['style'] ?? 'primary'));
        $blockSlug = trim((string) ($payload['block_slug'] ?? ''));

        $pageSlug = trim((string) ($article['slug'] ?? ''));

        $card = referralRenderCampaignCard(
            $campaign,
            [
                'locale' => $locale,
                'title' => $title !== '' ? $title : (string) ($campaign['name'] ?? ''),
                'description' => $description,
                'button_label' => $buttonLabel !== '' ? $buttonLabel : ($locale === 'es' ? 'Ver oferta' : 'View offer'),
                'button_style' => $style,
                'page_type' => 'guide',
                'page_slug' => $pageSlug,
                'placement' => 'inline_block',
                'block_slug' => $blockSlug,
            ]
        );

        if ($card === '') {
            return '';
        }

        $disclosure = referralDisclosureText($locale, $campaign);
        return '<div class="guide-cms-block my-6">' . $card . '<p class="text-xs text-gray-500 italic mt-2">' . h($disclosure) . '</p></div>';
    }

    if ($kind === 'html') {
        $html = guideCmsLocalizedText($payload, $locale, 'html_en', 'html_es');
        if ($html === '') {
            return '';
        }
        return '<div class="guide-cms-block">' . $html . '</div>';
    }

    return '';
}

function guideCmsBuildHead(array $article, string $locale): string
{
    $locale = referralNormalizeLocale($locale);
    $slug = (string) ($article['slug'] ?? '');

    $title = $locale === 'es'
        ? trim((string) ($article['title_es'] ?? ''))
        : trim((string) ($article['title_en'] ?? ''));
    if ($title === '') {
        $title = trim((string) ($article['title_en'] ?? 'Puerto Rico Beach Guide'));
    }

    $description = $locale === 'es'
        ? trim((string) ($article['description_es'] ?? ''))
        : trim((string) ($article['description_en'] ?? ''));
    if ($description === '') {
        $description = trim((string) ($article['description_en'] ?? '')); 
    }
    if ($description === '') {
        $description = $locale === 'es' ? 'Guia de playas de Puerto Rico.' : 'Puerto Rico beach guide.';
    }

    $date = trim((string) ($article['updated_at'] ?? $article['published_at'] ?? ''));
    $date = $date !== '' ? substr($date, 0, 10) : null;

    $head = '';
    $head .= articleSchema($title, $description, '/guides/' . $slug, null, $date);
    $head .= breadcrumbSchema([
        ['name' => $locale === 'es' ? 'Inicio' : 'Home', 'url' => $locale === 'es' ? '/es' : '/'],
        ['name' => $locale === 'es' ? 'Guias' : 'Guides', 'url' => $locale === 'es' ? '/es/guias/' : '/guides/'],
        ['name' => $title, 'url' => '/guides/' . $slug],
    ]);

    return $head;
}

function guideCmsRenderArticlePage(array $article, array $blocks, string $locale = 'en'): void
{
    $locale = referralNormalizeLocale($locale);

    $pageTitle = $locale === 'es'
        ? trim((string) ($article['title_es'] ?? ''))
        : trim((string) ($article['title_en'] ?? ''));
    if ($pageTitle === '') {
        $pageTitle = trim((string) ($article['title_en'] ?? 'Puerto Rico Beach Guide'));
    }

    $pageDescription = $locale === 'es'
        ? trim((string) ($article['description_es'] ?? ''))
        : trim((string) ($article['description_en'] ?? ''));
    if ($pageDescription === '') {
        $pageDescription = trim((string) ($article['description_en'] ?? '')); 
    }
    if ($pageDescription === '') {
        $pageDescription = $locale === 'es' ? 'Guia de playas de Puerto Rico.' : 'Puerto Rico beach guide.';
    }

    $extraHead = ($extraHead ?? '') . guideCmsBuildHead($article, $locale);

    $pageTheme = 'guide';
    $skipMapCSS = true;
    $skipMapScripts = true;
    $pageShellMode = 'start';
    include APP_ROOT . '/components/page-shell.php';

    $breadcrumbs = [
        ['name' => $locale === 'es' ? 'Inicio' : 'Home', 'url' => $locale === 'es' ? '/es' : '/'],
        ['name' => $locale === 'es' ? 'Guias' : 'Guides', 'url' => $locale === 'es' ? '/es/guias/' : '/guides/'],
        ['name' => $pageTitle],
    ];
    include APP_ROOT . '/components/hero-guide.php';

    echo '<main class="container mx-auto px-4 container-padding py-10">';
    echo '<article class="guide-article bg-white rounded-lg shadow-card p-6 md:p-8">';

    if (!empty($blocks)) {
        foreach ($blocks as $block) {
            $html = guideCmsRenderBlock($block, $article, $locale);
            if ($html === '') {
                continue;
            }
            echo $html;
        }
    }

    echo '</article>';
    echo '</main>';

    $pageShellMode = 'end';
    include APP_ROOT . '/components/page-shell.php';
}

function guideCmsRenderBySlug(string $slug, string $locale = 'en'): bool
{
    $article = guideCmsLoadArticleBySlug($slug, true);
    if (!$article) {
        return false;
    }

    $blocks = guideCmsLoadBlocks((string) $article['id'], true);
    guideCmsRenderArticlePage($article, $blocks, $locale);
    return true;
}
