<?php
/**
 * Migration: Add referral system + guide CMS tables
 */

require_once __DIR__ . '/../inc/db.php';

echo "Starting migration: referral system tables\n";

try {
    $db = getDB();

    $db->exec("\n        CREATE TABLE IF NOT EXISTS referral_providers (\n            id TEXT PRIMARY KEY,\n            slug TEXT NOT NULL UNIQUE,\n            name TEXT NOT NULL,\n            status TEXT NOT NULL DEFAULT 'active',\n            default_disclosure_en TEXT,\n            default_disclosure_es TEXT,\n            created_at TEXT DEFAULT CURRENT_TIMESTAMP,\n            updated_at TEXT DEFAULT CURRENT_TIMESTAMP\n        )\n    ");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_referral_providers_status ON referral_providers(status)");

    $db->exec("\n        CREATE TABLE IF NOT EXISTS referral_campaigns (\n            id TEXT PRIMARY KEY,\n            provider_id TEXT NOT NULL,\n            slug TEXT NOT NULL UNIQUE,\n            name TEXT NOT NULL,\n            link_type TEXT NOT NULL DEFAULT 'generic',\n            destination_scope TEXT NOT NULL DEFAULT 'global',\n            target_url TEXT NOT NULL DEFAULT '',\n            utm_json TEXT NOT NULL DEFAULT '{}',\n            priority INTEGER NOT NULL DEFAULT 100,\n            active_from TEXT,\n            active_to TEXT,\n            status TEXT NOT NULL DEFAULT 'draft',\n            created_at TEXT DEFAULT CURRENT_TIMESTAMP,\n            updated_at TEXT DEFAULT CURRENT_TIMESTAMP,\n            FOREIGN KEY (provider_id) REFERENCES referral_providers(id) ON DELETE CASCADE\n        )\n    ");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_referral_campaigns_provider ON referral_campaigns(provider_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_referral_campaigns_status ON referral_campaigns(status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_referral_campaigns_dates ON referral_campaigns(active_from, active_to)");

    $db->exec("\n        CREATE TABLE IF NOT EXISTS referral_blocks (\n            id TEXT PRIMARY KEY,\n            slug TEXT NOT NULL UNIQUE,\n            block_type TEXT NOT NULL,\n            label_en TEXT NOT NULL,\n            label_es TEXT,\n            style_variant TEXT NOT NULL DEFAULT 'card',\n            disclosure_mode TEXT NOT NULL DEFAULT 'group',\n            metadata_json TEXT NOT NULL DEFAULT '{}',\n            status TEXT NOT NULL DEFAULT 'active',\n            created_at TEXT DEFAULT CURRENT_TIMESTAMP,\n            updated_at TEXT DEFAULT CURRENT_TIMESTAMP\n        )\n    ");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_referral_blocks_status ON referral_blocks(status)");

    $db->exec("\n        CREATE TABLE IF NOT EXISTS guide_articles (\n            id TEXT PRIMARY KEY,\n            slug TEXT NOT NULL UNIQUE,\n            title_en TEXT NOT NULL,\n            title_es TEXT,\n            description_en TEXT,\n            description_es TEXT,\n            status TEXT NOT NULL DEFAULT 'draft',\n            author_id TEXT,\n            published_at TEXT,\n            created_at TEXT DEFAULT CURRENT_TIMESTAMP,\n            updated_at TEXT DEFAULT CURRENT_TIMESTAMP,\n            FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL\n        )\n    ");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_guide_articles_status ON guide_articles(status)");

    $db->exec("\n        CREATE TABLE IF NOT EXISTS guide_blocks (\n            id TEXT PRIMARY KEY,\n            guide_id TEXT NOT NULL,\n            block_order INTEGER NOT NULL DEFAULT 0,\n            block_kind TEXT NOT NULL,\n            payload_json TEXT NOT NULL,\n            status TEXT NOT NULL DEFAULT 'published',\n            created_at TEXT DEFAULT CURRENT_TIMESTAMP,\n            updated_at TEXT DEFAULT CURRENT_TIMESTAMP,\n            FOREIGN KEY (guide_id) REFERENCES guide_articles(id) ON DELETE CASCADE\n        )\n    ");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_guide_blocks_guide ON guide_blocks(guide_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_guide_blocks_status ON guide_blocks(status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_guide_blocks_order ON guide_blocks(guide_id, block_order)");

    $db->exec("\n        CREATE TABLE IF NOT EXISTS beach_referral_placements (\n            id TEXT PRIMARY KEY,\n            beach_id TEXT NOT NULL,\n            anchor_key TEXT NOT NULL,\n            campaign_id TEXT NOT NULL,\n            block_id TEXT,\n            locale TEXT NOT NULL DEFAULT 'all',\n            enabled INTEGER NOT NULL DEFAULT 1,\n            display_order INTEGER NOT NULL DEFAULT 0,\n            created_at TEXT DEFAULT CURRENT_TIMESTAMP,\n            updated_at TEXT DEFAULT CURRENT_TIMESTAMP,\n            FOREIGN KEY (beach_id) REFERENCES beaches(id) ON DELETE CASCADE,\n            FOREIGN KEY (campaign_id) REFERENCES referral_campaigns(id) ON DELETE CASCADE,\n            FOREIGN KEY (block_id) REFERENCES referral_blocks(id) ON DELETE SET NULL\n        )\n    ");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_beach_referrals_beach_anchor ON beach_referral_placements(beach_id, anchor_key)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_beach_referrals_campaign ON beach_referral_placements(campaign_id)");

    $db->exec("\n        CREATE TABLE IF NOT EXISTS referral_clicks (\n            id TEXT PRIMARY KEY,\n            provider_id TEXT NOT NULL,\n            campaign_id TEXT NOT NULL,\n            block_id TEXT,\n            page_type TEXT,\n            page_slug TEXT,\n            placement_key TEXT,\n            locale TEXT,\n            anon_id TEXT,\n            user_id TEXT,\n            ip_hash TEXT,\n            ua_hash TEXT,\n            referrer TEXT,\n            meta_json TEXT NOT NULL DEFAULT '{}',\n            clicked_at TEXT DEFAULT CURRENT_TIMESTAMP,\n            FOREIGN KEY (provider_id) REFERENCES referral_providers(id) ON DELETE CASCADE,\n            FOREIGN KEY (campaign_id) REFERENCES referral_campaigns(id) ON DELETE CASCADE,\n            FOREIGN KEY (block_id) REFERENCES referral_blocks(id) ON DELETE SET NULL,\n            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL\n        )\n    ");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_referral_clicks_campaign ON referral_clicks(campaign_id, clicked_at)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_referral_clicks_page ON referral_clicks(page_type, page_slug)");

    $db->exec("\n        CREATE TABLE IF NOT EXISTS referral_conversions (\n            id TEXT PRIMARY KEY,\n            provider_id TEXT NOT NULL,\n            campaign_id TEXT,\n            external_conversion_id TEXT NOT NULL,\n            click_id TEXT,\n            booking_value REAL NOT NULL DEFAULT 0,\n            commission_value REAL NOT NULL DEFAULT 0,\n            currency TEXT NOT NULL DEFAULT 'USD',\n            booked_at TEXT,\n            imported_at TEXT DEFAULT CURRENT_TIMESTAMP,\n            raw_json TEXT,\n            created_at TEXT DEFAULT CURRENT_TIMESTAMP,\n            updated_at TEXT DEFAULT CURRENT_TIMESTAMP,\n            FOREIGN KEY (provider_id) REFERENCES referral_providers(id) ON DELETE CASCADE,\n            FOREIGN KEY (campaign_id) REFERENCES referral_campaigns(id) ON DELETE SET NULL,\n            FOREIGN KEY (click_id) REFERENCES referral_clicks(id) ON DELETE SET NULL,\n            UNIQUE (provider_id, external_conversion_id)\n        )\n    ");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_referral_conversions_campaign ON referral_conversions(campaign_id, booked_at)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_referral_conversions_imported ON referral_conversions(imported_at)");

    $db->exec("\n        CREATE TABLE IF NOT EXISTS referral_import_jobs (\n            id TEXT PRIMARY KEY,\n            provider_id TEXT,\n            source_type TEXT NOT NULL,\n            status TEXT NOT NULL DEFAULT 'running',\n            started_at TEXT DEFAULT CURRENT_TIMESTAMP,\n            finished_at TEXT,\n            summary_json TEXT,\n            error_log TEXT,\n            created_at TEXT DEFAULT CURRENT_TIMESTAMP,\n            updated_at TEXT DEFAULT CURRENT_TIMESTAMP,\n            FOREIGN KEY (provider_id) REFERENCES referral_providers(id) ON DELETE SET NULL\n        )\n    ");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_referral_import_jobs_provider ON referral_import_jobs(provider_id, started_at)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_referral_import_jobs_status ON referral_import_jobs(status)");

    $expediaId = null;
    $aviatorId = null;

    $existingExpedia = queryOne('SELECT id FROM referral_providers WHERE slug = :slug', [':slug' => 'expedia']);
    if (!$existingExpedia) {
        $expediaId = uuid();
        execute(
            'INSERT INTO referral_providers (id, slug, name, status, default_disclosure_en, default_disclosure_es) VALUES (:id, :slug, :name, :status, :en, :es)',
            [
                ':id' => $expediaId,
                ':slug' => 'expedia',
                ':name' => 'Expedia',
                ':status' => 'active',
                ':en' => 'This section contains Expedia referral links. We may earn a commission at no extra cost to you.',
                ':es' => 'Esta seccion contiene enlaces de referidos de Expedia. Podemos recibir una comision sin costo adicional para ti.',
            ]
        );
    } else {
        $expediaId = (string) $existingExpedia['id'];
    }

    $existingAviator = queryOne('SELECT id FROM referral_providers WHERE slug = :slug', [':slug' => 'aviator']);
    if (!$existingAviator) {
        $aviatorId = uuid();
        execute(
            'INSERT INTO referral_providers (id, slug, name, status, default_disclosure_en, default_disclosure_es) VALUES (:id, :slug, :name, :status, :en, :es)',
            [
                ':id' => $aviatorId,
                ':slug' => 'aviator',
                ':name' => 'Aviator',
                ':status' => 'active',
                ':en' => 'This section contains Aviator referral links. We may earn a commission at no extra cost to you.',
                ':es' => 'Esta seccion contiene enlaces de referidos de Aviator. Podemos recibir una comision sin costo adicional para ti.',
            ]
        );
    } else {
        $aviatorId = (string) $existingAviator['id'];
    }

    $seedCampaigns = [
        ['slug' => 'expedia-flights-sju', 'name' => 'Expedia Flights - SJU', 'link_type' => 'flight', 'destination_scope' => 'san_juan', 'target_url' => 'https://expedia.com/affiliate/3tT42Fx', 'status' => 'active'],
        ['slug' => 'expedia-hotels-sanjuan', 'name' => 'Expedia Hotels - San Juan', 'link_type' => 'hotel', 'destination_scope' => 'san_juan', 'target_url' => 'https://expedia.com/affiliate/sJjkwZV', 'status' => 'active'],
        ['slug' => 'expedia-hotels-rincon', 'name' => 'Expedia Hotels - Rincon', 'link_type' => 'hotel', 'destination_scope' => 'rincon', 'target_url' => 'https://expedia.com/affiliate/yFIDZZn', 'status' => 'active'],
        ['slug' => 'expedia-hotels-vieques', 'name' => 'Expedia Hotels - Vieques', 'link_type' => 'hotel', 'destination_scope' => 'vieques', 'target_url' => 'https://expedia.com/affiliate/PO7CY7y', 'status' => 'active'],
        ['slug' => 'expedia-hotels-culebra', 'name' => 'Expedia Hotels - Culebra', 'link_type' => 'hotel', 'destination_scope' => 'culebra', 'target_url' => 'https://expedia.com/affiliate/th6nDNZ', 'status' => 'active'],
        ['slug' => 'expedia-cars-sju', 'name' => 'Expedia Cars - SJU', 'link_type' => 'car', 'destination_scope' => 'san_juan', 'target_url' => 'https://expedia.com/affiliate/9aBFUtt', 'status' => 'active'],
        ['slug' => 'aviator-flights-global', 'name' => 'Aviator Flights - Global', 'link_type' => 'flight', 'destination_scope' => 'global', 'target_url' => '', 'status' => 'draft'],
    ];

    foreach ($seedCampaigns as $campaign) {
        $exists = queryOne('SELECT id FROM referral_campaigns WHERE slug = :slug', [':slug' => $campaign['slug']]);
        if ($exists) {
            continue;
        }

        $providerId = str_starts_with($campaign['slug'], 'aviator-') ? $aviatorId : $expediaId;
        execute(
            'INSERT INTO referral_campaigns (id, provider_id, slug, name, link_type, destination_scope, target_url, status, priority, utm_json)
             VALUES (:id, :provider_id, :slug, :name, :link_type, :destination_scope, :target_url, :status, :priority, :utm_json)',
            [
                ':id' => uuid(),
                ':provider_id' => $providerId,
                ':slug' => $campaign['slug'],
                ':name' => $campaign['name'],
                ':link_type' => $campaign['link_type'],
                ':destination_scope' => $campaign['destination_scope'],
                ':target_url' => $campaign['target_url'],
                ':status' => $campaign['status'],
                ':priority' => 100,
                ':utm_json' => '{}',
            ]
        );
    }

    $seedBlocks = [
        ['slug' => 'trip-planning-inline', 'block_type' => 'inline_card', 'label_en' => 'Plan your trip', 'label_es' => 'Planifica tu viaje', 'style_variant' => 'card'],
        ['slug' => 'booking-cta-strip', 'block_type' => 'comparison_strip', 'label_en' => 'Book your travel', 'label_es' => 'Reserva tu viaje', 'style_variant' => 'strip'],
        ['slug' => 'compact-cta-button', 'block_type' => 'cta_button', 'label_en' => 'Book now', 'label_es' => 'Reserva ahora', 'style_variant' => 'button'],
    ];

    foreach ($seedBlocks as $block) {
        $exists = queryOne('SELECT id FROM referral_blocks WHERE slug = :slug', [':slug' => $block['slug']]);
        if ($exists) {
            continue;
        }

        execute(
            'INSERT INTO referral_blocks (id, slug, block_type, label_en, label_es, style_variant, disclosure_mode, metadata_json, status)
             VALUES (:id, :slug, :block_type, :label_en, :label_es, :style_variant, :disclosure_mode, :metadata_json, :status)',
            [
                ':id' => uuid(),
                ':slug' => $block['slug'],
                ':block_type' => $block['block_type'],
                ':label_en' => $block['label_en'],
                ':label_es' => $block['label_es'],
                ':style_variant' => $block['style_variant'],
                ':disclosure_mode' => 'group',
                ':metadata_json' => '{}',
                ':status' => 'active',
            ]
        );
    }

    echo "\n✅ Migration completed successfully!\n";
} catch (Throwable $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
