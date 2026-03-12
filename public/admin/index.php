<?php
/**
 * Admin Dashboard
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

$pageTitle = 'Dashboard';
$pageSubtitle = 'Overview of your AJI site';

include __DIR__ . '/components/header.php';

$stats = getAdminStats();
$recentActivity = getRecentActivity(10);
?>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Beaches -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Beaches</p>
                <p class="text-3xl font-bold text-gray-900 mt-1"><?= number_format($stats['total_beaches']) ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                <span class="text-2xl">🏖️</span>
            </div>
        </div>
        <div class="mt-4 flex items-center gap-4 text-sm">
            <span class="text-green-600"><?= $stats['published_beaches'] ?> published</span>
            <span class="text-gray-400"><?= $stats['draft_beaches'] ?> drafts</span>
        </div>
    </div>

    <!-- Total Users -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Registered Users</p>
                <p class="text-3xl font-bold text-gray-900 mt-1"><?= number_format($stats['total_users']) ?></p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <span class="text-2xl">👥</span>
            </div>
        </div>
        <a href="/admin/users" class="mt-4 text-sm text-blue-600 hover:text-blue-700 inline-block">
            Manage users →
        </a>
    </div>

    <!-- Reviews -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">User Reviews</p>
                <p class="text-3xl font-bold text-gray-900 mt-1"><?= number_format($stats['total_reviews']) ?></p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                <span class="text-2xl">⭐</span>
            </div>
        </div>
        <?php if ($stats['pending_reviews'] > 0): ?>
        <a href="/admin/reviews?status=pending" class="mt-4 text-sm text-yellow-600 hover:text-yellow-700 inline-block">
            <?= $stats['pending_reviews'] ?> pending review →
        </a>
        <?php else: ?>
        <p class="mt-4 text-sm text-gray-400">All reviews moderated</p>
        <?php endif; ?>
    </div>

    <!-- Favorites -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Saved Favorites</p>
                <p class="text-3xl font-bold text-gray-900 mt-1"><?= number_format($stats['total_favorites']) ?></p>
            </div>
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                <span class="text-2xl">❤️</span>
            </div>
        </div>
        <p class="mt-4 text-sm text-gray-400">Total user favorites</p>
    </div>
</div>

<!-- Quick Add from Google Maps -->
<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>
        <div>
            <h3 class="font-semibold text-gray-900">Quick Add from Google Maps</h3>
            <p class="text-sm text-gray-500">Paste a Google Maps URL to auto-create a beach</p>
        </div>
    </div>

    <div class="flex gap-2">
        <input type="text"
               id="quick-add-url"
               placeholder="https://maps.google.com/..."
               class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm">
        <button id="quick-add-btn"
                class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm font-medium flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Beach
        </button>
    </div>

    <div id="quick-add-status" class="mt-4 hidden"></div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Recent Activity -->
    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="font-semibold text-gray-900">Recent Activity</h2>
        </div>
        <div class="divide-y divide-gray-100">
            <?php if (empty($recentActivity)): ?>
            <div class="p-6 text-center text-gray-500">
                No recent activity
            </div>
            <?php else: ?>
            <?php foreach ($recentActivity as $activity): ?>
            <a href="<?= h($activity['link']) ?>" class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50">
                <div class="w-10 h-10 rounded-full flex items-center justify-center <?= $activity['type'] === 'review' ? 'bg-yellow-100' : 'bg-green-100' ?>">
                    <?= $activity['type'] === 'review' ? '⭐' : '👤' ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-900 truncate"><?= h($activity['message']) ?></p>
                    <p class="text-xs text-gray-500"><?= timeAgo($activity['time']) ?></p>
                </div>
            </a>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="font-semibold text-gray-900">Quick Actions</h2>
        </div>
        <div class="p-6 space-y-4">
            <a href="/admin/beaches?action=new"
               class="flex items-center gap-4 p-4 rounded-lg border-2 border-dashed border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-colors">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-gray-900">Add New Beach</p>
                    <p class="text-sm text-gray-500">Create a new beach listing</p>
                </div>
            </a>

            <a href="/admin/reviews?status=pending"
               class="flex items-center gap-4 p-4 rounded-lg border-2 border-dashed border-gray-200 hover:border-yellow-300 hover:bg-yellow-50 transition-colors">
                <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center text-yellow-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-gray-900">Moderate Reviews</p>
                    <p class="text-sm text-gray-500">Approve or reject pending reviews</p>
                </div>
            </a>

            <a href="/sitemap.xml" target="_blank"
               class="flex items-center gap-4 p-4 rounded-lg border-2 border-dashed border-gray-200 hover:border-green-300 hover:bg-green-50 transition-colors">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center text-green-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-gray-900">View Sitemap</p>
                    <p class="text-sm text-gray-500">Check SEO sitemap</p>
                </div>
            </a>
        </div>
    </div>
</div>

<?php
$extraScripts = <<<'SCRIPT'
<script <?= cspNonceAttr() ?>>
document.addEventListener('DOMContentLoaded', function() {
    const urlInput = document.getElementById('quick-add-url');
    const addBtn = document.getElementById('quick-add-btn');
    const statusDiv = document.getElementById('quick-add-status');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    addBtn.addEventListener('click', async function() {
        const url = urlInput.value.trim();

        if (!url) {
            showStatus('error', 'Please enter a Google Maps URL');
            return;
        }

        if (!url.includes('google.com/maps') && !url.includes('goo.gl') && !url.includes('maps.app')) {
            showStatus('error', 'Please enter a valid Google Maps URL');
            return;
        }

        // Set loading state
        addBtn.disabled = true;
        addBtn.innerHTML = `
            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            Adding...
        `;

        try {
            const response = await fetch('/api/admin/quick-add-beach.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    url: url,
                    csrf_token: csrfToken
                })
            });

            const data = await response.json();

            if (data.success) {
                // Success - show beach card
                let warningsHtml = '';
                if (data.warnings && data.warnings.length > 0) {
                    warningsHtml = `
                        <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <p class="text-sm text-yellow-800 font-medium">Notes:</p>
                            <ul class="text-sm text-yellow-700 mt-1 list-disc list-inside">
                                ${data.warnings.map(w => `<li>${escapeHtml(w)}</li>`).join('')}
                            </ul>
                        </div>
                    `;
                }

                showStatus('success', `
                    <div class="flex items-start gap-4">
                        <img src="${escapeHtml(data.beach.cover_image)}" alt="" class="w-20 h-20 rounded-lg object-cover flex-shrink-0">
                        <div class="flex-1 min-w-0">
                            <h4 class="font-semibold text-gray-900">${escapeHtml(data.beach.name)}</h4>
                            <p class="text-sm text-gray-500">${escapeHtml(data.beach.municipality)}</p>
                            ${data.beach.google_rating ? `<p class="text-sm text-gray-500 mt-1">⭐ ${data.beach.google_rating} (${data.beach.google_review_count} reviews)</p>` : ''}
                            <div class="flex gap-2 mt-3">
                                <a href="${escapeHtml(data.edit_url)}" class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Edit Beach
                                </a>
                                <span class="inline-flex items-center px-3 py-1.5 bg-yellow-100 text-yellow-800 text-sm rounded-lg">
                                    Draft
                                </span>
                            </div>
                        </div>
                    </div>
                    ${warningsHtml}
                `);

                // Clear input
                urlInput.value = '';

            } else if (data.error === 'duplicate') {
                // Duplicate found
                showStatus('warning', `
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6 text-yellow-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div>
                            <p class="font-medium text-gray-900">Beach Already Exists</p>
                            <p class="text-sm text-gray-600 mt-1">"${escapeHtml(data.existing_beach.name)}" matches this location.</p>
                            <a href="${escapeHtml(data.existing_beach.edit_url)}" class="inline-flex items-center gap-1 text-sm text-blue-600 hover:text-blue-700 mt-2">
                                View existing beach →
                            </a>
                        </div>
                    </div>
                `);
            } else {
                // Other error
                showStatus('error', data.message || 'Failed to add beach');
            }

        } catch (err) {
            showStatus('error', 'Network error. Please try again.');
            console.error('Quick add error:', err);
        } finally {
            // Reset button
            addBtn.disabled = false;
            addBtn.innerHTML = `
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Beach
            `;
        }
    });

    // Allow Enter key to submit
    urlInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            addBtn.click();
        }
    });

    function showStatus(type, html) {
        const bgColors = {
            success: 'bg-green-50 border-green-200',
            error: 'bg-red-50 border-red-200',
            warning: 'bg-yellow-50 border-yellow-200'
        };

        statusDiv.className = `mt-4 p-4 rounded-lg border ${bgColors[type] || bgColors.error}`;
        statusDiv.innerHTML = html;
        statusDiv.classList.remove('hidden');
    }

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
});
</script>
SCRIPT;
?>
<?php include __DIR__ . '/components/footer.php'; ?>
