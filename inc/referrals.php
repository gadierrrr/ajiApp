<?php
/**
 * Provider-agnostic referral system.
 */

if (defined('REFERRALS_INCLUDED')) {
    return;
}
define('REFERRALS_INCLUDED', true);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function referralNormalizeLocale(?string $locale): string
{
    return $locale === 'es' ? 'es' : 'en';
}

function referralNow(): string
{
    return gmdate('Y-m-d H:i:s');
}

function referralJsonDecode(?string $json, array $fallback = []): array
{
    if (!is_string($json) || trim($json) === '') {
        return $fallback;
    }

    $decoded = json_decode($json, true);
    return is_array($decoded) ? $decoded : $fallback;
}

function referralAllowedTargetHosts(): array
{
    $hosts = ['expedia.com', 'www.expedia.com'];

    $envHosts = trim((string) env('REFERRAL_ALLOWED_HOSTS', ''));
    if ($envHosts !== '') {
        foreach (explode(',', $envHosts) as $host) {
            $host = strtolower(trim($host));
            if ($host !== '') {
                $hosts[] = $host;
            }
        }
    }

    $appUrl = trim((string) env('APP_URL', ''));
    if ($appUrl !== '') {
        $appHost = strtolower(trim((string) parse_url($appUrl, PHP_URL_HOST)));
        if ($appHost !== '') {
            $hosts[] = $appHost;
        }
    }

    return array_values(array_unique($hosts));
}

function referralValidatedTargetUrl(string $target): string
{
    $target = trim($target);
    if ($target === '') {
        return '';
    }

    if (str_starts_with($target, '/')) {
        return sanitizeInternalRedirect($target, '');
    }

    $parts = parse_url($target);
    if (!is_array($parts)) {
        return '';
    }

    $scheme = strtolower(trim((string) ($parts['scheme'] ?? '')));
    $host = strtolower(trim((string) ($parts['host'] ?? '')));
    if ($scheme === '' || $host === '') {
        return '';
    }

    if (!in_array($scheme, ['https', 'http'], true)) {
        return '';
    }

    if (!in_array($host, referralAllowedTargetHosts(), true)) {
        return '';
    }

    if ($scheme !== 'https' && !in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
        return '';
    }

    return $target;
}

function referralTargetUrlIsAllowed(string $target): bool
{
    return referralValidatedTargetUrl($target) !== '';
}

function referralBuildUrlWithQuery(string $url, array $params): string
{
    if ($url === '') {
        return '';
    }

    $parts = parse_url($url);
    if (!is_array($parts)) {
        return $url;
    }

    $existingQuery = [];
    if (!empty($parts['query'])) {
        parse_str((string) $parts['query'], $existingQuery);
        if (!is_array($existingQuery)) {
            $existingQuery = [];
        }
    }

    $merged = array_merge($existingQuery, $params);
    $query = http_build_query($merged);

    $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
    $host = $parts['host'] ?? '';
    $port = isset($parts['port']) ? ':' . $parts['port'] : '';
    $user = $parts['user'] ?? '';
    $pass = isset($parts['pass']) ? ':' . $parts['pass'] : '';
    $auth = $user !== '' ? $user . $pass . '@' : '';
    $path = $parts['path'] ?? '';
    $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

    return $scheme . $auth . $host . $port . $path . ($query !== '' ? '?' . $query : '') . $fragment;
}

function referralCampaignIsCurrentlyActive(array $campaign): bool
{
    if (($campaign['status'] ?? '') !== 'active') {
        return false;
    }

    $targetUrl = referralValidatedTargetUrl((string) ($campaign['target_url'] ?? ''));
    if ($targetUrl === '') {
        return false;
    }

    $now = referralNow();
    $activeFrom = trim((string) ($campaign['active_from'] ?? ''));
    $activeTo = trim((string) ($campaign['active_to'] ?? ''));

    if ($activeFrom !== '' && $activeFrom > $now) {
        return false;
    }

    if ($activeTo !== '' && $activeTo < $now) {
        return false;
    }

    return true;
}

function referralGetCampaignBySlug(string $slug, bool $activeOnly = true): ?array
{
    $slug = trim($slug);
    if ($slug === '') {
        return null;
    }

    $row = queryOne(
        'SELECT
            c.*, p.slug AS provider_slug, p.name AS provider_name,
            p.default_disclosure_en, p.default_disclosure_es
         FROM referral_campaigns c
         INNER JOIN referral_providers p ON p.id = c.provider_id
         WHERE c.slug = :slug
         LIMIT 1',
        [':slug' => $slug]
    );

    if (!$row) {
        return null;
    }

    if ($activeOnly && !referralCampaignIsCurrentlyActive($row)) {
        return null;
    }

    return $row;
}

function referralGetCampaignById(string $id, bool $activeOnly = true): ?array
{
    $id = trim($id);
    if ($id === '') {
        return null;
    }

    $row = queryOne(
        'SELECT
            c.*, p.slug AS provider_slug, p.name AS provider_name,
            p.default_disclosure_en, p.default_disclosure_es
         FROM referral_campaigns c
         INNER JOIN referral_providers p ON p.id = c.provider_id
         WHERE c.id = :id
         LIMIT 1',
        [':id' => $id]
    );

    if (!$row) {
        return null;
    }

    if ($activeOnly && !referralCampaignIsCurrentlyActive($row)) {
        return null;
    }

    return $row;
}

function referralBuildTargetUrl(array $campaign, array $extraParams = []): string
{
    $target = referralValidatedTargetUrl((string) ($campaign['target_url'] ?? ''));
    if ($target === '') {
        return '';
    }

    $utmParams = referralJsonDecode((string) ($campaign['utm_json'] ?? ''), []);
    $query = [];

    // Only allow recognized UTM parameter names from campaign config
    $allowedUtmKeys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'utm_id'];

    foreach ($utmParams as $key => $value) {
        if (!is_string($key) || $key === '') {
            continue;
        }
        if (!is_scalar($value)) {
            continue;
        }
        // Restrict to known UTM keys to prevent arbitrary query param injection
        if (!in_array($key, $allowedUtmKeys, true)) {
            continue;
        }
        // Strip control characters and limit length
        $sanitized = preg_replace('/[\x00-\x1f\x7f]/', '', (string) $value);
        $query[$key] = mb_substr($sanitized, 0, 200);
    }

    foreach ($extraParams as $key => $value) {
        if (!is_string($key) || $key === '') {
            continue;
        }
        if (!is_scalar($value)) {
            continue;
        }
        $sanitized = preg_replace('/[\x00-\x1f\x7f]/', '', (string) $value);
        $query[$key] = mb_substr($sanitized, 0, 200);
    }

    if ($query === []) {
        return $target;
    }

    $built = referralBuildUrlWithQuery($target, $query);
    return referralValidatedTargetUrl($built);
}

function referralCreateGoUrl(string $campaignSlug, array $context = []): string
{
    $query = ['c' => $campaignSlug];

    $allowed = ['page_type', 'page_slug', 'placement', 'locale', 'block_slug'];
    foreach ($allowed as $key) {
        if (!isset($context[$key])) {
            continue;
        }

        $value = trim((string) $context[$key]);
        if ($value === '') {
            continue;
        }

        $query[$key] = $value;
    }

    return '/go?' . http_build_query($query);
}

function referralHashValue(string $value): string
{
    return hash('sha256', $value);
}

function referralCurrentAnonId(): string
{
    $anon = trim((string) ($_COOKIE['BF_ANON_ID'] ?? ''));
    return $anon;
}

function referralCurrentUserId(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return '';
    }

    $userId = trim((string) ($_SESSION['user_id'] ?? ''));
    return $userId;
}

function referralNormalizeContext(array $context): array
{
    $normalized = [];
    $allowed = ['page_type', 'page_slug', 'placement', 'locale', 'block_slug'];

    foreach ($allowed as $key) {
        if (!array_key_exists($key, $context)) {
            continue;
        }
        $value = trim((string) $context[$key]);
        if ($value === '') {
            continue;
        }
        $normalized[$key] = $value;
    }

    return $normalized;
}

function referralLogClick(array $campaign, array $context = []): string
{
    $clickId = uuid();
    $providerId = (string) ($campaign['provider_id'] ?? '');
    $campaignId = (string) ($campaign['id'] ?? '');

    $context = referralNormalizeContext($context);

    $pageType = (string) ($context['page_type'] ?? '');
    $pageSlug = (string) ($context['page_slug'] ?? '');
    $placement = (string) ($context['placement'] ?? '');
    $locale = referralNormalizeLocale($context['locale'] ?? 'en');
    $blockSlug = (string) ($context['block_slug'] ?? '');

    $blockId = '';
    if ($blockSlug !== '') {
        $block = queryOne('SELECT id FROM referral_blocks WHERE slug = :slug LIMIT 1', [':slug' => $blockSlug]);
        $blockId = (string) ($block['id'] ?? '');
    }

    $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    $ua = trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    $referrer = trim((string) ($_SERVER['HTTP_REFERER'] ?? ''));

    execute(
        'INSERT INTO referral_clicks
            (id, provider_id, campaign_id, block_id, page_type, page_slug, placement_key, locale, anon_id, user_id, ip_hash, ua_hash, referrer, meta_json, clicked_at)
         VALUES
            (:id, :provider_id, :campaign_id, :block_id, :page_type, :page_slug, :placement_key, :locale, :anon_id, :user_id, :ip_hash, :ua_hash, :referrer, :meta_json, CURRENT_TIMESTAMP)',
        [
            ':id' => $clickId,
            ':provider_id' => $providerId,
            ':campaign_id' => $campaignId,
            ':block_id' => $blockId !== '' ? $blockId : null,
            ':page_type' => $pageType,
            ':page_slug' => $pageSlug,
            ':placement_key' => $placement,
            ':locale' => $locale,
            ':anon_id' => referralCurrentAnonId(),
            ':user_id' => referralCurrentUserId() !== '' ? referralCurrentUserId() : null,
            ':ip_hash' => $ip !== '' ? referralHashValue($ip) : null,
            ':ua_hash' => $ua !== '' ? referralHashValue($ua) : null,
            ':referrer' => $referrer !== '' ? $referrer : null,
            ':meta_json' => json_encode($context, JSON_UNESCAPED_SLASHES),
        ]
    );

    return $clickId;
}

function referralResolveRedirect(string $campaignSlug, array $context = []): array
{
    $campaign = referralGetCampaignBySlug($campaignSlug, true);
    if (!$campaign) {
        return [
            'ok' => false,
            'status' => 404,
            'message' => 'Campaign not found',
        ];
    }

    $clickId = referralLogClick($campaign, $context);
    $target = referralBuildTargetUrl($campaign, ['bf_click_id' => $clickId]);

    if ($target === '') {
        return [
            'ok' => false,
            'status' => 422,
            'message' => 'Campaign has no target URL',
        ];
    }

    return [
        'ok' => true,
        'status' => 302,
        'target_url' => $target,
        'click_id' => $clickId,
        'campaign' => $campaign,
    ];
}

function referralDisclosureText(string $locale, ?array $provider = null): string
{
    $locale = referralNormalizeLocale($locale);

    if ($provider) {
        $key = $locale === 'es' ? 'default_disclosure_es' : 'default_disclosure_en';
        $value = trim((string) ($provider[$key] ?? ''));
        if ($value !== '') {
            return $value;
        }
    }

    if ($locale === 'es') {
        return 'Este contenido incluye enlaces de referidos. Podemos recibir una comision sin costo adicional para ti.';
    }

    return 'This content includes referral links. We may earn a commission at no extra cost to you.';
}

function referralRenderCampaignCta(array $campaign, string $label, array $context = [], string $style = 'primary'): string
{
    $slug = trim((string) ($campaign['slug'] ?? ''));
    if ($slug === '') {
        return '';
    }

    $locale = referralNormalizeLocale($context['locale'] ?? 'en');
    $label = trim($label);
    if ($label === '') {
        $label = (string) ($campaign['name'] ?? 'View offer');
    }

    $url = referralCreateGoUrl($slug, $context);

    $classes = [
        'primary' => 'inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2.5 rounded-lg transition-colors text-sm',
        'secondary' => 'inline-flex items-center gap-2 bg-white/10 hover:bg-white/20 text-white font-semibold px-4 py-2.5 rounded-lg border border-white/20 transition-colors text-sm',
        'outline' => 'inline-flex items-center gap-2 border border-blue-600 text-blue-700 hover:bg-blue-50 font-semibold px-4 py-2.5 rounded-lg transition-colors text-sm',
        'yellow' => 'inline-flex items-center gap-2 bg-yellow-400 hover:bg-yellow-300 text-gray-900 font-semibold px-4 py-2.5 rounded-lg transition-colors text-sm',
    ];

    $class = $classes[$style] ?? $classes['primary'];

    $attrs = [
        'href' => $url,
        'class' => $class,
        'target' => '_blank',
        'rel' => 'nofollow sponsored noopener',
        'data-bf-track' => 'referral-click',
        'data-bf-referral-provider' => (string) ($campaign['provider_slug'] ?? ''),
        'data-bf-referral-campaign' => $slug,
        'data-bf-referral-placement' => (string) ($context['placement'] ?? ''),
        'data-bf-referral-page-type' => (string) ($context['page_type'] ?? ''),
        'data-bf-referral-page-slug' => (string) ($context['page_slug'] ?? ''),
        'data-bf-referral-locale' => $locale,
    ];

    if (!empty($context['block_slug'])) {
        $attrs['data-bf-referral-block'] = (string) $context['block_slug'];
    }

    $attrParts = [];
    foreach ($attrs as $key => $value) {
        if ($value === '') {
            continue;
        }
        $attrParts[] = $key . '="' . h((string) $value) . '"';
    }

    return '<a ' . implode(' ', $attrParts) . '>' . h($label) . ' &rarr;</a>';
}

function referralRenderCampaignCard(array $campaign, array $opts = []): string
{
    $locale = referralNormalizeLocale($opts['locale'] ?? 'en');
    $title = trim((string) ($opts['title'] ?? $campaign['name'] ?? 'Travel Offer'));
    $body = trim((string) ($opts['description'] ?? ''));
    $buttonLabel = trim((string) ($opts['button_label'] ?? ($locale === 'es' ? 'Ver oferta' : 'View offer')));

    $context = [
        'page_type' => (string) ($opts['page_type'] ?? ''),
        'page_slug' => (string) ($opts['page_slug'] ?? ''),
        'placement' => (string) ($opts['placement'] ?? ''),
        'locale' => $locale,
        'block_slug' => (string) ($opts['block_slug'] ?? ''),
    ];

    $cta = referralRenderCampaignCta($campaign, $buttonLabel, $context, (string) ($opts['button_style'] ?? 'primary'));
    if ($cta === '') {
        return '';
    }

    $providerName = trim((string) ($campaign['provider_name'] ?? $campaign['provider_slug'] ?? 'Partner'));
    $providerTag = $providerName !== '' ? '<span class="text-xs font-semibold uppercase tracking-wide text-blue-700">' . h($providerName) . '</span>' : '';

    $out = '<section class="referral-card rounded-xl border border-blue-200 bg-blue-50/80 p-4 md:p-5"'
        . ' data-bf-track="referral-impression"'
        . ' data-bf-referral-provider="' . h((string) ($campaign['provider_slug'] ?? '')) . '"'
        . ' data-bf-referral-campaign="' . h((string) ($campaign['slug'] ?? '')) . '"'
        . ' data-bf-referral-placement="' . h((string) ($opts['placement'] ?? '')) . '"'
        . ' data-bf-referral-page-type="' . h((string) ($opts['page_type'] ?? '')) . '"'
        . ' data-bf-referral-page-slug="' . h((string) ($opts['page_slug'] ?? '')) . '"'
        . ' data-bf-referral-locale="' . h($locale) . '">';
    $out .= '<div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">';
    $out .= '<div class="min-w-0">' . $providerTag . '<h3 class="text-lg font-bold text-gray-900 mt-1">' . h($title) . '</h3>';
    if ($body !== '') {
        $out .= '<p class="text-sm text-gray-700 mt-1">' . h($body) . '</p>';
    }
    $out .= '</div>';
    $out .= '<div class="shrink-0">' . $cta . '</div>';
    $out .= '</div>';
    $out .= '</section>';

    return $out;
}

function referralGetBeachPlacements(string $beachId, string $anchorKey, string $locale = 'en'): array
{
    $beachId = trim($beachId);
    $anchorKey = trim($anchorKey);
    $locale = referralNormalizeLocale($locale);

    if ($beachId === '' || $anchorKey === '') {
        return [];
    }

    $rows = query(
        'SELECT
            p.id AS placement_id,
            p.anchor_key,
            p.locale AS placement_locale,
            p.display_order,
            p.enabled,
            b.slug AS block_slug,
            b.block_type,
            b.label_en AS block_label_en,
            b.label_es AS block_label_es,
            b.style_variant,
            b.metadata_json,
            c.*, pr.slug AS provider_slug, pr.name AS provider_name,
            pr.default_disclosure_en, pr.default_disclosure_es
         FROM beach_referral_placements p
         INNER JOIN referral_campaigns c ON c.id = p.campaign_id
         INNER JOIN referral_providers pr ON pr.id = c.provider_id
         LEFT JOIN referral_blocks b ON b.id = p.block_id
         WHERE p.beach_id = :beach_id
           AND p.anchor_key = :anchor_key
           AND p.enabled = 1
           AND (p.locale = "all" OR p.locale = :locale)
         ORDER BY p.display_order ASC, c.priority ASC',
        [
            ':beach_id' => $beachId,
            ':anchor_key' => $anchorKey,
            ':locale' => $locale,
        ]
    );

    if (!is_array($rows)) {
        return [];
    }

    $activeRows = [];
    foreach ($rows as $row) {
        if (referralCampaignIsCurrentlyActive($row)) {
            $activeRows[] = $row;
        }
    }

    return $activeRows;
}

function referralRenderBeachAnchor(string $beachId, string $anchorKey, string $locale = 'en', array $context = []): string
{
    $placements = referralGetBeachPlacements($beachId, $anchorKey, $locale);
    if ($placements === []) {
        return '';
    }

    $pageSlug = (string) ($context['page_slug'] ?? '');
    $pageType = (string) ($context['page_type'] ?? 'beach');

    $cards = [];
    foreach ($placements as $placement) {
        $blockLabel = $locale === 'es'
            ? trim((string) ($placement['block_label_es'] ?? ''))
            : trim((string) ($placement['block_label_en'] ?? ''));

        $cards[] = referralRenderCampaignCard(
            $placement,
            [
                'locale' => $locale,
                'title' => $blockLabel !== '' ? $blockLabel : (string) ($placement['name'] ?? ''),
                'description' => '',
                'button_label' => $locale === 'es' ? 'Ir a oferta' : 'View offer',
                'button_style' => (string) (($placement['style_variant'] ?? 'card') === 'button' ? 'outline' : 'primary'),
                'page_type' => $pageType,
                'page_slug' => $pageSlug,
                'placement' => $anchorKey,
                'block_slug' => (string) ($placement['block_slug'] ?? ''),
            ]
        );
    }

    $cards = array_values(array_filter($cards));
    if ($cards === []) {
        return '';
    }

    $disclosure = referralDisclosureText($locale, $placements[0]);

    $headingMap = [
        'hero' => ['en' => 'Plan This Trip', 'es' => 'Planifica Este Viaje'],
        'mid_content' => ['en' => 'Popular Booking Options', 'es' => 'Opciones Populares'],
        'sidebar' => ['en' => 'Travel Deals', 'es' => 'Ofertas de Viaje'],
        'bottom' => ['en' => 'Ready to Book?', 'es' => 'Listo para Reservar?'],
        'planning' => ['en' => 'Trip Planning', 'es' => 'Planificacion'],
    ];

    $heading = $headingMap[$anchorKey][$locale] ?? ($locale === 'es' ? 'Opciones de viaje' : 'Travel options');

    $out = '<section class="referral-anchor space-y-3">';
    $out .= '<h3 class="text-base font-semibold text-blue-900">' . h($heading) . '</h3>';
    $out .= implode('', $cards);
    $out .= '<p class="text-xs text-gray-500 italic">' . h($disclosure) . '</p>';
    $out .= '</section>';

    return $out;
}

function referralCampaignOptions(bool $activeOnly = false): array
{
    $sql = 'SELECT c.id, c.slug, c.name, c.status, c.target_url, c.priority, c.link_type, c.destination_scope,
                   p.slug AS provider_slug, p.name AS provider_name
            FROM referral_campaigns c
            INNER JOIN referral_providers p ON p.id = c.provider_id';

    if ($activeOnly) {
        $sql .= ' WHERE c.status = "active"';
    }

    $sql .= ' ORDER BY p.name ASC, c.priority ASC, c.name ASC';

    $rows = query($sql);
    return is_array($rows) ? $rows : [];
}

function referralProviderOptions(): array
{
    $rows = query('SELECT * FROM referral_providers ORDER BY name ASC');
    return is_array($rows) ? $rows : [];
}

function referralBlockOptions(bool $activeOnly = false): array
{
    $sql = 'SELECT * FROM referral_blocks';
    if ($activeOnly) {
        $sql .= ' WHERE status = "active"';
    }
    $sql .= ' ORDER BY slug ASC';

    $rows = query($sql);
    return is_array($rows) ? $rows : [];
}
