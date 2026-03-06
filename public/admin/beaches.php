<?php
/**
 * Admin - Beach Management
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/session.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Ensure CSRF token exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$action = $_GET['action'] ?? 'list';
$beachId = $_GET['id'] ?? '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once APP_ROOT . '/inc/session.php';
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    require_once APP_ROOT . '/inc/admin.php';
    requireAdmin();

    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'save') {
        $id = $_POST['id'] ?? '';
        $isNew = empty($id);

        if ($isNew) {
            $id = adminGenerateUuid();
        }

        $slug = slugify($_POST['name']) . '-' . substr($id, 0, 8);

        $db = getDb();
        $stmt = $db->prepare($isNew ? "
            INSERT INTO beaches (id, slug, name, municipality, lat, lng, place_id, description, cover_image,
                sargassum, surf, wind, access_label, notes, parking_details, safety_info, local_tips,
                best_time, publish_status, created_at, updated_at)
            VALUES (:id, :slug, :name, :municipality, :lat, :lng, :place_id, :description, :cover_image,
                :sargassum, :surf, :wind, :access_label, :notes, :parking_details, :safety_info, :local_tips,
                :best_time, :publish_status, datetime('now'), datetime('now'))
        " : "
            UPDATE beaches SET
                name = :name, municipality = :municipality, lat = :lat, lng = :lng, place_id = :place_id,
                description = :description, cover_image = :cover_image, sargassum = :sargassum,
                surf = :surf, wind = :wind, access_label = :access_label, notes = :notes,
                parking_details = :parking_details, safety_info = :safety_info, local_tips = :local_tips,
                best_time = :best_time, publish_status = :publish_status, updated_at = datetime('now')
            WHERE id = :id
        ");

        $stmt->bindValue(':id', $id, SQLITE3_TEXT);
        if ($isNew) {
            $stmt->bindValue(':slug', $slug, SQLITE3_TEXT);
        }
        $stmt->bindValue(':name', trim($_POST['name']), SQLITE3_TEXT);
        $stmt->bindValue(':municipality', trim($_POST['municipality']), SQLITE3_TEXT);
        $stmt->bindValue(':lat', floatval($_POST['lat']), SQLITE3_FLOAT);
        $stmt->bindValue(':lng', floatval($_POST['lng']), SQLITE3_FLOAT);
        $stmt->bindValue(':place_id', trim($_POST['place_id'] ?? '') ?: null, SQLITE3_TEXT);
        $stmt->bindValue(':description', trim($_POST['description'] ?? ''), SQLITE3_TEXT);
        $stmt->bindValue(':cover_image', trim($_POST['cover_image'] ?? '/images/beaches/placeholder-beach.jpg'), SQLITE3_TEXT);
        $stmt->bindValue(':sargassum', $_POST['sargassum'] ?? null, SQLITE3_TEXT);
        $stmt->bindValue(':surf', $_POST['surf'] ?? null, SQLITE3_TEXT);
        $stmt->bindValue(':wind', $_POST['wind'] ?? null, SQLITE3_TEXT);
        $stmt->bindValue(':access_label', trim($_POST['access_label'] ?? ''), SQLITE3_TEXT);
        $stmt->bindValue(':notes', trim($_POST['notes'] ?? ''), SQLITE3_TEXT);
        $stmt->bindValue(':parking_details', trim($_POST['parking_details'] ?? ''), SQLITE3_TEXT);
        $stmt->bindValue(':safety_info', trim($_POST['safety_info'] ?? ''), SQLITE3_TEXT);
        $stmt->bindValue(':local_tips', trim($_POST['local_tips'] ?? ''), SQLITE3_TEXT);
        $stmt->bindValue(':best_time', trim($_POST['best_time'] ?? ''), SQLITE3_TEXT);
        $stmt->bindValue(':publish_status', $_POST['publish_status'] ?? 'draft', SQLITE3_TEXT);

        if ($stmt->execute()) {
            // Handle tags
            $db->exec("DELETE FROM beach_tags WHERE beach_id = '$id'");
            $tags = array_filter(array_map('trim', explode(',', $_POST['tags'] ?? '')));
            foreach ($tags as $tag) {
                $tagSlug = slugify($tag);
                if ($tagSlug) {
                    $tagStmt = $db->prepare("INSERT INTO beach_tags (beach_id, tag) VALUES (:beach_id, :tag)");
                    $tagStmt->bindValue(':beach_id', $id, SQLITE3_TEXT);
                    $tagStmt->bindValue(':tag', $tagSlug, SQLITE3_TEXT);
                    $tagStmt->execute();
                }
            }

            // Handle amenities
            $db->exec("DELETE FROM beach_amenities WHERE beach_id = '$id'");
            $amenities = $_POST['amenities'] ?? [];
            foreach ($amenities as $amenity) {
                $amenityStmt = $db->prepare("INSERT INTO beach_amenities (beach_id, amenity) VALUES (:beach_id, :amenity)");
                $amenityStmt->bindValue(':beach_id', $id, SQLITE3_TEXT);
                $amenityStmt->bindValue(':amenity', $amenity, SQLITE3_TEXT);
                $amenityStmt->execute();
            }

            header('Location: /admin/beaches?saved=1');
            exit;
        }
    }

    if ($postAction === 'delete' && $beachId) {
        $db = getDb();
        $db->exec("DELETE FROM beach_tags WHERE beach_id = '$beachId'");
        $db->exec("DELETE FROM beach_amenities WHERE beach_id = '$beachId'");
        $db->exec("DELETE FROM beach_gallery WHERE beach_id = '$beachId'");
        $db->exec("DELETE FROM beaches WHERE id = '$beachId'");

        header('Location: /admin/beaches?deleted=1');
        exit;
    }
}

$pageTitle = 'Beaches';
$pageSubtitle = 'Manage beach listings';

if ($action === 'edit' || $action === 'new') {
    $pageTitle = $action === 'new' ? 'Add New Beach' : 'Edit Beach';
    $pageSubtitle = $action === 'new' ? 'Create a new beach listing' : 'Update beach information';
}

$pageActions = '<a href="/admin/beaches?action=new" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">+ Add Beach</a>';

include __DIR__ . '/components/header.php';

// Get municipalities for dropdown
$municipalities = array_column(
    query('SELECT DISTINCT municipality FROM beaches ORDER BY municipality'),
    'municipality'
);

// Available amenities
$availableAmenities = ['accessibility', 'food', 'lifeguard', 'parking', 'picnic-areas', 'restrooms', 'shade-structures', 'showers'];

if ($action === 'list'):
    // List beaches
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    $municipality = $_GET['municipality'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 20;
    $offset = ($page - 1) * $limit;

    $where = '1=1';
    $params = [];

    if ($search) {
        $where .= ' AND (name LIKE :search OR municipality LIKE :search)';
        $params[':search'] = "%$search%";
    }
    if ($status) {
        $where .= ' AND publish_status = :status';
        $params[':status'] = $status;
    }
    if ($municipality) {
        $where .= ' AND municipality = :municipality';
        $params[':municipality'] = $municipality;
    }

    $beaches = query("SELECT * FROM beaches WHERE $where ORDER BY name LIMIT $limit OFFSET $offset", $params);
    $total = queryOne("SELECT COUNT(*) as count FROM beaches WHERE $where", $params)['count'] ?? 0;
    $totalPages = ceil($total / $limit);
?>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <form method="GET" class="flex flex-wrap items-center gap-4">
        <input type="text" name="search" value="<?= h($search) ?>"
               placeholder="Search beaches..."
               class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">

        <select name="municipality" class="px-4 py-2 border border-gray-300 rounded-lg">
            <option value="">All Municipalities</option>
            <?php foreach ($municipalities as $m): ?>
            <option value="<?= h($m) ?>" <?= $municipality === $m ? 'selected' : '' ?>><?= h($m) ?></option>
            <?php endforeach; ?>
        </select>

        <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg">
            <option value="">All Status</option>
            <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
            <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
        </select>

        <button type="submit" class="bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg font-medium">Filter</button>
        <?php if ($search || $status || $municipality): ?>
        <a href="/admin/beaches" class="text-gray-500 hover:text-gray-700">Clear</a>
        <?php endif; ?>
    </form>
</div>

<?php if (isset($_GET['saved'])): ?>
<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">Beach saved successfully!</div>
<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>
<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">Beach deleted.</div>
<?php endif; ?>

<!-- Beach List -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Beach</th>
                <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Municipality</th>
                <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Rating</th>
                <th class="text-right px-6 py-3 text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <?php foreach ($beaches as $beach): ?>
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <img src="<?= h($beach['cover_image']) ?>" alt="" class="w-12 h-12 rounded-lg object-cover">
                        <div>
                            <p class="font-medium text-gray-900"><?= h($beach['name']) ?></p>
                            <p class="text-sm text-gray-500"><?= h($beach['slug']) ?></p>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 text-gray-600"><?= h($beach['municipality']) ?></td>
                <td class="px-6 py-4">
                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full <?= $beach['publish_status'] === 'published' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' ?>">
                        <?= h($beach['publish_status']) ?>
                    </span>
                </td>
                <td class="px-6 py-4 text-gray-600">
                    <?php if ($beach['google_rating']): ?>
                    ⭐ <?= number_format($beach['google_rating'], 1) ?>
                    <?php else: ?>
                    <span class="text-gray-400">—</span>
                    <?php endif; ?>
                </td>
                <td class="px-6 py-4 text-right">
                    <a href="/beach/<?= h($beach['slug']) ?>" target="_blank" class="text-gray-400 hover:text-gray-600 mr-3" title="View">
                        <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </a>
                    <a href="/admin/beaches?action=edit&id=<?= h($beach['id']) ?>" class="text-blue-600 hover:text-blue-700 mr-3">Edit</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
    <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
        <p class="text-sm text-gray-500">Showing <?= $offset + 1 ?>-<?= min($offset + $limit, $total) ?> of <?= $total ?> beaches</p>
        <div class="flex gap-2">
            <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&municipality=<?= urlencode($municipality) ?>"
               class="px-3 py-1 border rounded hover:bg-gray-50">Previous</a>
            <?php endif; ?>
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&municipality=<?= urlencode($municipality) ?>"
               class="px-3 py-1 border rounded hover:bg-gray-50">Next</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php else:
    // Edit/New form
    $beach = null;
    $beachTags = [];
    $beachAmenities = [];

    if ($action === 'edit' && $beachId) {
        $beach = queryOne('SELECT * FROM beaches WHERE id = :id', [':id' => $beachId]);
        if (!$beach) {
            echo '<div class="bg-red-50 text-red-700 p-4 rounded-lg">Beach not found.</div>';
            include __DIR__ . '/components/footer.php';
            exit;
        }
        $beachTags = array_column(query('SELECT tag FROM beach_tags WHERE beach_id = :id', [':id' => $beachId]), 'tag');
        $beachAmenities = array_column(query('SELECT amenity FROM beach_amenities WHERE beach_id = :id', [':id' => $beachId]), 'amenity');
    }
?>

<form method="POST" class="space-y-6">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= h($beach['id'] ?? '') ?>">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Info -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="font-semibold text-gray-900 mb-4">Basic Information</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Beach Name *</label>
                        <input type="text" name="name" value="<?= h($beach['name'] ?? '') ?>" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Municipality *</label>
                            <input type="text" name="municipality" value="<?= h($beach['municipality'] ?? '') ?>" required
                                   list="municipalities"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <datalist id="municipalities">
                                <?php foreach ($municipalities as $m): ?>
                                <option value="<?= h($m) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Access Type</label>
                            <select name="access_label" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="">Select...</option>
                                <option value="Easy Access" <?= ($beach['access_label'] ?? '') === 'Easy Access' ? 'selected' : '' ?>>Easy Access</option>
                                <option value="Moderate" <?= ($beach['access_label'] ?? '') === 'Moderate' ? 'selected' : '' ?>>Moderate</option>
                                <option value="Difficult" <?= ($beach['access_label'] ?? '') === 'Difficult' ? 'selected' : '' ?>>Difficult</option>
                                <option value="4x4 Required" <?= ($beach['access_label'] ?? '') === '4x4 Required' ? 'selected' : '' ?>>4x4 Required</option>
                            </select>
                        </div>
                    </div>

                    <!-- Google Maps URL Coordinate Extractor -->
                    <div class="bg-blue-50 a11y-on-light-blue border border-blue-200 rounded-lg p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="text-sm font-medium text-blue-800">Extract Coordinates from Google Maps</span>
                        </div>
                        <div class="flex gap-2">
                            <input type="text" id="google-maps-url" placeholder="Paste Google Maps URL here..."
                                   class="flex-1 px-3 py-2 text-sm border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <button type="button" id="extract-coords-btn"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                                Extract
                            </button>
                        </div>
                        <div id="extract-status" class="mt-2 text-sm hidden"></div>
                        <p class="text-xs text-blue-600 mt-2">
                            Supports: Google Maps links, Place URLs, short URLs (goo.gl)
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Latitude *</label>
                            <input type="number" step="any" name="lat" id="lat-input" value="<?= h($beach['lat'] ?? '') ?>" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Longitude *</label>
                            <input type="number" step="any" name="lng" id="lng-input" value="<?= h($beach['lng'] ?? '') ?>" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <!-- Google Place ID (populated by coordinate extractor) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Google Place ID</label>
                        <input type="text" name="place_id" id="place-id-input" value="<?= h($beach['place_id'] ?? '') ?>"
                               placeholder="Auto-populated when extracting coordinates"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 bg-gray-50 text-gray-600 text-sm font-mono">
                        <p class="text-xs text-gray-500 mt-1">Used for fetching Google reviews and ratings</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" rows="4"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"><?= h($beach['description'] ?? '') ?></textarea>
                    </div>

                </div>
            </div>

            <!-- Beach Images -->
            <?php if ($action === 'edit' && $beach): ?>
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="font-semibold text-gray-900 mb-4">Beach Images</h2>

                <div id="image-manager" data-beach-id="<?= h($beach['id']) ?>" data-csrf-token="<?= h($_SESSION['csrf_token'] ?? '') ?>">
                    <!-- Drop Zone -->
                    <div id="image-drop-zone"
                         class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-colors mb-4">
                        <svg class="w-10 h-10 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-gray-600 mb-1">Drop images here or click to upload</p>
                        <p class="text-sm text-gray-400">JPEG, PNG, WebP, GIF (max 10MB)</p>
                        <p class="text-sm text-green-600 mt-1">Auto-optimized to WebP</p>
                    </div>

                    <input type="file" id="image-file-input" accept="image/jpeg,image/png,image/webp,image/gif" multiple class="hidden">

                    <!-- Upload Progress -->
                    <div id="upload-progress"></div>

                    <!-- Image Gallery -->
                    <div id="image-gallery" class="grid grid-cols-4 gap-3 mb-4">
                        <!-- Images loaded via JS -->
                    </div>

                    <!-- Stats -->
                    <div id="image-stats"></div>

                    <p class="text-xs text-gray-500 mt-2">
                        Drag images to reorder. Click star to set as cover image. First image is automatically the cover.
                    </p>
                </div>

                <!-- Hidden input to preserve cover_image for form submission -->
                <input type="hidden" name="cover_image" value="<?= h($beach['cover_image'] ?? '/images/beaches/placeholder-beach.webp') ?>">
            </div>
            <?php else: ?>
            <!-- For new beaches, show placeholder message -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="font-semibold text-gray-900 mb-4">Beach Images</h2>
                <div class="bg-blue-50 a11y-on-light-blue border border-blue-200 text-blue-700 px-4 py-3 rounded-lg">
                    <p>Save the beach first to upload images.</p>
                </div>
                <input type="hidden" name="cover_image" value="/images/beaches/placeholder-beach.webp">
            </div>
            <?php endif; ?>

            <!-- Conditions -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="font-semibold text-gray-900 mb-4">Beach Conditions</h2>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sargassum</label>
                        <select name="sargassum" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">Unknown</option>
                            <option value="none" <?= ($beach['sargassum'] ?? '') === 'none' ? 'selected' : '' ?>>None</option>
                            <option value="light" <?= ($beach['sargassum'] ?? '') === 'light' ? 'selected' : '' ?>>Light</option>
                            <option value="moderate" <?= ($beach['sargassum'] ?? '') === 'moderate' ? 'selected' : '' ?>>Moderate</option>
                            <option value="heavy" <?= ($beach['sargassum'] ?? '') === 'heavy' ? 'selected' : '' ?>>Heavy</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Surf</label>
                        <select name="surf" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">Unknown</option>
                            <option value="calm" <?= ($beach['surf'] ?? '') === 'calm' ? 'selected' : '' ?>>Calm</option>
                            <option value="small" <?= ($beach['surf'] ?? '') === 'small' ? 'selected' : '' ?>>Small</option>
                            <option value="medium" <?= ($beach['surf'] ?? '') === 'medium' ? 'selected' : '' ?>>Medium</option>
                            <option value="large" <?= ($beach['surf'] ?? '') === 'large' ? 'selected' : '' ?>>Large</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Wind</label>
                        <select name="wind" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">Unknown</option>
                            <option value="calm" <?= ($beach['wind'] ?? '') === 'calm' ? 'selected' : '' ?>>Calm</option>
                            <option value="light" <?= ($beach['wind'] ?? '') === 'light' ? 'selected' : '' ?>>Light</option>
                            <option value="moderate" <?= ($beach['wind'] ?? '') === 'moderate' ? 'selected' : '' ?>>Moderate</option>
                            <option value="strong" <?= ($beach['wind'] ?? '') === 'strong' ? 'selected' : '' ?>>Strong</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Additional Info -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="font-semibold text-gray-900 mb-4">Additional Information</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <textarea name="notes" rows="2"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"><?= h($beach['notes'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Parking Details</label>
                        <textarea name="parking_details" rows="2"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"><?= h($beach['parking_details'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Safety Information</label>
                        <textarea name="safety_info" rows="2"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"><?= h($beach['safety_info'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Local Tips</label>
                        <textarea name="local_tips" rows="2"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"><?= h($beach['local_tips'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Best Time to Visit</label>
                        <input type="text" name="best_time" value="<?= h($beach['best_time'] ?? '') ?>"
                               placeholder="e.g., Morning, Weekdays, Winter months"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Publish -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="font-semibold text-gray-900 mb-4">Publish</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="publish_status" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="draft" <?= ($beach['publish_status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="published" <?= ($beach['publish_status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Published</option>
                        </select>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg font-medium">
                        <?= $action === 'new' ? 'Create Beach' : 'Save Changes' ?>
                    </button>

                    <a href="/admin/beaches" class="block text-center text-gray-500 hover:text-gray-700">Cancel</a>
                </div>

                <?php if ($beach): ?>
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <button type="button" data-action="submitFormById" data-action-args='["delete-form"]' data-action-confirm="Are you sure you want to delete this beach?" data-on="click"
                            class="w-full text-red-600 hover:text-red-700 text-sm">
                        Delete Beach
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <!-- Tags -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="font-semibold text-gray-900 mb-4">Tags</h2>
                <input type="text" name="tags" value="<?= h(implode(', ', $beachTags)) ?>"
                       placeholder="family-friendly, surfing, snorkeling..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-500 mt-2">Comma-separated list of tags</p>
            </div>

            <!-- Amenities -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="font-semibold text-gray-900 mb-4">Amenities</h2>
                <div class="space-y-2">
                    <?php foreach ($availableAmenities as $amenity): ?>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="amenities[]" value="<?= h($amenity) ?>"
                               <?= in_array($amenity, $beachAmenities) ? 'checked' : '' ?>
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700"><?= ucwords(str_replace('-', ' ', $amenity)) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</form>

<?php if ($beach): ?>
<form id="delete-form" method="POST" class="hidden">
    <input type="hidden" name="action" value="delete">
</form>
<?php endif; ?>

<?php endif; ?>

<?php
// Add scripts for edit/new page
$extraScripts = '';

// Coordinate extraction script (for both new and edit)
if ($action === 'edit' || $action === 'new'):
    $extraScripts .= <<<'SCRIPT'
<script <?= cspNonceAttr() ?>>
document.addEventListener('DOMContentLoaded', function() {
    const urlInput = document.getElementById('google-maps-url');
    const extractBtn = document.getElementById('extract-coords-btn');
    const statusDiv = document.getElementById('extract-status');
    const latInput = document.getElementById('lat-input');
    const lngInput = document.getElementById('lng-input');
    const placeIdInput = document.getElementById('place-id-input');
    const csrfToken = document.querySelector('meta[name=\"csrf-token\"]')?.content || '';

    if (!extractBtn) return;

    extractBtn.addEventListener('click', async function() {
        const url = urlInput.value.trim();

        if (!url) {
            showStatus('Please enter a Google Maps URL', 'error');
            return;
        }

        // Show loading state
        extractBtn.disabled = true;
        extractBtn.innerHTML = `
            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Extracting...
        `;

        try {
            const response = await fetch('/api/extract-coordinates.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ url: url, csrf_token: csrfToken })
            });

            const data = await response.json();

            if (data.success) {
                // Update coordinate fields
                latInput.value = data.data.lat;
                lngInput.value = data.data.lng;

                // Update Place ID if available
                if (data.data.place_id && placeIdInput) {
                    placeIdInput.value = data.data.place_id;
                    placeIdInput.classList.add('ring-2', 'ring-green-500');
                    setTimeout(() => {
                        placeIdInput.classList.remove('ring-2', 'ring-green-500');
                    }, 2000);
                }

                // Highlight the coordinate fields
                latInput.classList.add('ring-2', 'ring-green-500');
                lngInput.classList.add('ring-2', 'ring-green-500');
                setTimeout(() => {
                    latInput.classList.remove('ring-2', 'ring-green-500');
                    lngInput.classList.remove('ring-2', 'ring-green-500');
                }, 2000);

                // Build status message
                let msg = `Coordinates extracted: ${data.data.lat}, ${data.data.lng}`;
                if (data.data.name) {
                    msg += ` (${data.data.name})`;
                }
                if (data.data.place_id) {
                    msg += ' + Place ID';
                }

                showStatus(msg, 'success');

                // Show warning if outside PR
                if (data.data.warning) {
                    setTimeout(() => showStatus(data.data.warning, 'warning'), 100);
                }
            } else {
                showStatus(data.error || 'Could not extract coordinates', 'error');
            }
        } catch (err) {
            console.error('Extract error:', err);
            showStatus('Network error. Please try again.', 'error');
        } finally {
            extractBtn.disabled = false;
            extractBtn.innerHTML = `
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Extract
            `;
        }
    });

    // Allow pressing Enter in the URL input
    urlInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            extractBtn.click();
        }
    });

    function showStatus(message, type) {
        statusDiv.classList.remove('hidden', 'text-green-700', 'text-red-700', 'text-yellow-700', 'bg-green-50', 'bg-red-50', 'bg-yellow-50');

        const colors = {
            success: ['text-green-700', 'bg-green-50'],
            error: ['text-red-700', 'bg-red-50'],
            warning: ['text-yellow-700', 'bg-yellow-50']
        };

        statusDiv.classList.add(...(colors[type] || colors.success), 'p-2', 'rounded');
        statusDiv.textContent = message;
    }
});
</script>
SCRIPT;
endif;

// Add admin images script for edit page only
if ($action === 'edit' && isset($beach)):
    $extraScripts .= '<script src="/assets/js/admin-images.js" ' . cspNonceAttr() . '></script>';
endif;
?>

<?php include __DIR__ . '/components/footer.php'; ?>
