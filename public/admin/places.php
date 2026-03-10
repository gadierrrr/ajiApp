<?php
/**
 * Admin - Generic Place Management
 *
 * Handles CRUD for any non-beach place type.
 * URL: /admin/places?type=river&action=list
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/constants.php';
require_once APP_ROOT . '/inc/place_types.php';
require_once APP_ROOT . '/inc/place_helpers.php';
require_once APP_ROOT . '/inc/session.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$type = trim($_GET['type'] ?? '');
if (!isValidPlaceType($type) || $type === 'beach') {
    header('Location: /admin/');
    exit;
}

$config = getPlaceTypeConfig($type);
$table = $config['table'];
$typeLabel = $config['label'];
$typeLabelPlural = $config['label_plural'];
$action = $_GET['action'] ?? 'list';
$placeId = $_GET['id'] ?? '';

// Common columns for all types
$commonColumns = ['name', 'municipality', 'lat', 'lng', 'place_id', 'description', 'cover_image', 'publish_status'];

// Type-specific columns
$typeColumns = [];
switch ($type) {
    case 'river':
        $typeColumns = ['water_clarity', 'current_strength', 'depth_estimate', 'swimmable', 'access_difficulty', 'best_season', 'notes', 'safety_info', 'local_tips'];
        break;
    case 'waterfall':
        $typeColumns = ['height_meters', 'num_tiers', 'swimmable', 'hike_difficulty', 'hike_distance_km', 'hike_time_minutes', 'water_clarity', 'best_season', 'notes', 'safety_info', 'local_tips'];
        break;
    case 'trail':
        $typeColumns = ['difficulty', 'distance_km', 'elevation_gain_m', 'estimated_time_minutes', 'trail_type', 'surface_type', 'dog_friendly', 'bike_friendly', 'shaded', 'best_season', 'notes', 'safety_info', 'local_tips'];
        break;
    case 'restaurant':
        $typeColumns = ['cuisine_type', 'price_range', 'phone', 'website', 'instagram', 'hours_json', 'reservations_url', 'delivery_available', 'takeout_available', 'outdoor_seating', 'notes'];
        break;
    case 'photo_spot':
        $typeColumns = ['best_light', 'best_time_of_day', 'tripod_recommended', 'drone_allowed', 'accessibility', 'viewpoint_type', 'instagram_hashtag', 'notes', 'local_tips'];
        break;
}

// Handle POST (save/delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once APP_ROOT . '/inc/admin.php';
    requireAdmin();

    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'save') {
        $id = $_POST['id'] ?? '';
        $isNew = empty($id);

        if ($isNew) {
            $id = adminGenerateUuid();
            $slug = slugify($_POST['name']) . '-' . substr($id, 0, 8);
        }

        $allColumns = array_merge($commonColumns, $typeColumns);
        $db = getDB();

        if ($isNew) {
            $setCols = ['id', 'slug'];
            $setPlaceholders = [':id', ':slug'];
            foreach ($allColumns as $col) {
                $setCols[] = $col;
                $setPlaceholders[] = ':' . $col;
            }
            $setCols[] = 'created_at';
            $setPlaceholders[] = "datetime('now')";
            $setCols[] = 'updated_at';
            $setPlaceholders[] = "datetime('now')";

            $sql = "INSERT INTO {$table} (" . implode(', ', $setCols) . ") VALUES (" . implode(', ', $setPlaceholders) . ")";
        } else {
            $setParts = [];
            foreach ($allColumns as $col) {
                $setParts[] = "{$col} = :{$col}";
            }
            $setParts[] = "updated_at = datetime('now')";
            $sql = "UPDATE {$table} SET " . implode(', ', $setParts) . " WHERE id = :id";
        }

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $id, SQLITE3_TEXT);
        if ($isNew) {
            $stmt->bindValue(':slug', $slug, SQLITE3_TEXT);
        }

        // Bind common columns
        $stmt->bindValue(':name', trim($_POST['name'] ?? ''), SQLITE3_TEXT);
        $stmt->bindValue(':municipality', trim($_POST['municipality'] ?? ''), SQLITE3_TEXT);
        $stmt->bindValue(':lat', floatval($_POST['lat'] ?? 0), SQLITE3_FLOAT);
        $stmt->bindValue(':lng', floatval($_POST['lng'] ?? 0), SQLITE3_FLOAT);
        $stmt->bindValue(':place_id', trim($_POST['place_id'] ?? '') ?: null, SQLITE3_TEXT);
        $stmt->bindValue(':description', trim($_POST['description'] ?? ''), SQLITE3_TEXT);
        $stmt->bindValue(':cover_image', trim($_POST['cover_image'] ?? '') ?: null, SQLITE3_TEXT);
        $stmt->bindValue(':publish_status', $_POST['publish_status'] ?? 'draft', SQLITE3_TEXT);

        // Bind type-specific columns
        foreach ($typeColumns as $col) {
            $val = $_POST[$col] ?? null;
            if (in_array($col, ['swimmable', 'dog_friendly', 'bike_friendly', 'shaded', 'delivery_available', 'takeout_available', 'outdoor_seating', 'tripod_recommended', 'drone_allowed'])) {
                $stmt->bindValue(':' . $col, intval($val ?? 0), SQLITE3_INTEGER);
            } elseif (in_array($col, ['height_meters', 'hike_distance_km', 'distance_km', 'elevation_gain_m'])) {
                $stmt->bindValue(':' . $col, $val !== null && $val !== '' ? floatval($val) : null, SQLITE3_FLOAT);
            } elseif (in_array($col, ['num_tiers', 'hike_time_minutes', 'estimated_time_minutes'])) {
                $stmt->bindValue(':' . $col, $val !== null && $val !== '' ? intval($val) : null, SQLITE3_INTEGER);
            } else {
                $stmt->bindValue(':' . $col, trim((string)($val ?? '')), SQLITE3_TEXT);
            }
        }

        if ($stmt->execute()) {
            // Handle tags
            $db->exec("DELETE FROM place_tags WHERE place_type = '{$type}' AND place_id = '{$id}'");
            $tags = array_filter(array_map('trim', explode(',', $_POST['tags'] ?? '')));
            foreach ($tags as $tag) {
                $tagSlug = slugify($tag);
                if ($tagSlug) {
                    execute(
                        'INSERT INTO place_tags (id, place_type, place_id, tag) VALUES (?, ?, ?, ?)',
                        [uuid(), $type, $id, $tagSlug]
                    );
                }
            }

            // Handle amenities
            $db->exec("DELETE FROM place_amenities WHERE place_type = '{$type}' AND place_id = '{$id}'");
            $amenities = $_POST['amenities'] ?? [];
            foreach ($amenities as $amenity) {
                execute(
                    'INSERT INTO place_amenities (id, place_type, place_id, amenity) VALUES (?, ?, ?, ?)',
                    [uuid(), $type, $id, $amenity]
                );
            }

            header("Location: /admin/places?type={$type}&saved=1");
            exit;
        }
    }

    if ($postAction === 'delete' && $placeId) {
        $db = getDB();
        $db->exec("DELETE FROM place_tags WHERE place_type = '{$type}' AND place_id = '{$placeId}'");
        $db->exec("DELETE FROM place_amenities WHERE place_type = '{$type}' AND place_id = '{$placeId}'");
        $db->exec("DELETE FROM place_gallery WHERE place_type = '{$type}' AND place_id = '{$placeId}'");
        $db->exec("DELETE FROM {$table} WHERE id = '{$placeId}'");

        header("Location: /admin/places?type={$type}&deleted=1");
        exit;
    }
}

// Page meta
$pageTitle = $typeLabelPlural;
$pageSubtitle = "Manage {$typeLabelPlural}";

if ($action === 'edit' || $action === 'new') {
    $pageTitle = $action === 'new' ? "Add New {$typeLabel}" : "Edit {$typeLabel}";
    $pageSubtitle = $action === 'new' ? "Create a new {$typeLabel} listing" : "Update {$typeLabel} information";
}

$pageActions = '<a href="/admin/places?type=' . h($type) . '&action=new" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">+ Add ' . h($typeLabel) . '</a>';

include __DIR__ . '/components/header.php';

$tagVocab = getPlaceTypeTags($type);
$amenityVocab = getPlaceTypeAmenities($type);

if ($action === 'list'):
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 20;
    $offset = ($page - 1) * $limit;

    $where = '1=1';
    $params = [];

    if ($search) {
        $where .= ' AND (name LIKE :search OR municipality LIKE :search)';
        $params[':search'] = "%{$search}%";
    }
    if ($status) {
        $where .= ' AND publish_status = :status';
        $params[':status'] = $status;
    }

    $total = queryOne("SELECT COUNT(*) AS c FROM {$table} WHERE {$where}", $params)['c'] ?? 0;
    $places = query("SELECT id, name, municipality, publish_status, google_rating, created_at FROM {$table} WHERE {$where} ORDER BY name LIMIT {$limit} OFFSET {$offset}", $params) ?: [];
    $totalPages = max(1, ceil($total / $limit));
?>

<div class="mb-6 flex flex-wrap gap-3 items-center">
    <form method="GET" class="flex gap-2 items-center">
        <input type="hidden" name="type" value="<?= h($type) ?>">
        <input type="text" name="search" value="<?= h($search) ?>" placeholder="Search..." class="border rounded-lg px-3 py-2 text-sm">
        <select name="status" class="border rounded-lg px-3 py-2 text-sm">
            <option value="">All Status</option>
            <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
            <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
        </select>
        <button type="submit" class="bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg text-sm">Filter</button>
    </form>
    <span class="text-sm text-gray-500"><?= number_format($total) ?> <?= h(strtolower($typeLabelPlural)) ?></span>
</div>

<?php if (isset($_GET['saved'])): ?>
<div class="bg-green-50 text-green-700 px-4 py-3 rounded-lg mb-4"><?= h($typeLabel) ?> saved successfully.</div>
<?php endif; ?>
<?php if (isset($_GET['deleted'])): ?>
<div class="bg-red-50 text-red-700 px-4 py-3 rounded-lg mb-4"><?= h($typeLabel) ?> deleted.</div>
<?php endif; ?>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="text-left px-4 py-3 font-medium">Name</th>
                <th class="text-left px-4 py-3 font-medium">Municipality</th>
                <th class="text-left px-4 py-3 font-medium">Rating</th>
                <th class="text-left px-4 py-3 font-medium">Status</th>
                <th class="text-left px-4 py-3 font-medium">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            <?php foreach ($places as $p): ?>
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium"><?= h($p['name']) ?></td>
                <td class="px-4 py-3 text-gray-500"><?= h($p['municipality'] ?? '-') ?></td>
                <td class="px-4 py-3"><?= $p['google_rating'] ? number_format($p['google_rating'], 1) : '-' ?></td>
                <td class="px-4 py-3">
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $p['publish_status'] === 'published' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' ?>">
                        <?= h(ucfirst($p['publish_status'])) ?>
                    </span>
                </td>
                <td class="px-4 py-3">
                    <a href="/admin/places?type=<?= h($type) ?>&action=edit&id=<?= h($p['id']) ?>" class="text-blue-600 hover:underline">Edit</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($places)): ?>
            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No <?= h(strtolower($typeLabelPlural)) ?> found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
<div class="flex justify-center gap-2 mt-6">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <a href="?type=<?= h($type) ?>&page=<?= $i ?>&search=<?= h($search) ?>&status=<?= h($status) ?>"
       class="px-3 py-1 rounded <?= $i === $page ? 'bg-blue-600 text-white' : 'bg-gray-100 hover:bg-gray-200' ?> text-sm">
        <?= $i ?>
    </a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<?php elseif ($action === 'edit' || $action === 'new'):
    $place = null;
    $placeTags = [];
    $placeAmenities = [];

    if ($action === 'edit' && $placeId) {
        $place = queryOne("SELECT * FROM {$table} WHERE id = :id", [':id' => $placeId]);
        if (!$place) {
            echo '<div class="bg-red-50 text-red-700 px-4 py-3 rounded-lg">' . h($typeLabel) . ' not found.</div>';
            include __DIR__ . '/components/footer.php';
            exit;
        }
        $tagsResult = query('SELECT tag FROM place_tags WHERE place_type = ? AND place_id = ?', [$type, $placeId]);
        $placeTags = array_column($tagsResult ?: [], 'tag');
        $amenitiesResult = query('SELECT amenity FROM place_amenities WHERE place_type = ? AND place_id = ?', [$type, $placeId]);
        $placeAmenities = array_column($amenitiesResult ?: [], 'amenity');
    }
?>

<form method="POST" class="space-y-6">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= h($place['id'] ?? '') ?>">

    <div class="bg-white rounded-xl shadow p-6 space-y-4">
        <h3 class="text-lg font-semibold">Basic Information</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Name *</label>
                <input type="text" name="name" value="<?= h($place['name'] ?? '') ?>" required
                       class="w-full border rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Municipality</label>
                <select name="municipality" class="w-full border rounded-lg px-3 py-2 text-sm">
                    <option value="">Select...</option>
                    <?php foreach (MUNICIPALITIES as $mun): ?>
                    <option value="<?= h($mun) ?>" <?= ($place['municipality'] ?? '') === $mun ? 'selected' : '' ?>><?= h($mun) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Latitude</label>
                <input type="number" name="lat" step="any" value="<?= h($place['lat'] ?? '') ?>"
                       class="w-full border rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Longitude</label>
                <input type="number" name="lng" step="any" value="<?= h($place['lng'] ?? '') ?>"
                       class="w-full border rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Google Place ID</label>
                <input type="text" name="place_id" value="<?= h($place['place_id'] ?? '') ?>"
                       class="w-full border rounded-lg px-3 py-2 text-sm">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Description</label>
            <textarea name="description" rows="4" class="w-full border rounded-lg px-3 py-2 text-sm"><?= h($place['description'] ?? '') ?></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Cover Image URL</label>
                <input type="text" name="cover_image" value="<?= h($place['cover_image'] ?? '') ?>"
                       class="w-full border rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Status</label>
                <select name="publish_status" class="w-full border rounded-lg px-3 py-2 text-sm">
                    <option value="draft" <?= ($place['publish_status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="published" <?= ($place['publish_status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Type-specific fields -->
    <div class="bg-white rounded-xl shadow p-6 space-y-4">
        <h3 class="text-lg font-semibold"><?= h($typeLabel) ?> Details</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($typeColumns as $col):
                $val = $place[$col] ?? '';
                $label = ucfirst(str_replace('_', ' ', $col));
                $isBool = in_array($col, ['swimmable', 'dog_friendly', 'bike_friendly', 'shaded', 'delivery_available', 'takeout_available', 'outdoor_seating', 'tripod_recommended', 'drone_allowed']);
                $isTextarea = in_array($col, ['notes', 'safety_info', 'local_tips', 'hours_json']);
                $isSelect = false;
                $selectOptions = [];

                if ($col === 'difficulty' || $col === 'hike_difficulty' || $col === 'access_difficulty') {
                    $isSelect = true;
                    $selectOptions = ['easy', 'moderate', 'difficult', 'expert'];
                } elseif ($col === 'water_clarity') {
                    $isSelect = true;
                    $selectOptions = WATER_CLARITY_LEVELS;
                } elseif ($col === 'current_strength') {
                    $isSelect = true;
                    $selectOptions = CURRENT_STRENGTH_LEVELS;
                } elseif ($col === 'price_range') {
                    $isSelect = true;
                    $selectOptions = PRICE_RANGES;
                } elseif ($col === 'best_light') {
                    $isSelect = true;
                    $selectOptions = BEST_LIGHT_CONDITIONS;
                } elseif ($col === 'trail_type') {
                    $isSelect = true;
                    $selectOptions = ['out-and-back', 'loop', 'point-to-point'];
                } elseif ($col === 'surface_type') {
                    $isSelect = true;
                    $selectOptions = ['dirt', 'gravel', 'paved', 'rock', 'mixed'];
                }
            ?>
            <div <?= $isTextarea ? 'class="md:col-span-2 lg:col-span-3"' : '' ?>>
                <label class="block text-sm font-medium mb-1"><?= h($label) ?></label>
                <?php if ($isBool): ?>
                    <select name="<?= h($col) ?>" class="w-full border rounded-lg px-3 py-2 text-sm">
                        <option value="0" <?= !$val ? 'selected' : '' ?>>No</option>
                        <option value="1" <?= $val ? 'selected' : '' ?>>Yes</option>
                    </select>
                <?php elseif ($isSelect): ?>
                    <select name="<?= h($col) ?>" class="w-full border rounded-lg px-3 py-2 text-sm">
                        <option value="">Select...</option>
                        <?php foreach ($selectOptions as $opt): ?>
                        <option value="<?= h($opt) ?>" <?= (string)$val === (string)$opt ? 'selected' : '' ?>><?= h(ucfirst($opt)) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif ($isTextarea): ?>
                    <textarea name="<?= h($col) ?>" rows="3" class="w-full border rounded-lg px-3 py-2 text-sm"><?= h($val) ?></textarea>
                <?php else: ?>
                    <input type="text" name="<?= h($col) ?>" value="<?= h($val) ?>"
                           class="w-full border rounded-lg px-3 py-2 text-sm">
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Tags & Amenities -->
    <div class="bg-white rounded-xl shadow p-6 space-y-4">
        <h3 class="text-lg font-semibold">Tags & Amenities</h3>

        <div>
            <label class="block text-sm font-medium mb-1">Tags (comma-separated)</label>
            <input type="text" name="tags" value="<?= h(implode(', ', $placeTags)) ?>"
                   placeholder="<?= h(implode(', ', array_slice($tagVocab, 0, 5))) ?>..."
                   class="w-full border rounded-lg px-3 py-2 text-sm">
            <p class="text-xs text-gray-400 mt-1">Available: <?= h(implode(', ', $tagVocab)) ?></p>
        </div>

        <div>
            <label class="block text-sm font-medium mb-2">Amenities</label>
            <div class="flex flex-wrap gap-3">
                <?php foreach ($amenityVocab as $amenity): ?>
                <label class="inline-flex items-center gap-1.5 text-sm">
                    <input type="checkbox" name="amenities[]" value="<?= h($amenity) ?>"
                           <?= in_array($amenity, $placeAmenities) ? 'checked' : '' ?>>
                    <?= h(getPlaceAmenityLabel($type, $amenity)) ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg font-medium text-sm">
            Save <?= h($typeLabel) ?>
        </button>
        <a href="/admin/places?type=<?= h($type) ?>" class="text-gray-500 hover:text-gray-700 text-sm">Cancel</a>

        <?php if ($action === 'edit' && $placeId): ?>
        <form method="POST" class="ml-auto" onsubmit="return confirm('Delete this <?= h(strtolower($typeLabel)) ?>?')">
            <input type="hidden" name="action" value="delete">
            <button type="submit" class="text-red-600 hover:text-red-700 text-sm">Delete</button>
        </form>
        <?php endif; ?>
    </div>
</form>

<?php endif; ?>

<?php include __DIR__ . '/components/footer.php'; ?>
