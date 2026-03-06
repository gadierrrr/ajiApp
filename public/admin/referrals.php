<?php
/**
 * Admin - Referral system, placements, guide CMS, and revenue imports.
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/session.php';
require_once APP_ROOT . '/inc/admin.php';
require_once APP_ROOT . '/inc/referrals.php';
require_once APP_ROOT . '/inc/guide_cms.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

requireAdmin();

$tab = trim((string) ($_GET['tab'] ?? 'dashboard'));
$tab = $tab !== '' ? $tab : 'dashboard';

function referralAdminRedirect(string $tab, string $status = 'saved'): void
{
    redirect('/admin/referrals?tab=' . urlencode($tab) . '&' . urlencode($status) . '=1');
}

function referralParseJsonOrDefault(string $raw, array $default = []): string
{
    $raw = trim($raw);
    if ($raw === '') {
        return json_encode($default, JSON_UNESCAPED_SLASHES);
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return json_encode($default, JSON_UNESCAPED_SLASHES);
    }

    return json_encode($decoded, JSON_UNESCAPED_SLASHES);
}

function referralBuildGuidePayload(array $input): array
{
    $kind = trim((string) ($input['block_kind'] ?? 'rich_text'));

    if ($kind === 'heading') {
        return [
            'text_en' => trim((string) ($input['heading_text_en'] ?? '')),
            'text_es' => trim((string) ($input['heading_text_es'] ?? '')),
            'level' => max(2, min(4, (int) ($input['heading_level'] ?? 2))),
        ];
    }

    if ($kind === 'image') {
        return [
            'src' => trim((string) ($input['image_src'] ?? '')),
            'alt_en' => trim((string) ($input['image_alt_en'] ?? '')),
            'alt_es' => trim((string) ($input['image_alt_es'] ?? '')),
            'caption_en' => trim((string) ($input['image_caption_en'] ?? '')),
            'caption_es' => trim((string) ($input['image_caption_es'] ?? '')),
        ];
    }

    if ($kind === 'referral_block') {
        return [
            'campaign_slug' => trim((string) ($input['campaign_slug'] ?? '')),
            'title_en' => trim((string) ($input['ref_title_en'] ?? '')),
            'title_es' => trim((string) ($input['ref_title_es'] ?? '')),
            'description_en' => trim((string) ($input['ref_description_en'] ?? '')),
            'description_es' => trim((string) ($input['ref_description_es'] ?? '')),
            'button_label_en' => trim((string) ($input['ref_button_en'] ?? '')),
            'button_label_es' => trim((string) ($input['ref_button_es'] ?? '')),
            'style' => trim((string) ($input['ref_style'] ?? 'primary')),
            'block_slug' => trim((string) ($input['ref_block_slug'] ?? '')),
        ];
    }

    if ($kind === 'html') {
        return [
            'html_en' => (string) ($input['html_en'] ?? ''),
            'html_es' => (string) ($input['html_es'] ?? ''),
        ];
    }

    return [
        'html_en' => (string) ($input['rich_text_en'] ?? ''),
        'html_es' => (string) ($input['rich_text_es'] ?? ''),
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf((string) ($_POST['csrf_token'] ?? ''))) {
        referralAdminRedirect($tab, 'csrf_error');
    }

    $action = trim((string) ($_POST['action'] ?? ''));

    if ($action === 'save_provider') {
        $id = trim((string) ($_POST['id'] ?? ''));
        $isNew = $id === '';

        $slug = slugify((string) ($_POST['slug'] ?? ''));
        if ($slug === '') {
            referralAdminRedirect('providers', 'invalid');
        }

        if ($isNew) {
            $existing = queryOne('SELECT id FROM referral_providers WHERE slug = :slug', [':slug' => $slug]);
            if ($existing) {
                $id = (string) $existing['id'];
                $isNew = false;
            } else {
                $id = uuid();
            }
        }

        $params = [
            ':id' => $id,
            ':slug' => $slug,
            ':name' => trim((string) ($_POST['name'] ?? '')),
            ':status' => trim((string) ($_POST['status'] ?? 'active')),
            ':en' => trim((string) ($_POST['default_disclosure_en'] ?? '')),
            ':es' => trim((string) ($_POST['default_disclosure_es'] ?? '')),
        ];

        if ($isNew) {
            execute(
                'INSERT INTO referral_providers
                    (id, slug, name, status, default_disclosure_en, default_disclosure_es, created_at, updated_at)
                 VALUES
                    (:id, :slug, :name, :status, :en, :es, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
                $params
            );
        } else {
            execute(
                'UPDATE referral_providers
                 SET slug = :slug, name = :name, status = :status,
                     default_disclosure_en = :en, default_disclosure_es = :es,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id',
                $params
            );
        }

        referralAdminRedirect('providers');
    }

    if ($action === 'save_campaign') {
        $id = trim((string) ($_POST['id'] ?? ''));
        $isNew = $id === '';

        $slug = slugify((string) ($_POST['slug'] ?? ''));
        if ($slug === '') {
            referralAdminRedirect('campaigns', 'invalid');
        }

        if ($isNew) {
            $existing = queryOne('SELECT id FROM referral_campaigns WHERE slug = :slug', [':slug' => $slug]);
            if ($existing) {
                $id = (string) $existing['id'];
                $isNew = false;
            } else {
                $id = uuid();
            }
        }

        $params = [
            ':id' => $id,
            ':provider_id' => trim((string) ($_POST['provider_id'] ?? '')),
            ':slug' => $slug,
            ':name' => trim((string) ($_POST['name'] ?? '')),
            ':link_type' => trim((string) ($_POST['link_type'] ?? 'generic')),
            ':destination_scope' => trim((string) ($_POST['destination_scope'] ?? 'global')),
            ':target_url' => trim((string) ($_POST['target_url'] ?? '')),
            ':utm_json' => referralParseJsonOrDefault((string) ($_POST['utm_json'] ?? ''), []),
            ':priority' => (int) ($_POST['priority'] ?? 100),
            ':active_from' => trim((string) ($_POST['active_from'] ?? '')),
            ':active_to' => trim((string) ($_POST['active_to'] ?? '')),
            ':status' => trim((string) ($_POST['status'] ?? 'draft')),
        ];

        if ($params[':target_url'] !== '' && !referralTargetUrlIsAllowed($params[':target_url'])) {
            referralAdminRedirect('campaigns', 'invalid');
        }

        if ($isNew) {
            execute(
                'INSERT INTO referral_campaigns
                    (id, provider_id, slug, name, link_type, destination_scope, target_url, utm_json,
                     priority, active_from, active_to, status, created_at, updated_at)
                 VALUES
                    (:id, :provider_id, :slug, :name, :link_type, :destination_scope, :target_url, :utm_json,
                     :priority, :active_from, :active_to, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
                $params
            );
        } else {
            execute(
                'UPDATE referral_campaigns
                 SET provider_id = :provider_id, slug = :slug, name = :name,
                     link_type = :link_type, destination_scope = :destination_scope,
                     target_url = :target_url, utm_json = :utm_json,
                     priority = :priority, active_from = :active_from, active_to = :active_to,
                     status = :status, updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id',
                $params
            );
        }

        referralAdminRedirect('campaigns');
    }

    if ($action === 'save_block') {
        $id = trim((string) ($_POST['id'] ?? ''));
        $isNew = $id === '';

        $slug = slugify((string) ($_POST['slug'] ?? ''));
        if ($slug === '') {
            referralAdminRedirect('blocks', 'invalid');
        }

        if ($isNew) {
            $existing = queryOne('SELECT id FROM referral_blocks WHERE slug = :slug', [':slug' => $slug]);
            if ($existing) {
                $id = (string) $existing['id'];
                $isNew = false;
            } else {
                $id = uuid();
            }
        }

        $params = [
            ':id' => $id,
            ':slug' => $slug,
            ':block_type' => trim((string) ($_POST['block_type'] ?? 'inline_card')),
            ':label_en' => trim((string) ($_POST['label_en'] ?? '')),
            ':label_es' => trim((string) ($_POST['label_es'] ?? '')),
            ':style_variant' => trim((string) ($_POST['style_variant'] ?? 'card')),
            ':disclosure_mode' => trim((string) ($_POST['disclosure_mode'] ?? 'group')),
            ':metadata_json' => referralParseJsonOrDefault((string) ($_POST['metadata_json'] ?? ''), []),
            ':status' => trim((string) ($_POST['status'] ?? 'active')),
        ];

        if ($isNew) {
            execute(
                'INSERT INTO referral_blocks
                    (id, slug, block_type, label_en, label_es, style_variant, disclosure_mode, metadata_json, status, created_at, updated_at)
                 VALUES
                    (:id, :slug, :block_type, :label_en, :label_es, :style_variant, :disclosure_mode, :metadata_json, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
                $params
            );
        } else {
            execute(
                'UPDATE referral_blocks
                 SET slug = :slug, block_type = :block_type,
                     label_en = :label_en, label_es = :label_es,
                     style_variant = :style_variant, disclosure_mode = :disclosure_mode,
                     metadata_json = :metadata_json, status = :status,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id',
                $params
            );
        }

        referralAdminRedirect('blocks');
    }

    if ($action === 'save_placement') {
        $id = trim((string) ($_POST['id'] ?? ''));
        $isNew = $id === '';
        if ($isNew) {
            $id = uuid();
        }

        $params = [
            ':id' => $id,
            ':beach_id' => trim((string) ($_POST['beach_id'] ?? '')),
            ':anchor_key' => trim((string) ($_POST['anchor_key'] ?? 'hero')),
            ':campaign_id' => trim((string) ($_POST['campaign_id'] ?? '')),
            ':block_id' => trim((string) ($_POST['block_id'] ?? '')),
            ':locale' => trim((string) ($_POST['locale'] ?? 'all')),
            ':enabled' => isset($_POST['enabled']) ? 1 : 0,
            ':display_order' => (int) ($_POST['display_order'] ?? 0),
        ];

        if ($isNew) {
            execute(
                'INSERT INTO beach_referral_placements
                    (id, beach_id, anchor_key, campaign_id, block_id, locale, enabled, display_order, created_at, updated_at)
                 VALUES
                    (:id, :beach_id, :anchor_key, :campaign_id, :block_id, :locale, :enabled, :display_order, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
                [
                    ':id' => $params[':id'],
                    ':beach_id' => $params[':beach_id'],
                    ':anchor_key' => $params[':anchor_key'],
                    ':campaign_id' => $params[':campaign_id'],
                    ':block_id' => $params[':block_id'] !== '' ? $params[':block_id'] : null,
                    ':locale' => $params[':locale'],
                    ':enabled' => $params[':enabled'],
                    ':display_order' => $params[':display_order'],
                ]
            );
        } else {
            execute(
                'UPDATE beach_referral_placements
                 SET beach_id = :beach_id,
                     anchor_key = :anchor_key,
                     campaign_id = :campaign_id,
                     block_id = :block_id,
                     locale = :locale,
                     enabled = :enabled,
                     display_order = :display_order,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id',
                [
                    ':id' => $params[':id'],
                    ':beach_id' => $params[':beach_id'],
                    ':anchor_key' => $params[':anchor_key'],
                    ':campaign_id' => $params[':campaign_id'],
                    ':block_id' => $params[':block_id'] !== '' ? $params[':block_id'] : null,
                    ':locale' => $params[':locale'],
                    ':enabled' => $params[':enabled'],
                    ':display_order' => $params[':display_order'],
                ]
            );
        }

        referralAdminRedirect('placements');
    }

    if ($action === 'delete_placement') {
        $id = trim((string) ($_POST['id'] ?? ''));
        if ($id !== '') {
            execute('DELETE FROM beach_referral_placements WHERE id = :id', [':id' => $id]);
        }
        referralAdminRedirect('placements');
    }

    if ($action === 'save_guide') {
        $id = trim((string) ($_POST['id'] ?? ''));
        $isNew = $id === '';

        $slug = slugify((string) ($_POST['slug'] ?? ''));
        if ($slug === '') {
            referralAdminRedirect('guides', 'invalid');
        }

        if ($isNew) {
            $existing = queryOne('SELECT id FROM guide_articles WHERE slug = :slug', [':slug' => $slug]);
            if ($existing) {
                $id = (string) $existing['id'];
                $isNew = false;
            } else {
                $id = uuid();
            }
        }

        $params = [
            ':id' => $id,
            ':slug' => $slug,
            ':title_en' => trim((string) ($_POST['title_en'] ?? '')),
            ':title_es' => trim((string) ($_POST['title_es'] ?? '')),
            ':description_en' => trim((string) ($_POST['description_en'] ?? '')),
            ':description_es' => trim((string) ($_POST['description_es'] ?? '')),
            ':status' => trim((string) ($_POST['status'] ?? 'draft')),
            ':author_id' => trim((string) ($_SESSION['user_id'] ?? '')),
        ];

        if ($isNew) {
            execute(
                'INSERT INTO guide_articles
                    (id, slug, title_en, title_es, description_en, description_es, status, author_id, published_at, created_at, updated_at)
                 VALUES
                    (:id, :slug, :title_en, :title_es, :description_en, :description_es, :status, :author_id,
                     CASE WHEN :status = "published" THEN CURRENT_TIMESTAMP ELSE NULL END,
                     CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
                $params
            );
        } else {
            execute(
                'UPDATE guide_articles
                 SET slug = :slug,
                     title_en = :title_en,
                     title_es = :title_es,
                     description_en = :description_en,
                     description_es = :description_es,
                     status = :status,
                     author_id = :author_id,
                     published_at = CASE
                         WHEN :status = "published" AND (published_at IS NULL OR published_at = "") THEN CURRENT_TIMESTAMP
                         WHEN :status <> "published" THEN NULL
                         ELSE published_at
                     END,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id',
                $params
            );
        }

        redirect('/admin/referrals?tab=guides&guide_id=' . urlencode($id) . '&saved=1');
    }

    if ($action === 'save_guide_block') {
        $guideId = trim((string) ($_POST['guide_id'] ?? ''));
        $id = trim((string) ($_POST['id'] ?? ''));
        $isNew = $id === '';
        if ($isNew) {
            $id = uuid();
        }

        $blockKind = trim((string) ($_POST['block_kind'] ?? 'rich_text'));
        $payload = referralBuildGuidePayload($_POST);

        $params = [
            ':id' => $id,
            ':guide_id' => $guideId,
            ':block_order' => (int) ($_POST['block_order'] ?? 0),
            ':block_kind' => $blockKind,
            ':payload_json' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ':status' => trim((string) ($_POST['status'] ?? 'published')),
        ];

        if ($isNew) {
            execute(
                'INSERT INTO guide_blocks
                    (id, guide_id, block_order, block_kind, payload_json, status, created_at, updated_at)
                 VALUES
                    (:id, :guide_id, :block_order, :block_kind, :payload_json, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
                $params
            );
        } else {
            execute(
                'UPDATE guide_blocks
                 SET block_order = :block_order,
                     block_kind = :block_kind,
                     payload_json = :payload_json,
                     status = :status,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id AND guide_id = :guide_id',
                $params
            );
        }

        redirect('/admin/referrals?tab=guides&guide_id=' . urlencode($guideId) . '&saved=1');
    }

    if ($action === 'delete_guide_block') {
        $guideId = trim((string) ($_POST['guide_id'] ?? ''));
        $id = trim((string) ($_POST['id'] ?? ''));
        if ($id !== '') {
            execute('DELETE FROM guide_blocks WHERE id = :id', [':id' => $id]);
        }
        redirect('/admin/referrals?tab=guides&guide_id=' . urlencode($guideId) . '&saved=1');
    }

    if ($action === 'import_revenue_csv') {
        $providerId = trim((string) ($_POST['provider_id'] ?? ''));
        if ($providerId === '' || empty($_FILES['csv_file']['tmp_name'])) {
            referralAdminRedirect('revenue', 'invalid');
        }

        $jobId = uuid();
        execute(
            'INSERT INTO referral_import_jobs (id, provider_id, source_type, status, started_at, created_at, updated_at)
             VALUES (:id, :provider_id, :source_type, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
            [
                ':id' => $jobId,
                ':provider_id' => $providerId,
                ':source_type' => 'csv',
                ':status' => 'running',
            ]
        );

        $processed = 0;
        $upserted = 0;
        $skipped = 0;
        $errors = [];

        $fh = fopen((string) $_FILES['csv_file']['tmp_name'], 'r');
        if (!$fh) {
            execute(
                'UPDATE referral_import_jobs
                 SET status = :status, finished_at = CURRENT_TIMESTAMP, error_log = :error_log, updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id',
                [':status' => 'failed', ':error_log' => 'Unable to open uploaded CSV.', ':id' => $jobId]
            );
            referralAdminRedirect('revenue', 'import_failed');
        }

        $header = fgetcsv($fh);
        if (!is_array($header)) {
            fclose($fh);
            execute(
                'UPDATE referral_import_jobs
                 SET status = :status, finished_at = CURRENT_TIMESTAMP, error_log = :error_log, updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id',
                [':status' => 'failed', ':error_log' => 'CSV header not found.', ':id' => $jobId]
            );
            referralAdminRedirect('revenue', 'import_failed');
        }

        $map = [];
        foreach ($header as $idx => $column) {
            $map[trim((string) $column)] = $idx;
        }

        while (($row = fgetcsv($fh)) !== false) {
            $processed++;

            $externalId = trim((string) ($row[$map['external_conversion_id'] ?? -1] ?? ''));
            if ($externalId === '') {
                $skipped++;
                $errors[] = 'Row ' . $processed . ': missing external_conversion_id';
                continue;
            }

            $campaignSlug = trim((string) ($row[$map['campaign_slug'] ?? -1] ?? ''));
            $campaign = $campaignSlug !== '' ? queryOne('SELECT id FROM referral_campaigns WHERE slug = :slug', [':slug' => $campaignSlug]) : null;
            $campaignId = trim((string) ($campaign['id'] ?? ''));

            $bookingValue = (float) ($row[$map['booking_value'] ?? -1] ?? 0);
            $commissionValue = (float) ($row[$map['commission_value'] ?? -1] ?? 0);
            $currency = strtoupper(trim((string) ($row[$map['currency'] ?? -1] ?? 'USD')));
            if ($currency === '') {
                $currency = 'USD';
            }

            $bookedAt = trim((string) ($row[$map['booked_at'] ?? -1] ?? ''));
            if ($bookedAt === '') {
                $bookedAt = null;
            }

            $raw = [
                'external_conversion_id' => $externalId,
                'campaign_slug' => $campaignSlug,
                'booking_value' => $bookingValue,
                'commission_value' => $commissionValue,
                'currency' => $currency,
                'booked_at' => $bookedAt,
            ];

            $ok = execute(
                'INSERT INTO referral_conversions
                    (id, provider_id, campaign_id, external_conversion_id, click_id, booking_value, commission_value, currency, booked_at, imported_at, raw_json, created_at, updated_at)
                 VALUES
                    (:id, :provider_id, :campaign_id, :external_conversion_id, NULL, :booking_value, :commission_value, :currency, :booked_at, CURRENT_TIMESTAMP, :raw_json, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                 ON CONFLICT(provider_id, external_conversion_id) DO UPDATE SET
                    campaign_id = excluded.campaign_id,
                    booking_value = excluded.booking_value,
                    commission_value = excluded.commission_value,
                    currency = excluded.currency,
                    booked_at = excluded.booked_at,
                    imported_at = CURRENT_TIMESTAMP,
                    raw_json = excluded.raw_json,
                    updated_at = CURRENT_TIMESTAMP',
                [
                    ':id' => uuid(),
                    ':provider_id' => $providerId,
                    ':campaign_id' => $campaignId !== '' ? $campaignId : null,
                    ':external_conversion_id' => $externalId,
                    ':booking_value' => $bookingValue,
                    ':commission_value' => $commissionValue,
                    ':currency' => $currency,
                    ':booked_at' => $bookedAt,
                    ':raw_json' => json_encode($raw, JSON_UNESCAPED_SLASHES),
                ]
            );

            if ($ok) {
                $upserted++;
            } else {
                $errors[] = 'Row ' . $processed . ': insert/update failed for external_conversion_id=' . $externalId;
            }
        }

        fclose($fh);

        $summary = [
            'processed' => $processed,
            'upserted' => $upserted,
            'skipped' => $skipped,
            'errors' => count($errors),
        ];

        execute(
            'UPDATE referral_import_jobs
             SET status = :status,
                 finished_at = CURRENT_TIMESTAMP,
                 summary_json = :summary_json,
                 error_log = :error_log,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id',
            [
                ':status' => count($errors) > 0 ? 'completed_with_errors' : 'completed',
                ':summary_json' => json_encode($summary, JSON_UNESCAPED_SLASHES),
                ':error_log' => $errors ? implode("\n", array_slice($errors, 0, 100)) : null,
                ':id' => $jobId,
            ]
        );

        referralAdminRedirect('revenue', 'imported');
    }
}

$pageTitle = 'Referrals & Guide CMS';
$pageSubtitle = 'Manage providers, campaigns, placements, guide blocks, and revenue imports';

$providers = referralProviderOptions();
$campaigns = referralCampaignOptions(false);
$activeCampaigns = referralCampaignOptions(true);
$blocks = referralBlockOptions(false);
$beaches = query('SELECT id, name, slug FROM beaches ORDER BY name ASC');
$beaches = is_array($beaches) ? $beaches : [];

$placements = query(
    'SELECT
        p.id,
        p.anchor_key,
        p.locale,
        p.enabled,
        p.display_order,
        b.name AS beach_name,
        b.slug AS beach_slug,
        c.name AS campaign_name,
        c.slug AS campaign_slug,
        rb.slug AS block_slug
     FROM beach_referral_placements p
     INNER JOIN beaches b ON b.id = p.beach_id
     INNER JOIN referral_campaigns c ON c.id = p.campaign_id
     LEFT JOIN referral_blocks rb ON rb.id = p.block_id
     ORDER BY b.name ASC, p.anchor_key ASC, p.display_order ASC'
);
$placements = is_array($placements) ? $placements : [];

$guides = query('SELECT * FROM guide_articles ORDER BY updated_at DESC, created_at DESC');
$guides = is_array($guides) ? $guides : [];

$selectedGuideId = trim((string) ($_GET['guide_id'] ?? ''));
$selectedGuide = $selectedGuideId !== '' ? guideCmsLoadArticleById($selectedGuideId) : null;
if (!$selectedGuide && !empty($guides)) {
    $selectedGuide = $guides[0];
    $selectedGuideId = (string) ($selectedGuide['id'] ?? '');
}

$selectedGuideBlocks = [];
if ($selectedGuideId !== '') {
    $selectedGuideBlocks = guideCmsLoadBlocks($selectedGuideId, false);
}

$metrics = [
    'clicks_30d' => (int) (queryOne('SELECT COUNT(*) AS c FROM referral_clicks WHERE clicked_at >= datetime("now", "-30 day")')['c'] ?? 0),
    'clicks_all' => (int) (queryOne('SELECT COUNT(*) AS c FROM referral_clicks')['c'] ?? 0),
    'conversions_30d' => (int) (queryOne('SELECT COUNT(*) AS c FROM referral_conversions WHERE imported_at >= datetime("now", "-30 day")')['c'] ?? 0),
    'commission_30d' => (float) (queryOne('SELECT COALESCE(SUM(commission_value), 0) AS c FROM referral_conversions WHERE imported_at >= datetime("now", "-30 day")')['c'] ?? 0),
    'booking_30d' => (float) (queryOne('SELECT COALESCE(SUM(booking_value), 0) AS c FROM referral_conversions WHERE imported_at >= datetime("now", "-30 day")')['c'] ?? 0),
];

$providerPerformance = query(
    'SELECT
        p.name AS provider_name,
        COUNT(rc.id) AS clicks,
        COALESCE(SUM(rv.commission_value), 0) AS commission,
        COALESCE(SUM(rv.booking_value), 0) AS booking
     FROM referral_providers p
     LEFT JOIN referral_campaigns c ON c.provider_id = p.id
     LEFT JOIN referral_clicks rc ON rc.campaign_id = c.id
     LEFT JOIN referral_conversions rv ON rv.campaign_id = c.id
     GROUP BY p.id
     ORDER BY p.name ASC'
);
$providerPerformance = is_array($providerPerformance) ? $providerPerformance : [];

$recentImports = query('SELECT * FROM referral_import_jobs ORDER BY started_at DESC LIMIT 20');
$recentImports = is_array($recentImports) ? $recentImports : [];

$pageActions = '<a href="/go?c=expedia-hotels-sanjuan" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">Test /go Redirect</a>';

include __DIR__ . '/components/header.php';

$tabs = [
    'dashboard' => 'Dashboard',
    'providers' => 'Providers',
    'campaigns' => 'Campaigns',
    'blocks' => 'Blocks',
    'placements' => 'Beach Anchors',
    'guides' => 'Guide CMS',
    'revenue' => 'Revenue',
];
?>

<div class="bg-white rounded-xl shadow-sm mb-6">
    <div class="p-2 flex flex-wrap gap-2">
        <?php foreach ($tabs as $key => $label): ?>
            <a href="/admin/referrals?tab=<?= h($key) ?><?= $selectedGuideId !== '' && $key === 'guides' ? '&guide_id=' . urlencode($selectedGuideId) : '' ?>"
               class="px-3 py-2 rounded-lg text-sm font-medium <?= $tab === $key ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                <?= h($label) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<?php if (isset($_GET['saved'])): ?>
<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">Saved successfully.</div>
<?php endif; ?>
<?php if (isset($_GET['imported'])): ?>
<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">Revenue import completed.</div>
<?php endif; ?>
<?php if (isset($_GET['import_failed'])): ?>
<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">Revenue import failed. Check import job logs below.</div>
<?php endif; ?>
<?php if (isset($_GET['invalid'])): ?>
<div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-lg mb-6">Some required fields are missing or invalid.</div>
<?php endif; ?>
<?php if (isset($_GET['csrf_error'])): ?>
<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">CSRF validation failed. Please retry.</div>
<?php endif; ?>

<?php if ($tab === 'dashboard'): ?>
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-4"><p class="text-sm text-gray-500">Clicks (30d)</p><p class="text-2xl font-bold text-gray-900"><?= number_format($metrics['clicks_30d']) ?></p></div>
    <div class="bg-white rounded-xl shadow-sm p-4"><p class="text-sm text-gray-500">Clicks (all)</p><p class="text-2xl font-bold text-gray-900"><?= number_format($metrics['clicks_all']) ?></p></div>
    <div class="bg-white rounded-xl shadow-sm p-4"><p class="text-sm text-gray-500">Conversions (30d)</p><p class="text-2xl font-bold text-gray-900"><?= number_format($metrics['conversions_30d']) ?></p></div>
    <div class="bg-white rounded-xl shadow-sm p-4"><p class="text-sm text-gray-500">Booking Value (30d)</p><p class="text-2xl font-bold text-gray-900">$<?= number_format($metrics['booking_30d'], 2) ?></p></div>
    <div class="bg-white rounded-xl shadow-sm p-4"><p class="text-sm text-gray-500">Commission (30d)</p><p class="text-2xl font-bold text-green-700">$<?= number_format($metrics['commission_30d'], 2) ?></p></div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100"><h2 class="font-semibold text-gray-900">Provider Performance</h2></div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-4 py-2">Provider</th>
                    <th class="text-right px-4 py-2">Clicks</th>
                    <th class="text-right px-4 py-2">Commission</th>
                    <th class="text-right px-4 py-2">Booking</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($providerPerformance as $row): ?>
                <tr class="border-t border-gray-100">
                    <td class="px-4 py-2"><?= h($row['provider_name']) ?></td>
                    <td class="px-4 py-2 text-right"><?= number_format((int) $row['clicks']) ?></td>
                    <td class="px-4 py-2 text-right">$<?= number_format((float) $row['commission'], 2) ?></td>
                    <td class="px-4 py-2 text-right">$<?= number_format((float) $row['booking'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100"><h2 class="font-semibold text-gray-900">Recent Import Jobs</h2></div>
        <div class="divide-y divide-gray-100">
            <?php foreach ($recentImports as $job): ?>
            <div class="px-4 py-3">
                <p class="text-sm font-medium text-gray-900"><?= h($job['source_type']) ?> · <?= h($job['status']) ?></p>
                <p class="text-xs text-gray-500"><?= h((string) ($job['started_at'] ?? '')) ?> → <?= h((string) ($job['finished_at'] ?? '')) ?></p>
                <?php if (!empty($job['summary_json'])): ?>
                    <pre class="mt-2 text-xs bg-gray-50 p-2 rounded overflow-x-auto"><?= h((string) $job['summary_json']) ?></pre>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php if (empty($recentImports)): ?>
            <div class="px-4 py-3 text-sm text-gray-500">No imports yet.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($tab === 'providers'): ?>
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-4">
        <h2 class="text-lg font-semibold text-gray-900 mb-3">Save Provider</h2>
        <form method="POST" class="space-y-3">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="save_provider">
            <input type="hidden" name="id" value="">
            <div><label class="block text-sm text-gray-600 mb-1">Slug</label><input name="slug" required class="w-full border rounded-lg px-3 py-2" placeholder="aviator"></div>
            <div><label class="block text-sm text-gray-600 mb-1">Name</label><input name="name" required class="w-full border rounded-lg px-3 py-2" placeholder="Aviator"></div>
            <div><label class="block text-sm text-gray-600 mb-1">Status</label><select name="status" class="w-full border rounded-lg px-3 py-2"><option value="active">active</option><option value="paused">paused</option><option value="archived">archived</option></select></div>
            <div><label class="block text-sm text-gray-600 mb-1">Disclosure (EN)</label><textarea name="default_disclosure_en" rows="2" class="w-full border rounded-lg px-3 py-2"></textarea></div>
            <div><label class="block text-sm text-gray-600 mb-1">Disclosure (ES)</label><textarea name="default_disclosure_es" rows="2" class="w-full border rounded-lg px-3 py-2"></textarea></div>
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">Save Provider</button>
        </form>
    </div>
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600"><tr><th class="text-left px-4 py-2">Slug</th><th class="text-left px-4 py-2">Name</th><th class="text-left px-4 py-2">Status</th></tr></thead>
            <tbody>
            <?php foreach ($providers as $provider): ?>
                <tr class="border-t border-gray-100"><td class="px-4 py-2 font-mono"><?= h($provider['slug']) ?></td><td class="px-4 py-2"><?= h($provider['name']) ?></td><td class="px-4 py-2"><?= h($provider['status']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($tab === 'campaigns'): ?>
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-4">
        <h2 class="text-lg font-semibold text-gray-900 mb-3">Save Campaign</h2>
        <form method="POST" class="space-y-3">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="save_campaign">
            <input type="hidden" name="id" value="">
            <div><label class="block text-sm text-gray-600 mb-1">Provider</label><select name="provider_id" class="w-full border rounded-lg px-3 py-2" required><?php foreach ($providers as $provider): ?><option value="<?= h($provider['id']) ?>"><?= h($provider['name']) ?> (<?= h($provider['slug']) ?>)</option><?php endforeach; ?></select></div>
            <div><label class="block text-sm text-gray-600 mb-1">Slug</label><input name="slug" required class="w-full border rounded-lg px-3 py-2" placeholder="expedia-hotels-sanjuan"></div>
            <div><label class="block text-sm text-gray-600 mb-1">Name</label><input name="name" required class="w-full border rounded-lg px-3 py-2"></div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="block text-sm text-gray-600 mb-1">Link Type</label><select name="link_type" class="w-full border rounded-lg px-3 py-2"><option>hotel</option><option>flight</option><option>car</option><option>package</option><option>activity</option><option selected>generic</option></select></div>
                <div><label class="block text-sm text-gray-600 mb-1">Scope</label><input name="destination_scope" class="w-full border rounded-lg px-3 py-2" value="global"></div>
            </div>
            <div><label class="block text-sm text-gray-600 mb-1">Target URL</label><input name="target_url" class="w-full border rounded-lg px-3 py-2" placeholder="https://..."></div>
            <div><label class="block text-sm text-gray-600 mb-1">UTM JSON</label><textarea name="utm_json" rows="2" class="w-full border rounded-lg px-3 py-2" placeholder='{"utm_source":"bf"}'></textarea></div>
            <div class="grid grid-cols-3 gap-3">
                <div><label class="block text-sm text-gray-600 mb-1">Priority</label><input name="priority" type="number" value="100" class="w-full border rounded-lg px-3 py-2"></div>
                <div><label class="block text-sm text-gray-600 mb-1">Active From</label><input name="active_from" type="datetime-local" class="w-full border rounded-lg px-3 py-2"></div>
                <div><label class="block text-sm text-gray-600 mb-1">Active To</label><input name="active_to" type="datetime-local" class="w-full border rounded-lg px-3 py-2"></div>
            </div>
            <div><label class="block text-sm text-gray-600 mb-1">Status</label><select name="status" class="w-full border rounded-lg px-3 py-2"><option value="active">active</option><option value="draft" selected>draft</option><option value="paused">paused</option><option value="archived">archived</option></select></div>
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">Save Campaign</button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600"><tr><th class="text-left px-4 py-2">Campaign</th><th class="text-left px-4 py-2">Provider</th><th class="text-left px-4 py-2">Status</th><th class="text-right px-4 py-2">Priority</th></tr></thead>
            <tbody>
            <?php foreach ($campaigns as $campaign): ?>
                <tr class="border-t border-gray-100">
                    <td class="px-4 py-2"><p class="font-medium text-gray-900"><?= h($campaign['name']) ?></p><p class="font-mono text-xs text-gray-500"><?= h($campaign['slug']) ?></p></td>
                    <td class="px-4 py-2"><?= h($campaign['provider_name']) ?></td>
                    <td class="px-4 py-2"><?= h($campaign['status']) ?></td>
                    <td class="px-4 py-2 text-right"><?= (int) ($campaign['priority'] ?? 0) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($tab === 'blocks'): ?>
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-4">
        <h2 class="text-lg font-semibold text-gray-900 mb-3">Save Block Template</h2>
        <form method="POST" class="space-y-3">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="save_block">
            <input type="hidden" name="id" value="">
            <div><label class="block text-sm text-gray-600 mb-1">Slug</label><input name="slug" required class="w-full border rounded-lg px-3 py-2"></div>
            <div><label class="block text-sm text-gray-600 mb-1">Type</label><select name="block_type" class="w-full border rounded-lg px-3 py-2"><option value="inline_card">inline_card</option><option value="comparison_strip">comparison_strip</option><option value="cta_button">cta_button</option><option value="hotel_grid">hotel_grid</option></select></div>
            <div><label class="block text-sm text-gray-600 mb-1">Label EN</label><input name="label_en" class="w-full border rounded-lg px-3 py-2"></div>
            <div><label class="block text-sm text-gray-600 mb-1">Label ES</label><input name="label_es" class="w-full border rounded-lg px-3 py-2"></div>
            <div><label class="block text-sm text-gray-600 mb-1">Style Variant</label><input name="style_variant" class="w-full border rounded-lg px-3 py-2" value="card"></div>
            <div><label class="block text-sm text-gray-600 mb-1">Disclosure Mode</label><select name="disclosure_mode" class="w-full border rounded-lg px-3 py-2"><option value="group">group</option><option value="inline">inline</option><option value="none">none</option></select></div>
            <div><label class="block text-sm text-gray-600 mb-1">Metadata JSON</label><textarea name="metadata_json" rows="2" class="w-full border rounded-lg px-3 py-2">{}</textarea></div>
            <div><label class="block text-sm text-gray-600 mb-1">Status</label><select name="status" class="w-full border rounded-lg px-3 py-2"><option value="active">active</option><option value="archived">archived</option></select></div>
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">Save Block</button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600"><tr><th class="text-left px-4 py-2">Slug</th><th class="text-left px-4 py-2">Type</th><th class="text-left px-4 py-2">Style</th><th class="text-left px-4 py-2">Status</th></tr></thead>
            <tbody>
            <?php foreach ($blocks as $block): ?>
                <tr class="border-t border-gray-100"><td class="px-4 py-2 font-mono"><?= h($block['slug']) ?></td><td class="px-4 py-2"><?= h($block['block_type']) ?></td><td class="px-4 py-2"><?= h($block['style_variant']) ?></td><td class="px-4 py-2"><?= h($block['status']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($tab === 'placements'): ?>
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-4">
        <h2 class="text-lg font-semibold text-gray-900 mb-3">Save Beach Anchor Placement</h2>
        <form method="POST" class="space-y-3">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="save_placement">
            <input type="hidden" name="id" value="">
            <div><label class="block text-sm text-gray-600 mb-1">Beach</label><select name="beach_id" class="w-full border rounded-lg px-3 py-2" required><?php foreach ($beaches as $beach): ?><option value="<?= h($beach['id']) ?>"><?= h($beach['name']) ?> (<?= h($beach['slug']) ?>)</option><?php endforeach; ?></select></div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="block text-sm text-gray-600 mb-1">Anchor</label><select name="anchor_key" class="w-full border rounded-lg px-3 py-2"><option>hero</option><option>mid_content</option><option>sidebar</option><option>planning</option><option>bottom</option></select></div>
                <div><label class="block text-sm text-gray-600 mb-1">Locale</label><select name="locale" class="w-full border rounded-lg px-3 py-2"><option value="all">all</option><option value="en">en</option><option value="es">es</option></select></div>
            </div>
            <div><label class="block text-sm text-gray-600 mb-1">Campaign</label><select name="campaign_id" class="w-full border rounded-lg px-3 py-2" required><?php foreach ($activeCampaigns as $campaign): ?><option value="<?= h($campaign['id']) ?>"><?= h($campaign['provider_name']) ?> · <?= h($campaign['name']) ?></option><?php endforeach; ?></select></div>
            <div><label class="block text-sm text-gray-600 mb-1">Block Template (optional)</label><select name="block_id" class="w-full border rounded-lg px-3 py-2"><option value="">(none)</option><?php foreach ($blocks as $block): ?><option value="<?= h($block['id']) ?>"><?= h($block['slug']) ?></option><?php endforeach; ?></select></div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="block text-sm text-gray-600 mb-1">Display Order</label><input type="number" name="display_order" value="0" class="w-full border rounded-lg px-3 py-2"></div>
                <label class="flex items-center gap-2 text-sm text-gray-700 mt-7"><input type="checkbox" name="enabled" checked> Enabled</label>
            </div>
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">Save Placement</button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600"><tr><th class="text-left px-4 py-2">Beach</th><th class="text-left px-4 py-2">Anchor</th><th class="text-left px-4 py-2">Campaign</th><th class="text-left px-4 py-2">Locale</th><th class="text-right px-4 py-2">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($placements as $placement): ?>
                <tr class="border-t border-gray-100">
                    <td class="px-4 py-2"><p class="font-medium"><?= h($placement['beach_name']) ?></p><p class="text-xs text-gray-500"><?= h($placement['beach_slug']) ?></p></td>
                    <td class="px-4 py-2"><?= h($placement['anchor_key']) ?> <span class="text-xs text-gray-500">(#<?= (int)$placement['display_order'] ?>)</span></td>
                    <td class="px-4 py-2"><p class="font-medium"><?= h($placement['campaign_name']) ?></p><p class="text-xs text-gray-500"><?= h($placement['campaign_slug']) ?></p></td>
                    <td class="px-4 py-2"><?= h($placement['locale']) ?></td>
                    <td class="px-4 py-2 text-right">
                        <form method="POST" class="inline" data-action-confirm="Delete this placement?" data-action="submitParentForm" data-action-args='["__this__"]' data-on="submit">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete_placement">
                            <input type="hidden" name="id" value="<?= h($placement['id']) ?>">
                            <button class="text-red-600 hover:text-red-800 text-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($tab === 'guides'): ?>
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-4 xl:col-span-1">
        <h2 class="text-lg font-semibold text-gray-900 mb-3">Save Guide</h2>
        <form method="POST" class="space-y-3">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="save_guide">
            <input type="hidden" name="id" value="<?= h((string) ($selectedGuide['id'] ?? '')) ?>">
            <div><label class="block text-sm text-gray-600 mb-1">Slug</label><input name="slug" class="w-full border rounded-lg px-3 py-2" value="<?= h((string) ($selectedGuide['slug'] ?? '')) ?>" required></div>
            <div><label class="block text-sm text-gray-600 mb-1">Title EN</label><input name="title_en" class="w-full border rounded-lg px-3 py-2" value="<?= h((string) ($selectedGuide['title_en'] ?? '')) ?>" required></div>
            <div><label class="block text-sm text-gray-600 mb-1">Title ES</label><input name="title_es" class="w-full border rounded-lg px-3 py-2" value="<?= h((string) ($selectedGuide['title_es'] ?? '')) ?>"></div>
            <div><label class="block text-sm text-gray-600 mb-1">Description EN</label><textarea name="description_en" rows="2" class="w-full border rounded-lg px-3 py-2"><?= h((string) ($selectedGuide['description_en'] ?? '')) ?></textarea></div>
            <div><label class="block text-sm text-gray-600 mb-1">Description ES</label><textarea name="description_es" rows="2" class="w-full border rounded-lg px-3 py-2"><?= h((string) ($selectedGuide['description_es'] ?? '')) ?></textarea></div>
            <div><label class="block text-sm text-gray-600 mb-1">Status</label><select name="status" class="w-full border rounded-lg px-3 py-2"><option value="draft" <?= (($selectedGuide['status'] ?? '') === 'draft') ? 'selected' : '' ?>>draft</option><option value="published" <?= (($selectedGuide['status'] ?? '') === 'published') ? 'selected' : '' ?>>published</option><option value="archived" <?= (($selectedGuide['status'] ?? '') === 'archived') ? 'selected' : '' ?>>archived</option></select></div>
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">Save Guide</button>
        </form>

        <div class="mt-6 border-t border-gray-100 pt-4">
            <h3 class="font-semibold text-gray-900 mb-2">Guides</h3>
            <div class="space-y-1 max-h-72 overflow-auto">
                <?php foreach ($guides as $guide): ?>
                    <a href="/admin/referrals?tab=guides&guide_id=<?= urlencode((string) $guide['id']) ?>" class="block px-2 py-1 rounded <?= ((string) $guide['id'] === (string) $selectedGuideId) ? 'bg-blue-50 text-blue-700' : 'hover:bg-gray-50 text-gray-700' ?>">
                        <div class="text-sm font-medium"><?= h($guide['title_en']) ?></div>
                        <div class="text-xs font-mono text-gray-500"><?= h($guide['slug']) ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-4 xl:col-span-2">
        <h2 class="text-lg font-semibold text-gray-900 mb-3">Add Guide Block</h2>
        <?php if (!$selectedGuideId): ?>
            <p class="text-sm text-gray-500">Save a guide first to add blocks.</p>
        <?php else: ?>
            <form method="POST" class="space-y-3 mb-6">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="save_guide_block">
                <input type="hidden" name="id" value="">
                <input type="hidden" name="guide_id" value="<?= h($selectedGuideId) ?>">
                <div class="grid grid-cols-3 gap-3">
                    <div><label class="block text-sm text-gray-600 mb-1">Order</label><input type="number" name="block_order" value="0" class="w-full border rounded-lg px-3 py-2"></div>
                    <div><label class="block text-sm text-gray-600 mb-1">Kind</label><select name="block_kind" class="w-full border rounded-lg px-3 py-2"><option value="rich_text">rich_text</option><option value="heading">heading</option><option value="referral_block">referral_block</option><option value="image">image</option><option value="html">html</option></select></div>
                    <div><label class="block text-sm text-gray-600 mb-1">Status</label><select name="status" class="w-full border rounded-lg px-3 py-2"><option value="published">published</option><option value="draft">draft</option></select></div>
                </div>

                <details class="border rounded-lg p-3" open>
                    <summary class="font-medium text-gray-800 cursor-pointer">Rich Text / HTML</summary>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
                        <div><label class="block text-sm text-gray-600 mb-1">Rich Text EN (HTML)</label><textarea name="rich_text_en" rows="4" class="w-full border rounded-lg px-3 py-2"></textarea></div>
                        <div><label class="block text-sm text-gray-600 mb-1">Rich Text ES (HTML)</label><textarea name="rich_text_es" rows="4" class="w-full border rounded-lg px-3 py-2"></textarea></div>
                        <div><label class="block text-sm text-gray-600 mb-1">Raw HTML EN</label><textarea name="html_en" rows="4" class="w-full border rounded-lg px-3 py-2"></textarea></div>
                        <div><label class="block text-sm text-gray-600 mb-1">Raw HTML ES</label><textarea name="html_es" rows="4" class="w-full border rounded-lg px-3 py-2"></textarea></div>
                    </div>
                </details>

                <details class="border rounded-lg p-3">
                    <summary class="font-medium text-gray-800 cursor-pointer">Heading</summary>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-3">
                        <div><label class="block text-sm text-gray-600 mb-1">Text EN</label><input name="heading_text_en" class="w-full border rounded-lg px-3 py-2"></div>
                        <div><label class="block text-sm text-gray-600 mb-1">Text ES</label><input name="heading_text_es" class="w-full border rounded-lg px-3 py-2"></div>
                        <div><label class="block text-sm text-gray-600 mb-1">Level</label><select name="heading_level" class="w-full border rounded-lg px-3 py-2"><option value="2">h2</option><option value="3">h3</option><option value="4">h4</option></select></div>
                    </div>
                </details>

                <details class="border rounded-lg p-3">
                    <summary class="font-medium text-gray-800 cursor-pointer">Referral Block</summary>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
                        <div><label class="block text-sm text-gray-600 mb-1">Campaign</label><select name="campaign_slug" class="w-full border rounded-lg px-3 py-2"><?php foreach ($activeCampaigns as $campaign): ?><option value="<?= h($campaign['slug']) ?>"><?= h($campaign['provider_name']) ?> · <?= h($campaign['name']) ?></option><?php endforeach; ?></select></div>
                        <div><label class="block text-sm text-gray-600 mb-1">Style</label><select name="ref_style" class="w-full border rounded-lg px-3 py-2"><option value="primary">primary</option><option value="secondary">secondary</option><option value="outline">outline</option><option value="yellow">yellow</option></select></div>
                        <div><label class="block text-sm text-gray-600 mb-1">Title EN</label><input name="ref_title_en" class="w-full border rounded-lg px-3 py-2"></div>
                        <div><label class="block text-sm text-gray-600 mb-1">Title ES</label><input name="ref_title_es" class="w-full border rounded-lg px-3 py-2"></div>
                        <div><label class="block text-sm text-gray-600 mb-1">Description EN</label><textarea name="ref_description_en" rows="2" class="w-full border rounded-lg px-3 py-2"></textarea></div>
                        <div><label class="block text-sm text-gray-600 mb-1">Description ES</label><textarea name="ref_description_es" rows="2" class="w-full border rounded-lg px-3 py-2"></textarea></div>
                        <div><label class="block text-sm text-gray-600 mb-1">Button EN</label><input name="ref_button_en" class="w-full border rounded-lg px-3 py-2"></div>
                        <div><label class="block text-sm text-gray-600 mb-1">Button ES</label><input name="ref_button_es" class="w-full border rounded-lg px-3 py-2"></div>
                        <div><label class="block text-sm text-gray-600 mb-1">Block Slug (tracking)</label><input name="ref_block_slug" class="w-full border rounded-lg px-3 py-2" placeholder="booking-cta-strip"></div>
                    </div>
                </details>

                <details class="border rounded-lg p-3">
                    <summary class="font-medium text-gray-800 cursor-pointer">Image</summary>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
                        <div><label class="block text-sm text-gray-600 mb-1">Image URL</label><input name="image_src" class="w-full border rounded-lg px-3 py-2"></div>
                        <div><label class="block text-sm text-gray-600 mb-1">Alt EN</label><input name="image_alt_en" class="w-full border rounded-lg px-3 py-2"></div>
                        <div><label class="block text-sm text-gray-600 mb-1">Alt ES</label><input name="image_alt_es" class="w-full border rounded-lg px-3 py-2"></div>
                        <div><label class="block text-sm text-gray-600 mb-1">Caption EN</label><input name="image_caption_en" class="w-full border rounded-lg px-3 py-2"></div>
                        <div><label class="block text-sm text-gray-600 mb-1">Caption ES</label><input name="image_caption_es" class="w-full border rounded-lg px-3 py-2"></div>
                    </div>
                </details>

                <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">Add Block</button>
            </form>

            <h3 class="font-semibold text-gray-900 mb-2">Current Blocks</h3>
            <div class="space-y-3">
                <?php foreach ($selectedGuideBlocks as $block): ?>
                <div class="border rounded-lg p-3">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">#<?= (int) $block['block_order'] ?> · <?= h($block['block_kind']) ?> · <?= h($block['status']) ?></p>
                            <pre class="mt-2 text-xs bg-gray-50 p-2 rounded overflow-x-auto"><?= h((string) $block['payload_json']) ?></pre>
                        </div>
                        <form method="POST" data-action-confirm="Delete this block?" data-action="submitParentForm" data-action-args='["__this__"]' data-on="submit">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete_guide_block">
                            <input type="hidden" name="guide_id" value="<?= h($selectedGuideId) ?>">
                            <input type="hidden" name="id" value="<?= h($block['id']) ?>">
                            <button class="text-red-600 hover:text-red-800 text-sm">Delete</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($selectedGuideBlocks)): ?>
                <p class="text-sm text-gray-500">No blocks yet.</p>
                <?php endif; ?>
            </div>

            <?php if ($selectedGuide): ?>
            <div class="mt-4 pt-4 border-t border-gray-100">
                <a href="/guides/<?= h($selectedGuide['slug']) ?>" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Open public guide URL ↗</a>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if ($tab === 'revenue'): ?>
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-4">
        <h2 class="text-lg font-semibold text-gray-900 mb-3">Import Revenue CSV</h2>
        <p class="text-sm text-gray-600 mb-3">Required headers: <code>external_conversion_id,campaign_slug,booking_value,commission_value,currency,booked_at</code></p>
        <form method="POST" enctype="multipart/form-data" class="space-y-3">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="import_revenue_csv">
            <div><label class="block text-sm text-gray-600 mb-1">Provider</label><select name="provider_id" class="w-full border rounded-lg px-3 py-2" required><?php foreach ($providers as $provider): ?><option value="<?= h($provider['id']) ?>"><?= h($provider['name']) ?></option><?php endforeach; ?></select></div>
            <div><label class="block text-sm text-gray-600 mb-1">CSV file</label><input type="file" name="csv_file" accept=".csv,text/csv" class="w-full border rounded-lg px-3 py-2 bg-white" required></div>
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">Import</button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-4">
        <h2 class="text-lg font-semibold text-gray-900 mb-3">Recent Import Jobs</h2>
        <div class="space-y-3 max-h-[28rem] overflow-auto">
            <?php foreach ($recentImports as $job): ?>
                <div class="border rounded-lg p-3">
                    <p class="text-sm font-semibold text-gray-900"><?= h($job['status']) ?> · <?= h($job['source_type']) ?></p>
                    <p class="text-xs text-gray-500"><?= h((string) ($job['started_at'] ?? '')) ?> → <?= h((string) ($job['finished_at'] ?? '')) ?></p>
                    <?php if (!empty($job['summary_json'])): ?>
                    <pre class="mt-2 text-xs bg-gray-50 p-2 rounded overflow-x-auto"><?= h((string) $job['summary_json']) ?></pre>
                    <?php endif; ?>
                    <?php if (!empty($job['error_log'])): ?>
                    <pre class="mt-2 text-xs bg-red-50 text-red-700 p-2 rounded overflow-x-auto"><?= h((string) $job['error_log']) ?></pre>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <?php if (empty($recentImports)): ?>
                <p class="text-sm text-gray-500">No imports yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/components/footer.php'; ?>
