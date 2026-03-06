<?php
/**
 * Backward compatibility wrapper around the provider-agnostic referral system.
 *
 * Legacy guide templates can keep using AFFILIATE_LINKS + affiliateCTA().
 */

if (defined('AFFILIATE_INCLUDED')) {
    return;
}
define('AFFILIATE_INCLUDED', true);

require_once __DIR__ . '/referrals.php';

const AFFILIATE_LINKS = [
    'flights_sju' => '/go?c=expedia-flights-sju',
    'hotels_sanjuan' => '/go?c=expedia-hotels-sanjuan',
    'hotels_rincon' => '/go?c=expedia-hotels-rincon',
    'hotels_vieques' => '/go?c=expedia-hotels-vieques',
    'hotels_culebra' => '/go?c=expedia-hotels-culebra',
    'cars_sju' => '/go?c=expedia-cars-sju',
];

function affiliateCampaignSlugByLegacyKey(string $key): string
{
    $map = [
        'flights_sju' => 'expedia-flights-sju',
        'hotels_sanjuan' => 'expedia-hotels-sanjuan',
        'hotels_rincon' => 'expedia-hotels-rincon',
        'hotels_vieques' => 'expedia-hotels-vieques',
        'hotels_culebra' => 'expedia-hotels-culebra',
        'cars_sju' => 'expedia-cars-sju',
    ];

    return $map[$key] ?? '';
}

/**
 * Legacy CTA renderer that now routes through /go when a matching campaign exists.
 */
function affiliateCTA(string $key, string $label, string $style = 'primary'): string
{
    $campaignSlug = affiliateCampaignSlugByLegacyKey($key);
    if ($campaignSlug !== '') {
        $campaign = referralGetCampaignBySlug($campaignSlug, true);
        if ($campaign) {
            return referralRenderCampaignCta(
                $campaign,
                $label,
                [
                    'page_type' => 'guide',
                    'page_slug' => '',
                    'placement' => 'legacy_affiliate',
                    'locale' => 'en',
                ],
                $style
            );
        }
    }

    // Fallback to direct URL to avoid breaking legacy pages when DB campaign isn't configured.
    $url = AFFILIATE_LINKS[$key] ?? '';
    if ($url === '') {
        return '';
    }

    $u = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    $l = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');

    if ($style === 'secondary') {
        $cls = 'inline-flex items-center gap-2 border border-white text-white hover:bg-white hover:text-blue-700 font-semibold px-5 py-2.5 rounded-lg transition-colors text-sm';
    } elseif ($style === 'highlight') {
        $cls = 'inline-flex items-center gap-2 bg-white text-blue-700 hover:bg-blue-50 font-bold px-5 py-2.5 rounded-lg transition-colors text-sm shadow-sm';
    } elseif ($style === 'yellow') {
        $cls = 'inline-flex items-center gap-2 bg-yellow-400 hover:bg-yellow-300 text-gray-900 font-semibold px-5 py-2.5 rounded-lg transition-colors text-sm shadow-sm';
    } elseif ($style === 'outline') {
        $cls = 'inline-flex items-center gap-2 border border-blue-600 text-blue-700 hover:bg-blue-50 font-semibold px-5 py-2.5 rounded-lg transition-colors text-sm';
    } else {
        $cls = 'inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2.5 rounded-lg transition-colors text-sm';
    }

    $partnerLabel = function_exists('__') ? __('common.opens_partner_site') : '(opens partner site)';
    return '<a href="' . $u . '" class="' . $cls . '" rel="nofollow sponsored noopener" target="_blank" aria-label="' . $l . ' ' . $partnerLabel . '">'
        . $l . ' &rarr;'
        . '</a>';
}
