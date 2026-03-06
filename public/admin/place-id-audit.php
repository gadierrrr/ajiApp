<?php
/**
 * Admin Place ID Audit Page
 *
 * Bulk check beaches against Google Place IDs to identify:
 * - Beaches without place_id
 * - Invalid/expired place_ids
 * - Name or coordinate mismatches
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

$pageTitle = 'Place ID Audit';
$pageSubtitle = 'Verify beach data against Google Places';

include __DIR__ . '/components/header.php';
?>

<div class="space-y-6">
    <!-- Audit Control Panel -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h3 class="font-semibold text-gray-900">Audit Status</h3>
                <p class="text-sm text-gray-500 mt-1" id="audit-status-text">Loading...</p>
            </div>
            <div class="flex items-center gap-3">
                <button id="start-audit-btn"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Start New Audit
                </button>
                <button id="run-batch-btn"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium flex items-center gap-2 hidden">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Process Next Batch
                </button>
                <button id="run-all-btn"
                        class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm font-medium flex items-center gap-2 hidden">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Run Full Audit
                </button>
            </div>
        </div>

        <!-- Progress Bar -->
        <div id="progress-container" class="mt-4 hidden">
            <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
                <span id="progress-text">Processing...</span>
                <span id="progress-percent">0%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div id="progress-bar" class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4" id="stats-grid">
        <div class="bg-white rounded-xl shadow-sm p-4">
            <p class="text-sm text-gray-500">Total Beaches</p>
            <p class="text-2xl font-bold text-gray-900" id="stat-total">-</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4">
            <p class="text-sm text-gray-500">Processed</p>
            <p class="text-2xl font-bold text-green-600" id="stat-processed">-</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4">
            <p class="text-sm text-gray-500">Issues Found</p>
            <p class="text-2xl font-bold text-red-600" id="stat-issues">-</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4">
            <p class="text-sm text-gray-500">Missing Place ID</p>
            <p class="text-2xl font-bold text-orange-600" id="stat-missing">-</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4">
            <p class="text-sm text-gray-500">Flagged</p>
            <p class="text-2xl font-bold text-yellow-600" id="stat-flagged">-</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4">
            <p class="text-sm text-gray-500">Resolved</p>
            <p class="text-2xl font-bold text-blue-600" id="stat-resolved">-</p>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="bg-white rounded-xl shadow-sm">
        <div class="border-b border-gray-200">
            <nav class="flex space-x-1 px-4" aria-label="Tabs">
                <button class="filter-tab active px-4 py-3 text-sm font-medium border-b-2 border-blue-600 text-blue-600" data-filter="all">
                    All
                </button>
                <button class="filter-tab px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700" data-filter="issues">
                    Issues
                </button>
                <button class="filter-tab px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700" data-filter="missing">
                    Missing ID
                </button>
                <button class="filter-tab px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700" data-filter="mismatch">
                    Mismatches
                </button>
                <button class="filter-tab px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700" data-filter="flagged">
                    Flagged
                </button>
                <button class="filter-tab px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700" data-filter="resolved">
                    Resolved
                </button>
            </nav>
        </div>

        <!-- Results Table -->
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Beach</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Issue</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Google Match</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody id="results-table" class="divide-y divide-gray-100">
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                            Loading audit results...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div id="pagination" class="px-4 py-3 border-t border-gray-200 flex items-center justify-between">
            <p class="text-sm text-gray-500" id="pagination-info">Showing 0 results</p>
            <div class="flex gap-2" id="pagination-buttons"></div>
        </div>
    </div>
</div>

<!-- Update Place ID Modal -->
<div id="update-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" data-action="closeUpdateModal"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-lg w-full p-6 relative">
            <button data-action="closeUpdateModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <h3 class="text-lg font-semibold text-gray-900 mb-4">Update Place ID</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Beach</label>
                    <p class="text-gray-900" id="modal-beach-name">-</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Place ID</label>
                    <p class="text-gray-500 font-mono text-sm" id="modal-current-id">-</p>
                </div>

                <div id="modal-suggested" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Suggested Place ID</label>
                    <div class="flex items-center gap-2">
                        <p class="text-green-600 font-mono text-sm flex-1" id="modal-suggested-id">-</p>
                        <button data-action="useSuggestedId" class="text-sm text-blue-600 hover:text-blue-700">Use this</button>
                    </div>
                    <p class="text-sm text-gray-500 mt-1" id="modal-suggested-name">-</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Place ID</label>
                    <input type="text"
                           id="modal-new-id"
                           placeholder="Enter Google Place ID..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm">
                    <p class="text-xs text-gray-500 mt-1">Leave empty to clear the place_id</p>
                </div>

                <div class="flex gap-3 pt-4">
                    <button data-action="closeUpdateModal" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button data-action="saveNewPlaceId" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extraScripts = <<<'SCRIPT'
<script <?= cspNonceAttr() ?>>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
let currentFilter = 'all';
let currentPage = 1;
let currentBeachId = null;
let isRunningBatch = false;

document.addEventListener('DOMContentLoaded', function() {
    loadAuditResults();

    // Filter tabs
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.filter-tab').forEach(t => {
                t.classList.remove('active', 'border-blue-600', 'text-blue-600');
                t.classList.add('border-transparent', 'text-gray-500');
            });
            this.classList.add('active', 'border-blue-600', 'text-blue-600');
            this.classList.remove('border-transparent', 'text-gray-500');

            currentFilter = this.dataset.filter;
            currentPage = 1;
            loadAuditResults();
        });
    });

    // Start audit button
    document.getElementById('start-audit-btn').addEventListener('click', startAudit);

    // Run batch button
    document.getElementById('run-batch-btn').addEventListener('click', () => runBatch(10));

    // Run all button
    document.getElementById('run-all-btn').addEventListener('click', runAllBatches);
});

async function loadAuditResults() {
    try {
        const response = await fetch(`/api/admin/audit-place-ids.php?filter=${currentFilter}&page=${currentPage}`);
        const data = await response.json();

        if (data.success) {
            updateStats(data.stats);
            renderResults(data.results);
            renderPagination(data);
            updateButtonVisibility(data.stats);
        }
    } catch (err) {
        console.error('Error loading audit results:', err);
        showToast('Failed to load audit results', 'error');
    }
}

function updateStats(stats) {
    document.getElementById('stat-total').textContent = stats.total_beaches;
    document.getElementById('stat-processed').textContent = stats.processed;
    document.getElementById('stat-issues').textContent = stats.issues;
    document.getElementById('stat-missing').textContent = stats.missing_place_id;
    document.getElementById('stat-flagged').textContent = stats.flagged;
    document.getElementById('stat-resolved').textContent = stats.resolved;

    // Update status text
    const statusText = document.getElementById('audit-status-text');
    if (stats.total === 0) {
        statusText.textContent = 'No audit has been run yet. Click "Start New Audit" to begin.';
    } else if (stats.pending > 0) {
        statusText.textContent = `Audit in progress: ${stats.processed} of ${stats.total} beaches processed (${stats.pending} remaining)`;
    } else {
        statusText.textContent = `Audit complete: ${stats.processed} beaches checked, ${stats.issues} issues found`;
    }
}

function updateButtonVisibility(stats) {
    const runBatchBtn = document.getElementById('run-batch-btn');
    const runAllBtn = document.getElementById('run-all-btn');

    if (stats.pending > 0) {
        runBatchBtn.classList.remove('hidden');
        runAllBtn.classList.remove('hidden');
    } else {
        runBatchBtn.classList.add('hidden');
        runAllBtn.classList.add('hidden');
    }
}

function renderResults(results) {
    const tbody = document.getElementById('results-table');

    if (results.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                    No results found for this filter
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = results.map(r => `
        <tr class="${r.flagged ? 'bg-yellow-50' : ''} ${r.resolved ? 'opacity-60' : ''}">
            <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                    <img src="${escapeHtml(r.cover_image || '/images/beaches/placeholder-beach.webp')}"
                         alt="" class="w-10 h-10 rounded-lg object-cover">
                    <div>
	                        <a href="/admin/beaches?action=edit&id=${r.beach_id}"
	                           class="font-medium text-gray-900 hover:text-blue-600">${escapeHtml(r.beach_name)}</a>
                        <p class="text-sm text-gray-500">${escapeHtml(r.municipality || '')}</p>
                    </div>
                </div>
            </td>
            <td class="px-4 py-3">
                ${getStatusBadge(r)}
            </td>
            <td class="px-4 py-3">
                ${r.issue_type ? `
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium ${getIssueClass(r.issue_type)}">
                        ${getIssueLabel(r.issue_type)}
                    </span>
                    <p class="text-xs text-gray-500 mt-1 max-w-xs truncate" title="${escapeHtml(r.issue_details || '')}">
                        ${escapeHtml(r.issue_details || '')}
                    </p>
                ` : '<span class="text-gray-400 text-sm">No issues</span>'}
            </td>
            <td class="px-4 py-3">
                ${r.google_name ? `
                    <p class="text-sm text-gray-900">${escapeHtml(r.google_name)}</p>
                    ${r.coord_distance_meters ? `<p class="text-xs text-gray-500">${Math.round(r.coord_distance_meters)}m away</p>` : ''}
                ` : '<span class="text-gray-400 text-sm">-</span>'}
            </td>
            <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                    ${!r.resolved ? `
                        <button data-action="openUpdateModal" data-action-args='["${r.beach_id}","${escapeHtml(r.beach_name)}","${escapeHtml(r.current_place_id || '')}","${escapeHtml(r.found_place_id || '')}","${escapeHtml(r.google_name || '')}"]'
                                class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded" title="Update Place ID">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <button data-action="toggleFlag" data-action-args='["${r.beach_id}",${r.flagged ? false : true}]'
                                class="p-1.5 ${r.flagged ? 'text-yellow-500' : 'text-gray-400 hover:text-yellow-500'} hover:bg-yellow-50 rounded" title="${r.flagged ? 'Remove flag' : 'Flag for review'}">
                            <svg class="w-4 h-4" fill="${r.flagged ? 'currentColor' : 'none'}" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>
                            </svg>
                        </button>
                        ${r.issue_type ? `
                            <button data-action="resolveIssue" data-action-args='["${r.beach_id}"]'
                                    class="p-1.5 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded" title="Mark as resolved">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </button>
                        ` : ''}
                    ` : `
                        <span class="text-xs text-green-600">Resolved</span>
                    `}
                    <a href="https://www.google.com/maps/search/?api=1&query=${r.lat},${r.lng}"
                       target="_blank"
                       class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded" title="View on Google Maps">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                    </a>
                </div>
            </td>
        </tr>
    `).join('');
}

function getStatusBadge(r) {
    if (r.resolved) {
        return '<span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-700">Resolved</span>';
    }
    if (r.flagged) {
        return '<span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-700">Flagged</span>';
    }
    if (r.status === 'pending') {
        return '<span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700">Pending</span>';
    }
    if (r.issue_type) {
        return '<span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-700">Needs Review</span>';
    }
    return '<span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-700">OK</span>';
}

function getIssueClass(type) {
    const classes = {
        'missing_place_id': 'bg-orange-100 text-orange-700',
        'invalid_place_id': 'bg-red-100 text-red-700',
        'name_mismatch': 'bg-yellow-100 text-yellow-700',
        'coord_mismatch': 'bg-purple-100 text-purple-700'
    };
    return classes[type] || 'bg-gray-100 text-gray-700';
}

function getIssueLabel(type) {
    const labels = {
        'missing_place_id': 'Missing ID',
        'invalid_place_id': 'Invalid ID',
        'name_mismatch': 'Name Mismatch',
        'coord_mismatch': 'Coord Mismatch'
    };
    return labels[type] || type;
}

function renderPagination(data) {
    const info = document.getElementById('pagination-info');
    const buttons = document.getElementById('pagination-buttons');

    const start = (data.page - 1) * data.per_page + 1;
    const end = Math.min(data.page * data.per_page, data.total_results);
    info.textContent = `Showing ${start}-${end} of ${data.total_results} results`;

    if (data.total_pages <= 1) {
        buttons.innerHTML = '';
        return;
    }

    let html = '';
    if (data.page > 1) {
        html += `<button data-action="goToPage" data-action-args='[${data.page - 1}]' class="px-3 py-1 border rounded hover:bg-gray-50">Prev</button>`;
    }
    html += `<span class="px-3 py-1 text-sm text-gray-600">Page ${data.page} of ${data.total_pages}</span>`;
    if (data.page < data.total_pages) {
        html += `<button data-action="goToPage" data-action-args='[${data.page + 1}]' class="px-3 py-1 border rounded hover:bg-gray-50">Next</button>`;
    }
    buttons.innerHTML = html;
}

function goToPage(page) {
    currentPage = page;
    loadAuditResults();
}

async function startAudit() {
    if (!confirm('This will clear any previous audit results and start fresh. Continue?')) {
        return;
    }

    try {
        const response = await fetch('/api/admin/audit-place-ids.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'start', csrf_token: csrfToken })
        });

        const data = await response.json();

        if (data.success) {
            showToast(`Audit started for ${data.total_beaches} beaches`, 'success');
            loadAuditResults();
        } else {
            showToast(data.error || 'Failed to start audit', 'error');
        }
    } catch (err) {
        showToast('Network error', 'error');
    }
}

async function runBatch(batchSize = 10) {
    if (isRunningBatch) return;
    isRunningBatch = true;

    const btn = document.getElementById('run-batch-btn');
    btn.disabled = true;
    btn.innerHTML = `
        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        Processing...
    `;

    try {
        const response = await fetch('/api/admin/audit-place-ids.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'batch', batch_size: batchSize, csrf_token: csrfToken })
        });

        const data = await response.json();

        if (data.success) {
            showToast(`Processed ${data.processed} beaches, ${data.issues_found} issues found`, 'info');
            loadAuditResults();
        } else {
            showToast(data.error || 'Batch processing failed', 'error');
        }
    } catch (err) {
        showToast('Network error', 'error');
    } finally {
        isRunningBatch = false;
        btn.disabled = false;
        btn.innerHTML = `
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Process Next Batch
        `;
    }
}

async function runAllBatches() {
    if (!confirm('This will process all remaining beaches. This may take a while and use API quota. Continue?')) {
        return;
    }

    const progressContainer = document.getElementById('progress-container');
    const progressBar = document.getElementById('progress-bar');
    const progressText = document.getElementById('progress-text');
    const progressPercent = document.getElementById('progress-percent');

    progressContainer.classList.remove('hidden');
    document.getElementById('run-all-btn').disabled = true;
    document.getElementById('run-batch-btn').disabled = true;
    document.getElementById('start-audit-btn').disabled = true;

    let processed = 0;
    let total = 0;
    let hasMore = true;

    // Get initial count
    try {
        const initResponse = await fetch('/api/admin/audit-place-ids.php?filter=pending');
        const initData = await initResponse.json();
        total = initData.stats.pending;
    } catch (e) {
        total = 100; // fallback
    }

    while (hasMore) {
        try {
            const response = await fetch('/api/admin/audit-place-ids.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'batch', batch_size: 10, csrf_token: csrfToken })
            });

            const data = await response.json();

            if (data.success) {
                processed += data.processed;
                hasMore = data.remaining > 0;

                const percent = total > 0 ? Math.round((processed / total) * 100) : 0;
                progressBar.style.width = `${percent}%`;
                progressText.textContent = `Processed ${processed} of ${total} beaches...`;
                progressPercent.textContent = `${percent}%`;
            } else {
                hasMore = false;
                showToast(data.error || 'Processing stopped', 'error');
            }
        } catch (err) {
            hasMore = false;
            showToast('Network error - processing stopped', 'error');
        }

        // Small delay between batches
        await new Promise(r => setTimeout(r, 500));
    }

    progressContainer.classList.add('hidden');
    document.getElementById('run-all-btn').disabled = false;
    document.getElementById('run-batch-btn').disabled = false;
    document.getElementById('start-audit-btn').disabled = false;

    showToast('Audit complete!', 'success');
    loadAuditResults();
}

async function toggleFlag(beachId, flagged) {
    try {
        const response = await fetch('/api/admin/audit-place-ids.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'flag', beach_id: beachId, flagged: flagged, csrf_token: csrfToken })
        });

        const data = await response.json();
        if (data.success) {
            loadAuditResults();
        }
    } catch (err) {
        showToast('Failed to update flag', 'error');
    }
}

async function resolveIssue(beachId) {
    const resolution = prompt('Add resolution note (optional):');
    if (resolution === null) return;

    try {
        const response = await fetch('/api/admin/audit-place-ids.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'resolve', beach_id: beachId, resolution: resolution, csrf_token: csrfToken })
        });

        const data = await response.json();
        if (data.success) {
            showToast('Issue marked as resolved', 'success');
            loadAuditResults();
        }
    } catch (err) {
        showToast('Failed to resolve issue', 'error');
    }
}

function openUpdateModal(beachId, beachName, currentId, suggestedId, suggestedName) {
    currentBeachId = beachId;
    document.getElementById('modal-beach-name').textContent = beachName;
    document.getElementById('modal-current-id').textContent = currentId || '(none)';
    document.getElementById('modal-new-id').value = '';

    const suggestedDiv = document.getElementById('modal-suggested');
    if (suggestedId) {
        suggestedDiv.classList.remove('hidden');
        document.getElementById('modal-suggested-id').textContent = suggestedId;
        document.getElementById('modal-suggested-name').textContent = suggestedName || '';
    } else {
        suggestedDiv.classList.add('hidden');
    }

    document.getElementById('update-modal').classList.remove('hidden');
}

function closeUpdateModal() {
    document.getElementById('update-modal').classList.add('hidden');
    currentBeachId = null;
}

function useSuggestedId() {
    document.getElementById('modal-new-id').value = document.getElementById('modal-suggested-id').textContent;
}

async function saveNewPlaceId() {
    if (!currentBeachId) return;

    const newId = document.getElementById('modal-new-id').value.trim();

    try {
        const response = await fetch('/api/admin/audit-place-ids.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'update', beach_id: currentBeachId, place_id: newId, csrf_token: csrfToken })
        });

        const data = await response.json();
        if (data.success) {
            showToast('Place ID updated', 'success');
            closeUpdateModal();
            loadAuditResults();
        } else {
            showToast(data.error || 'Failed to update', 'error');
        }
    } catch (err) {
        showToast('Network error', 'error');
    }
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
</script>
SCRIPT;
?>
<?php include __DIR__ . '/components/footer.php'; ?>
