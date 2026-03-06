<?php
/**
 * Referral + guide CMS edge-case test suite.
 *
 * Usage: php scripts/test-referrals-edge-cases.php
 */

require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/referrals.php';
require_once APP_ROOT . '/inc/guide_cms.php';

$failures = [];
$checks = 0;

$cleanup = [
    'guide_blocks' => [],
    'guide_articles' => [],
    'beach_referral_placements' => [],
    'referral_conversions' => [],
    'referral_clicks' => [],
    'referral_campaigns' => [],
    'referral_blocks' => [],
];

function assertTrue(bool $condition, string $message, array &$failures, int &$checks): void
{
    $checks++;
    if (!$condition) {
        $failures[] = $message;
    }
}

function remember(array &$cleanup, string $table, string $id): void
{
    if ($id === '') {
        return;
    }
    $cleanup[$table][] = $id;
}

function cleanupTestRows(array $cleanup): void
{
    foreach ($cleanup['guide_blocks'] as $id) {
        execute('DELETE FROM guide_blocks WHERE id = :id', [':id' => $id]);
    }
    foreach ($cleanup['guide_articles'] as $id) {
        execute('DELETE FROM guide_articles WHERE id = :id', [':id' => $id]);
    }
    foreach ($cleanup['beach_referral_placements'] as $id) {
        execute('DELETE FROM beach_referral_placements WHERE id = :id', [':id' => $id]);
    }
    foreach ($cleanup['referral_conversions'] as $id) {
        execute('DELETE FROM referral_conversions WHERE id = :id', [':id' => $id]);
    }
    foreach ($cleanup['referral_clicks'] as $id) {
        execute('DELETE FROM referral_clicks WHERE id = :id', [':id' => $id]);
    }
    foreach ($cleanup['referral_campaigns'] as $id) {
        execute('DELETE FROM referral_campaigns WHERE id = :id', [':id' => $id]);
    }
    foreach ($cleanup['referral_blocks'] as $id) {
        execute('DELETE FROM referral_blocks WHERE id = :id', [':id' => $id]);
    }
}

function runGoPhp(array $query): array
{
    $snippet = '$_SERVER["DOCUMENT_ROOT"] = ' . var_export(PUBLIC_ROOT, true) . ';'
        . '$_GET = ' . var_export($query, true) . ';'
        . 'require ' . var_export(PUBLIC_ROOT . '/go.php', true) . ';';

    $output = [];
    $exitCode = 0;
    exec('php -r ' . escapeshellarg($snippet) . ' 2>&1', $output, $exitCode);

    return [
        'exit_code' => $exitCode,
        'output' => implode("\n", $output),
    ];
}

try {
    $provider = queryOne('SELECT id FROM referral_providers WHERE slug = :slug', [':slug' => 'expedia']);
    assertTrue(is_array($provider) && !empty($provider['id']), 'Expected seeded expedia provider', $failures, $checks);

    $beach = queryOne('SELECT id, slug FROM beaches WHERE publish_status = "published" LIMIT 1');
    assertTrue(is_array($beach) && !empty($beach['id']) && !empty($beach['slug']), 'Expected at least one published beach', $failures, $checks);

    $providerId = (string) ($provider['id'] ?? '');
    $beachId = (string) ($beach['id'] ?? '');
    $beachSlug = (string) ($beach['slug'] ?? '');

    $suffix = substr(str_replace('-', '', uuid()), 0, 10);

    $activeCampaignId = uuid();
    $activeCampaignSlug = 'qa-edge-active-' . $suffix;
    execute(
        'INSERT INTO referral_campaigns
            (id, provider_id, slug, name, link_type, destination_scope, target_url, utm_json, priority, status, created_at, updated_at)
         VALUES
            (:id, :provider_id, :slug, :name, :link_type, :destination_scope, :target_url, :utm_json, :priority, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
        [
            ':id' => $activeCampaignId,
            ':provider_id' => $providerId,
            ':slug' => $activeCampaignSlug,
            ':name' => 'QA Active Campaign',
            ':link_type' => 'hotel',
            ':destination_scope' => 'global',
            ':target_url' => 'https://example.com/search?x=1',
            ':utm_json' => json_encode(['utm_source' => 'bf', 'x' => '2'], JSON_UNESCAPED_SLASHES),
            ':priority' => 10,
            ':status' => 'active',
        ]
    );
    remember($cleanup, 'referral_campaigns', $activeCampaignId);

    $draftCampaignId = uuid();
    $draftCampaignSlug = 'qa-edge-draft-' . $suffix;
    execute(
        'INSERT INTO referral_campaigns
            (id, provider_id, slug, name, link_type, destination_scope, target_url, utm_json, priority, status, created_at, updated_at)
         VALUES
            (:id, :provider_id, :slug, :name, :link_type, :destination_scope, :target_url, :utm_json, :priority, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
        [
            ':id' => $draftCampaignId,
            ':provider_id' => $providerId,
            ':slug' => $draftCampaignSlug,
            ':name' => 'QA Draft Campaign',
            ':link_type' => 'hotel',
            ':destination_scope' => 'global',
            ':target_url' => 'https://example.com/draft',
            ':utm_json' => '{}',
            ':priority' => 20,
            ':status' => 'draft',
        ]
    );
    remember($cleanup, 'referral_campaigns', $draftCampaignId);

    $futureCampaignId = uuid();
    $futureCampaignSlug = 'qa-edge-future-' . $suffix;
    execute(
        'INSERT INTO referral_campaigns
            (id, provider_id, slug, name, link_type, destination_scope, target_url, utm_json, priority, active_from, status, created_at, updated_at)
         VALUES
            (:id, :provider_id, :slug, :name, :link_type, :destination_scope, :target_url, :utm_json, :priority, datetime("now", "+2 day"), :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
        [
            ':id' => $futureCampaignId,
            ':provider_id' => $providerId,
            ':slug' => $futureCampaignSlug,
            ':name' => 'QA Future Campaign',
            ':link_type' => 'hotel',
            ':destination_scope' => 'global',
            ':target_url' => 'https://example.com/future',
            ':utm_json' => '{}',
            ':priority' => 30,
            ':status' => 'active',
        ]
    );
    remember($cleanup, 'referral_campaigns', $futureCampaignId);

    $pastCampaignId = uuid();
    $pastCampaignSlug = 'qa-edge-past-' . $suffix;
    execute(
        'INSERT INTO referral_campaigns
            (id, provider_id, slug, name, link_type, destination_scope, target_url, utm_json, priority, active_to, status, created_at, updated_at)
         VALUES
            (:id, :provider_id, :slug, :name, :link_type, :destination_scope, :target_url, :utm_json, :priority, datetime("now", "-2 day"), :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
        [
            ':id' => $pastCampaignId,
            ':provider_id' => $providerId,
            ':slug' => $pastCampaignSlug,
            ':name' => 'QA Past Campaign',
            ':link_type' => 'hotel',
            ':destination_scope' => 'global',
            ':target_url' => 'https://example.com/past',
            ':utm_json' => '{}',
            ':priority' => 40,
            ':status' => 'active',
        ]
    );
    remember($cleanup, 'referral_campaigns', $pastCampaignId);

    $noUrlCampaignId = uuid();
    $noUrlCampaignSlug = 'qa-edge-nourl-' . $suffix;
    execute(
        'INSERT INTO referral_campaigns
            (id, provider_id, slug, name, link_type, destination_scope, target_url, utm_json, priority, status, created_at, updated_at)
         VALUES
            (:id, :provider_id, :slug, :name, :link_type, :destination_scope, :target_url, :utm_json, :priority, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
        [
            ':id' => $noUrlCampaignId,
            ':provider_id' => $providerId,
            ':slug' => $noUrlCampaignSlug,
            ':name' => 'QA No URL Campaign',
            ':link_type' => 'hotel',
            ':destination_scope' => 'global',
            ':target_url' => '',
            ':utm_json' => '{}',
            ':priority' => 50,
            ':status' => 'active',
        ]
    );
    remember($cleanup, 'referral_campaigns', $noUrlCampaignId);

    $blockId = uuid();
    $blockSlug = 'qa-edge-block-' . $suffix;
    execute(
        'INSERT INTO referral_blocks
            (id, slug, block_type, label_en, label_es, style_variant, disclosure_mode, metadata_json, status, created_at, updated_at)
         VALUES
            (:id, :slug, :block_type, :label_en, :label_es, :style_variant, :disclosure_mode, :metadata_json, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
        [
            ':id' => $blockId,
            ':slug' => $blockSlug,
            ':block_type' => 'inline_card',
            ':label_en' => 'QA Block',
            ':label_es' => 'Bloque QA',
            ':style_variant' => 'card',
            ':disclosure_mode' => 'group',
            ':metadata_json' => '{}',
            ':status' => 'active',
        ]
    );
    remember($cleanup, 'referral_blocks', $blockId);

    assertTrue(referralGetCampaignBySlug('qa-edge-missing-' . $suffix, true) === null, 'Unknown campaign should return null', $failures, $checks);
    assertTrue(referralGetCampaignBySlug($activeCampaignSlug, true) !== null, 'Active campaign should resolve', $failures, $checks);
    assertTrue(referralGetCampaignBySlug($draftCampaignSlug, true) === null, 'Draft campaign should not resolve when activeOnly=true', $failures, $checks);
    assertTrue(referralGetCampaignBySlug($draftCampaignSlug, false) !== null, 'Draft campaign should resolve when activeOnly=false', $failures, $checks);
    assertTrue(referralGetCampaignBySlug($futureCampaignSlug, true) === null, 'Future campaign should not resolve yet', $failures, $checks);
    assertTrue(referralGetCampaignBySlug($pastCampaignSlug, true) === null, 'Expired campaign should not resolve', $failures, $checks);
    assertTrue(referralGetCampaignBySlug($noUrlCampaignSlug, true) === null, 'Campaign without target URL should not resolve active', $failures, $checks);

    $activeCampaign = referralGetCampaignBySlug($activeCampaignSlug, true);
    $targetUrl = referralBuildTargetUrl((array) $activeCampaign, ['bf_click_id' => 'edge-test']);
    $parts = parse_url($targetUrl);
    parse_str((string) ($parts['query'] ?? ''), $query);
    assertTrue(($parts['host'] ?? '') === 'example.com', 'Target URL host mismatch', $failures, $checks);
    assertTrue(($query['utm_source'] ?? '') === 'bf', 'UTM source should be appended', $failures, $checks);
    assertTrue(($query['x'] ?? '') === '2', 'UTM values should override existing query when keys collide', $failures, $checks);
    assertTrue(($query['bf_click_id'] ?? '') === 'edge-test', 'Extra params should be appended', $failures, $checks);

    $resolveMissing = referralResolveRedirect('qa-edge-missing-' . $suffix, []);
    assertTrue(($resolveMissing['ok'] ?? false) === false && (int) ($resolveMissing['status'] ?? 0) === 404, 'Missing campaign should return 404 response object', $failures, $checks);

    $resolveActive = referralResolveRedirect($activeCampaignSlug, [
        'page_type' => 'guide',
        'page_slug' => 'qa-edge-guide',
        'placement' => 'hero',
        'locale' => 'en',
    ]);
    assertTrue(($resolveActive['ok'] ?? false) === true, 'Active campaign should resolve for redirect', $failures, $checks);
    assertTrue((int) ($resolveActive['status'] ?? 0) === 302, 'Active campaign should produce 302 status', $failures, $checks);

    $redirectTarget = (string) ($resolveActive['target_url'] ?? '');
    $clickIdFromResolve = (string) ($resolveActive['click_id'] ?? '');
    assertTrue($redirectTarget !== '' && strpos($redirectTarget, 'bf_click_id=') !== false, 'Redirect target should include bf_click_id', $failures, $checks);
    assertTrue($clickIdFromResolve !== '', 'resolveRedirect should return click id', $failures, $checks);

    $clickRow = queryOne('SELECT * FROM referral_clicks WHERE id = :id', [':id' => $clickIdFromResolve]);
    assertTrue(is_array($clickRow) && !empty($clickRow['id']), 'resolveRedirect should persist click row', $failures, $checks);
    remember($cleanup, 'referral_clicks', $clickIdFromResolve);

    $clickIdWithBlock = referralLogClick((array) $activeCampaign, [
        'page_type' => 'guide',
        'page_slug' => 'qa-edge-guide',
        'placement' => 'inline_block',
        'locale' => 'en',
        'block_slug' => $blockSlug,
    ]);
    assertTrue($clickIdWithBlock !== '', 'referralLogClick should return click id', $failures, $checks);
    $clickBlockRow = queryOne('SELECT * FROM referral_clicks WHERE id = :id', [':id' => $clickIdWithBlock]);
    assertTrue((string) ($clickBlockRow['block_id'] ?? '') === $blockId, 'Click should resolve block_id from block_slug', $failures, $checks);
    assertTrue((string) ($clickBlockRow['locale'] ?? '') === 'en', 'Click locale should persist', $failures, $checks);
    remember($cleanup, 'referral_clicks', $clickIdWithBlock);

    $clickCountBeforeGo = (int) (queryOne(
        'SELECT COUNT(*) AS count_all FROM referral_clicks WHERE campaign_id = :campaign_id',
        [':campaign_id' => $activeCampaignId]
    )['count_all'] ?? 0);

    $goMissing = runGoPhp([]);
    assertTrue(strpos($goMissing['output'], 'Missing referral campaign.') !== false, 'go.php should return missing-campaign message for empty c', $failures, $checks);

    $clickCountAfterMissingGo = (int) (queryOne(
        'SELECT COUNT(*) AS count_all FROM referral_clicks WHERE campaign_id = :campaign_id',
        [':campaign_id' => $activeCampaignId]
    )['count_all'] ?? 0);
    assertTrue($clickCountAfterMissingGo === $clickCountBeforeGo, 'go.php missing-campaign request should not create clicks', $failures, $checks);

    $goUnknown = runGoPhp(['c' => 'qa-edge-missing-' . $suffix]);
    assertTrue(stripos($goUnknown['output'], 'not found') !== false, 'go.php unknown campaign should return not-found message', $failures, $checks);

    $clickCountAfterUnknownGo = (int) (queryOne(
        'SELECT COUNT(*) AS count_all FROM referral_clicks WHERE campaign_id = :campaign_id',
        [':campaign_id' => $activeCampaignId]
    )['count_all'] ?? 0);
    assertTrue($clickCountAfterUnknownGo === $clickCountBeforeGo, 'go.php unknown-campaign request should not create clicks', $failures, $checks);

    $goPageSlug = 'qa-go-endpoint-' . $suffix;
    $goOk = runGoPhp([
        'c' => $activeCampaignSlug,
        'page_type' => 'guide',
        'page_slug' => $goPageSlug,
        'placement' => 'hero',
        'locale' => 'es',
        'block_slug' => $blockSlug,
    ]);
    assertTrue($goOk['exit_code'] === 0, 'go.php valid request should exit cleanly', $failures, $checks);
    assertTrue(trim($goOk['output']) === '', 'go.php valid request should not render body output', $failures, $checks);

    $clickCountAfterGo = (int) (queryOne(
        'SELECT COUNT(*) AS count_all FROM referral_clicks WHERE campaign_id = :campaign_id',
        [':campaign_id' => $activeCampaignId]
    )['count_all'] ?? 0);
    assertTrue($clickCountAfterGo === ($clickCountBeforeGo + 1), 'go.php valid request should create exactly one click row', $failures, $checks);

    $goClickRow = queryOne(
        'SELECT * FROM referral_clicks
         WHERE campaign_id = :campaign_id AND page_slug = :page_slug
         ORDER BY clicked_at DESC
         LIMIT 1',
        [':campaign_id' => $activeCampaignId, ':page_slug' => $goPageSlug]
    );
    assertTrue(is_array($goClickRow) && !empty($goClickRow['id']), 'go.php valid request should persist click row with page slug', $failures, $checks);
    assertTrue((string) ($goClickRow['page_type'] ?? '') === 'guide', 'go.php valid click should persist page_type', $failures, $checks);
    assertTrue((string) ($goClickRow['placement_key'] ?? '') === 'hero', 'go.php valid click should persist placement', $failures, $checks);
    assertTrue((string) ($goClickRow['locale'] ?? '') === 'es', 'go.php valid click should persist locale', $failures, $checks);
    assertTrue((string) ($goClickRow['block_id'] ?? '') === $blockId, 'go.php valid click should resolve block_id from block_slug', $failures, $checks);
    remember($cleanup, 'referral_clicks', (string) ($goClickRow['id'] ?? ''));

    $goNormalizedPageSlug = 'qa-go-locale-normalized-' . $suffix;
    $goNormalized = runGoPhp([
        'c' => $activeCampaignSlug,
        'page_type' => 'guide',
        'page_slug' => $goNormalizedPageSlug,
        'placement' => 'hero',
        'locale' => 'fr',
    ]);
    assertTrue($goNormalized['exit_code'] === 0, 'go.php unsupported locale request should exit cleanly', $failures, $checks);

    $goNormalizedRow = queryOne(
        'SELECT * FROM referral_clicks
         WHERE campaign_id = :campaign_id AND page_slug = :page_slug
         LIMIT 1',
        [':campaign_id' => $activeCampaignId, ':page_slug' => $goNormalizedPageSlug]
    );
    assertTrue(is_array($goNormalizedRow) && !empty($goNormalizedRow['id']), 'go.php unsupported-locale request should persist click row', $failures, $checks);
    assertTrue((string) ($goNormalizedRow['locale'] ?? '') === 'en', 'go.php should normalize unsupported locale to en', $failures, $checks);
    remember($cleanup, 'referral_clicks', (string) ($goNormalizedRow['id'] ?? ''));

    $placementAnchor = 'qa_anchor_' . $suffix;
    $placementEnId = uuid();
    $placementAllId = uuid();
    $placementEsId = uuid();
    $placementDisabledId = uuid();

    execute(
        'INSERT INTO beach_referral_placements (id, beach_id, anchor_key, campaign_id, block_id, locale, enabled, display_order, created_at, updated_at)
         VALUES (:id, :beach_id, :anchor_key, :campaign_id, :block_id, :locale, :enabled, :display_order, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
        [':id' => $placementEnId, ':beach_id' => $beachId, ':anchor_key' => $placementAnchor, ':campaign_id' => $activeCampaignId, ':block_id' => $blockId, ':locale' => 'en', ':enabled' => 1, ':display_order' => 1]
    );
    remember($cleanup, 'beach_referral_placements', $placementEnId);

    execute(
        'INSERT INTO beach_referral_placements (id, beach_id, anchor_key, campaign_id, block_id, locale, enabled, display_order, created_at, updated_at)
         VALUES (:id, :beach_id, :anchor_key, :campaign_id, :block_id, :locale, :enabled, :display_order, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
        [':id' => $placementAllId, ':beach_id' => $beachId, ':anchor_key' => $placementAnchor, ':campaign_id' => $activeCampaignId, ':block_id' => $blockId, ':locale' => 'all', ':enabled' => 1, ':display_order' => 2]
    );
    remember($cleanup, 'beach_referral_placements', $placementAllId);

    execute(
        'INSERT INTO beach_referral_placements (id, beach_id, anchor_key, campaign_id, block_id, locale, enabled, display_order, created_at, updated_at)
         VALUES (:id, :beach_id, :anchor_key, :campaign_id, :block_id, :locale, :enabled, :display_order, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
        [':id' => $placementEsId, ':beach_id' => $beachId, ':anchor_key' => $placementAnchor, ':campaign_id' => $activeCampaignId, ':block_id' => $blockId, ':locale' => 'es', ':enabled' => 1, ':display_order' => 0]
    );
    remember($cleanup, 'beach_referral_placements', $placementEsId);

    execute(
        'INSERT INTO beach_referral_placements (id, beach_id, anchor_key, campaign_id, block_id, locale, enabled, display_order, created_at, updated_at)
         VALUES (:id, :beach_id, :anchor_key, :campaign_id, :block_id, :locale, :enabled, :display_order, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
        [':id' => $placementDisabledId, ':beach_id' => $beachId, ':anchor_key' => $placementAnchor, ':campaign_id' => $activeCampaignId, ':block_id' => $blockId, ':locale' => 'all', ':enabled' => 0, ':display_order' => 0]
    );
    remember($cleanup, 'beach_referral_placements', $placementDisabledId);

    $enPlacements = referralGetBeachPlacements($beachId, $placementAnchor, 'en');
    assertTrue(count($enPlacements) === 2, 'EN locale should include en + all placements only', $failures, $checks);
    assertTrue(((string) ($enPlacements[0]['placement_locale'] ?? '')) === 'en', 'EN placements should be sorted by display_order', $failures, $checks);
    assertTrue(((string) ($enPlacements[1]['placement_locale'] ?? '')) === 'all', 'EN placement second row should be locale=all', $failures, $checks);

    $esPlacements = referralGetBeachPlacements($beachId, $placementAnchor, 'es');
    assertTrue(count($esPlacements) === 2, 'ES locale should include es + all placements only', $failures, $checks);
    assertTrue(((string) ($esPlacements[0]['placement_locale'] ?? '')) === 'es', 'ES placements should be sorted by display_order', $failures, $checks);

    $anchorHtml = referralRenderBeachAnchor($beachId, $placementAnchor, 'en', [
        'page_type' => 'beach',
        'page_slug' => $beachSlug,
    ]);
    assertTrue($anchorHtml !== '', 'Rendered anchor HTML should not be empty when placements exist', $failures, $checks);
    assertTrue(strpos($anchorHtml, '/go?c=' . $activeCampaignSlug) !== false, 'Rendered anchor should include /go campaign link', $failures, $checks);

    $guideId = uuid();
    $guideSlug = 'qa-edge-guide-' . $suffix;
    execute(
        'INSERT INTO guide_articles
            (id, slug, title_en, title_es, description_en, description_es, status, published_at, created_at, updated_at)
         VALUES
            (:id, :slug, :title_en, :title_es, :description_en, :description_es, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
        [
            ':id' => $guideId,
            ':slug' => $guideSlug,
            ':title_en' => 'QA Edge Guide',
            ':title_es' => 'Guia QA',
            ':description_en' => 'Edge-case guide.',
            ':description_es' => 'Guia de pruebas.',
            ':status' => 'published',
        ]
    );
    remember($cleanup, 'guide_articles', $guideId);

    $guideBlockHeadingId = uuid();
    execute(
        'INSERT INTO guide_blocks (id, guide_id, block_order, block_kind, payload_json, status, created_at, updated_at)
         VALUES (:id, :guide_id, :block_order, :block_kind, :payload_json, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
        [
            ':id' => $guideBlockHeadingId,
            ':guide_id' => $guideId,
            ':block_order' => 0,
            ':block_kind' => 'heading',
            ':payload_json' => json_encode(['text_en' => 'QA Heading', 'text_es' => 'Encabezado QA', 'level' => 2], JSON_UNESCAPED_SLASHES),
            ':status' => 'published',
        ]
    );
    remember($cleanup, 'guide_blocks', $guideBlockHeadingId);

    $guideBlockRichId = uuid();
    execute(
        'INSERT INTO guide_blocks (id, guide_id, block_order, block_kind, payload_json, status, created_at, updated_at)
         VALUES (:id, :guide_id, :block_order, :block_kind, :payload_json, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
        [
            ':id' => $guideBlockRichId,
            ':guide_id' => $guideId,
            ':block_order' => 1,
            ':block_kind' => 'rich_text',
            ':payload_json' => json_encode(['html_en' => '<p>QA rich text.</p>', 'html_es' => '<p>Texto QA.</p>'], JSON_UNESCAPED_SLASHES),
            ':status' => 'published',
        ]
    );
    remember($cleanup, 'guide_blocks', $guideBlockRichId);

    $guideBlockRefId = uuid();
    execute(
        'INSERT INTO guide_blocks (id, guide_id, block_order, block_kind, payload_json, status, created_at, updated_at)
         VALUES (:id, :guide_id, :block_order, :block_kind, :payload_json, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
        [
            ':id' => $guideBlockRefId,
            ':guide_id' => $guideId,
            ':block_order' => 2,
            ':block_kind' => 'referral_block',
            ':payload_json' => json_encode([
                'campaign_slug' => $activeCampaignSlug,
                'title_en' => 'QA Referral Title',
                'button_label_en' => 'QA CTA',
            ], JSON_UNESCAPED_SLASHES),
            ':status' => 'published',
        ]
    );
    remember($cleanup, 'guide_blocks', $guideBlockRefId);

    $guideLoaded = guideCmsLoadArticleBySlug($guideSlug, true);
    assertTrue(is_array($guideLoaded) && (string) ($guideLoaded['id'] ?? '') === $guideId, 'guideCmsLoadArticleBySlug should load published guide', $failures, $checks);

    $blocksLoaded = guideCmsLoadBlocks($guideId, true);
    assertTrue(count($blocksLoaded) === 3, 'guideCmsLoadBlocks should load ordered published blocks', $failures, $checks);

    $renderedHeading = guideCmsRenderBlock($blocksLoaded[0], $guideLoaded, 'en');
    $renderedRich = guideCmsRenderBlock($blocksLoaded[1], $guideLoaded, 'en');
    $renderedReferral = guideCmsRenderBlock($blocksLoaded[2], $guideLoaded, 'en');
    assertTrue(strpos($renderedHeading, 'QA Heading') !== false, 'Heading block should render localized heading text', $failures, $checks);
    assertTrue(strpos($renderedRich, '<p>QA rich text.</p>') !== false, 'Rich text block should render HTML content', $failures, $checks);
    assertTrue(strpos($renderedReferral, '/go?c=' . $activeCampaignSlug) !== false, 'Referral block should render /go URL', $failures, $checks);

    $draftGuideId = uuid();
    $draftGuideSlug = 'qa-edge-guide-draft-' . $suffix;
    execute(
        'INSERT INTO guide_articles
            (id, slug, title_en, description_en, status, created_at, updated_at)
         VALUES
            (:id, :slug, :title_en, :description_en, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
        [
            ':id' => $draftGuideId,
            ':slug' => $draftGuideSlug,
            ':title_en' => 'QA Draft Guide',
            ':description_en' => 'Draft guide.',
            ':status' => 'draft',
        ]
    );
    remember($cleanup, 'guide_articles', $draftGuideId);

    assertTrue(guideCmsLoadArticleBySlug($draftGuideSlug, true) === null, 'Draft guide should not load when publishedOnly=true', $failures, $checks);
    assertTrue(guideCmsLoadArticleBySlug($draftGuideSlug, false) !== null, 'Draft guide should load when publishedOnly=false', $failures, $checks);

    $conversionId = uuid();
    $externalId = 'qa-edge-conv-' . $suffix;
    $okInsert = execute(
        'INSERT INTO referral_conversions
            (id, provider_id, campaign_id, external_conversion_id, booking_value, commission_value, currency, booked_at, imported_at, raw_json, created_at, updated_at)
         VALUES
            (:id, :provider_id, :campaign_id, :external_conversion_id, :booking_value, :commission_value, :currency, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, :raw_json, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
        [
            ':id' => $conversionId,
            ':provider_id' => $providerId,
            ':campaign_id' => $activeCampaignId,
            ':external_conversion_id' => $externalId,
            ':booking_value' => 100,
            ':commission_value' => 10,
            ':currency' => 'USD',
            ':raw_json' => '{}',
        ]
    );
    assertTrue($okInsert, 'Initial conversion insert should succeed', $failures, $checks);
    remember($cleanup, 'referral_conversions', $conversionId);

    $okUpsert = execute(
        'INSERT INTO referral_conversions
            (id, provider_id, campaign_id, external_conversion_id, booking_value, commission_value, currency, booked_at, imported_at, raw_json, created_at, updated_at)
         VALUES
            (:id, :provider_id, :campaign_id, :external_conversion_id, :booking_value, :commission_value, :currency, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, :raw_json, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
         ON CONFLICT(provider_id, external_conversion_id) DO UPDATE SET
            campaign_id = excluded.campaign_id,
            booking_value = excluded.booking_value,
            commission_value = excluded.commission_value,
            currency = excluded.currency,
            imported_at = CURRENT_TIMESTAMP,
            raw_json = excluded.raw_json,
            updated_at = CURRENT_TIMESTAMP',
        [
            ':id' => uuid(),
            ':provider_id' => $providerId,
            ':campaign_id' => $activeCampaignId,
            ':external_conversion_id' => $externalId,
            ':booking_value' => 250,
            ':commission_value' => 25,
            ':currency' => 'USD',
            ':raw_json' => '{"updated":true}',
        ]
    );
    assertTrue($okUpsert, 'Conversion upsert should succeed', $failures, $checks);

    $updatedConversion = queryOne(
        'SELECT booking_value, commission_value, raw_json
         FROM referral_conversions
         WHERE provider_id = :provider_id AND external_conversion_id = :external_conversion_id',
        [':provider_id' => $providerId, ':external_conversion_id' => $externalId]
    );
    assertTrue((float) ($updatedConversion['booking_value'] ?? 0) === 250.0, 'Conversion upsert should update booking_value', $failures, $checks);
    assertTrue((float) ($updatedConversion['commission_value'] ?? 0) === 25.0, 'Conversion upsert should update commission_value', $failures, $checks);
    assertTrue(strpos((string) ($updatedConversion['raw_json'] ?? ''), 'updated') !== false, 'Conversion upsert should update raw_json', $failures, $checks);

} finally {
    cleanupTestRows($cleanup);
}

if ($failures === []) {
    echo "Referral/CMS edge-case tests passed ($checks checks).\n";
    exit(0);
}

echo "Referral/CMS edge-case tests failed (" . count($failures) . " / $checks failed):\n";
foreach ($failures as $idx => $failure) {
    echo '  ' . ($idx + 1) . '. ' . $failure . "\n";
}
exit(1);
