<?php
/**
 * Admin Beach Image Upload API
 *
 * Endpoints:
 * POST (upload)     - Upload and optimize a new image
 * POST (delete)     - Delete an image
 * POST (reorder)    - Update image positions
 * POST (set-cover)  - Set an image as cover
 * POST (update-alt) - Update alt text
 * GET               - Get images for a beach
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/session.php';
session_start();
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/admin.php';
require_once APP_ROOT . '/inc/image-optimizer.php';

// Require admin authentication
if (!isAdmin()) {
    jsonResponse(['error' => 'Unauthorized'], 403);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    getImages();
} elseif ($method === 'POST') {
    $action = $_POST['action'] ?? 'upload';

    // Validate CSRF for all POST actions
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrf($csrfToken)) {
        jsonResponse(['error' => 'Invalid CSRF token'], 403);
    }

    switch ($action) {
        case 'upload':
            uploadImage();
            break;
        case 'delete':
            deleteImage();
            break;
        case 'reorder':
            reorderImages();
            break;
        case 'set-cover':
            setCoverImage();
            break;
        case 'update-alt':
            updateAltText();
            break;
        default:
            jsonResponse(['error' => 'Invalid action'], 400);
    }
} else {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

/**
 * Get all images for a beach
 */
function getImages()
{
    $beachId = $_GET['beach_id'] ?? '';

    if (!$beachId) {
        jsonResponse(['error' => 'Beach ID required'], 400);
    }

    $images = getBeachImages($beachId);

    // Add URLs to each image
    foreach ($images as &$image) {
        $image['urls'] = buildImageUrls($image['filename']);
        $image['file_size_formatted'] = formatFileSize($image['file_size']);
        $image['savings_formatted'] = formatFileSize($image['optimization_savings']);
    }

    jsonResponse(['images' => $images]);
}

/**
 * Upload and optimize a new image
 */
function uploadImage()
{
    $beachId = $_POST['beach_id'] ?? '';

    if (!$beachId) {
        jsonResponse(['error' => 'Beach ID required'], 400);
    }

    // Verify beach exists and get slug
    $beach = queryOne('SELECT id, slug, name FROM beaches WHERE id = :id', [':id' => $beachId]);
    if (!$beach) {
        jsonResponse(['error' => 'Beach not found'], 404);
    }

    // Check file upload
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File too large (exceeds server limit)',
            UPLOAD_ERR_FORM_SIZE => 'File too large',
            UPLOAD_ERR_PARTIAL => 'Upload incomplete',
            UPLOAD_ERR_NO_FILE => 'No file selected',
            UPLOAD_ERR_NO_TMP_DIR => 'Server configuration error',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
            UPLOAD_ERR_EXTENSION => 'Upload blocked by extension'
        ];
        $errorCode = $_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE;
        jsonResponse(['error' => $errors[$errorCode] ?? 'Upload failed'], 400);
    }

    $file = $_FILES['image'];

    // Validate file size (max 10MB)
    $maxSize = 10 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        jsonResponse(['error' => 'File must be less than 10MB'], 400);
    }

    // Validate image signature and MIME type
    $signature = detectImageSignature($file['tmp_name']);
    $mimeType = (string) ($signature['mime'] ?? '');

    if ($signature === null || !in_array($mimeType, ALLOWED_IMAGE_TYPES, true)) {
        jsonResponse(['error' => 'Only JPEG, PNG, WebP, and GIF images are allowed'], 400);
    }

    // Process image through optimizer
    $result = optimizeImage($file['tmp_name'], $beach['slug'], $file['name']);

    if (isset($result['error'])) {
        jsonResponse(['error' => $result['error']], 400);
    }

    // Get next position
    $maxPos = queryOne("
        SELECT MAX(position) as max_pos FROM beach_images WHERE beach_id = :beach_id
    ", [':beach_id' => $beachId]);
    $position = ($maxPos['max_pos'] ?? -1) + 1;

    // Check if this is the first image (should be cover)
    $imageCount = queryOne("
        SELECT COUNT(*) as count FROM beach_images WHERE beach_id = :beach_id
    ", [':beach_id' => $beachId]);
    $isCover = ($imageCount['count'] ?? 0) == 0 ? 1 : 0;

    // Insert into database
    $insertResult = execute("
        INSERT INTO beach_images (
            beach_id, filename, original_filename, original_format,
            file_size, original_size, mime_type, width, height,
            position, is_cover, optimization_savings, created_at, uploaded_by
        ) VALUES (
            :beach_id, :filename, :original_filename, :original_format,
            :file_size, :original_size, 'image/webp', :width, :height,
            :position, :is_cover, :optimization_savings, datetime('now'), :uploaded_by
        )
    ", [
        ':beach_id' => $beachId,
        ':filename' => $result['filename'],
        ':original_filename' => $result['original_filename'],
        ':original_format' => $result['original_format'],
        ':file_size' => $result['optimization']['optimized_size'],
        ':original_size' => $result['optimization']['original_size'],
        ':width' => $result['width'],
        ':height' => $result['height'],
        ':position' => $position,
        ':is_cover' => $isCover,
        ':optimization_savings' => $result['optimization']['savings_bytes'],
        ':uploaded_by' => $_SESSION['user_id'] ?? null
    ]);

    if (!$insertResult) {
        // Clean up files on database failure
        deleteImageFiles($result['filename']);
        jsonResponse(['error' => 'Failed to save image record'], 500);
    }

    $imageId = getDb()->lastInsertRowID();

    // If this is the cover image, update the beach's cover_image field
    if ($isCover) {
        updateBeachCoverImage($beachId, $result['urls']['medium']);
    }

    jsonResponse([
        'success' => true,
        'message' => 'Image uploaded and optimized',
        'image' => [
            'id' => $imageId,
            'filename' => $result['filename'],
            'urls' => $result['urls'],
            'is_cover' => $isCover,
            'position' => $position,
            'width' => $result['width'],
            'height' => $result['height'],
            'optimization' => [
                'original_size' => $result['optimization']['original_size'],
                'optimized_size' => $result['optimization']['optimized_size'],
                'savings_percent' => $result['optimization']['savings_percent'],
                'savings_bytes' => $result['optimization']['savings_bytes'],
                'original_size_formatted' => formatFileSize($result['optimization']['original_size']),
                'optimized_size_formatted' => formatFileSize($result['optimization']['optimized_size']),
                'savings_formatted' => formatFileSize($result['optimization']['savings_bytes']),
            ]
        ]
    ]);
}

/**
 * Delete an image
 */
function deleteImage()
{
    $imageId = intval($_POST['image_id'] ?? 0);

    if (!$imageId) {
        jsonResponse(['error' => 'Image ID required'], 400);
    }

    // Get image record — fetch only needed columns to avoid leaking data
    $image = queryOne('SELECT id, beach_id, filename, is_cover FROM beach_images WHERE id = :id', [':id' => $imageId]);

    if (!$image) {
        jsonResponse(['error' => 'Image not found'], 404);
    }

    // Validate filename before filesystem operation (defense in depth)
    $filename = (string) $image['filename'];
    if ($filename === '' || str_contains($filename, '/') || str_contains($filename, '..')) {
        jsonResponse(['error' => 'Invalid image record'], 400);
    }

    // Delete files
    deleteImageFiles($filename);

    // Delete database record
    execute('DELETE FROM beach_images WHERE id = :id', [':id' => $imageId]);

    // If this was the cover image, set a new one
    if ($image['is_cover']) {
        $newCover = queryOne("
            SELECT id, filename FROM beach_images
            WHERE beach_id = :beach_id
            ORDER BY position ASC
            LIMIT 1
        ", [':beach_id' => $image['beach_id']]);

        if ($newCover) {
            execute('UPDATE beach_images SET is_cover = 1 WHERE id = :id', [':id' => $newCover['id']]);
            $urls = buildImageUrls($newCover['filename']);
            updateBeachCoverImage($image['beach_id'], $urls['medium']);
        } else {
            // No images left, set placeholder
            updateBeachCoverImage($image['beach_id'], '/images/beaches/placeholder-beach.webp');
        }
    }

    jsonResponse(['success' => true, 'message' => 'Image deleted']);
}

/**
 * Reorder images
 */
function reorderImages()
{
    $beachId = $_POST['beach_id'] ?? '';
    $order = $_POST['order'] ?? '';

    if (!$beachId || !$order) {
        jsonResponse(['error' => 'Beach ID and order required'], 400);
    }

    // Verify beach exists
    $beach = queryOne('SELECT id FROM beaches WHERE id = :id', [':id' => $beachId]);
    if (!$beach) {
        jsonResponse(['error' => 'Beach not found'], 404);
    }

    // Parse order (comma-separated image IDs), filter out zero/invalid
    $imageIds = array_filter(array_map('intval', explode(',', $order)));
    if (empty($imageIds)) {
        jsonResponse(['error' => 'No valid image IDs provided'], 400);
    }

    $db = getDb();

    foreach ($imageIds as $position => $imageId) {
        $stmt = $db->prepare('UPDATE beach_images SET position = :position WHERE id = :id AND beach_id = :beach_id');
        $stmt->bindValue(':position', $position, SQLITE3_INTEGER);
        $stmt->bindValue(':id', $imageId, SQLITE3_INTEGER);
        $stmt->bindValue(':beach_id', $beachId, SQLITE3_TEXT);
        $stmt->execute();
    }

    jsonResponse(['success' => true, 'message' => 'Order updated']);
}

/**
 * Set cover image
 */
function setCoverImage()
{
    $imageId = intval($_POST['image_id'] ?? 0);

    if (!$imageId) {
        jsonResponse(['error' => 'Image ID required'], 400);
    }

    // Get image record
    $image = queryOne('SELECT * FROM beach_images WHERE id = :id', [':id' => $imageId]);

    if (!$image) {
        jsonResponse(['error' => 'Image not found'], 404);
    }

    $db = getDb();

    // Clear existing cover for this beach
    $stmt = $db->prepare('UPDATE beach_images SET is_cover = 0 WHERE beach_id = :beach_id');
    $stmt->bindValue(':beach_id', $image['beach_id'], SQLITE3_TEXT);
    $stmt->execute();

    // Set new cover
    $stmt = $db->prepare('UPDATE beach_images SET is_cover = 1 WHERE id = :id');
    $stmt->bindValue(':id', $imageId, SQLITE3_INTEGER);
    $stmt->execute();

    // Update beach's cover_image field
    $urls = buildImageUrls($image['filename']);
    updateBeachCoverImage($image['beach_id'], $urls['medium']);

    jsonResponse(['success' => true, 'message' => 'Cover image updated']);
}

/**
 * Update alt text
 */
function updateAltText()
{
    $imageId = intval($_POST['image_id'] ?? 0);
    $altText = trim($_POST['alt_text'] ?? '');

    if (!$imageId) {
        jsonResponse(['error' => 'Image ID required'], 400);
    }

    // Strip HTML tags and limit length to prevent stored XSS / abuse
    $altText = strip_tags($altText);
    $altText = mb_substr($altText, 0, 500);

    // Verify image exists before updating
    $image = queryOne('SELECT id, beach_id FROM beach_images WHERE id = :id', [':id' => $imageId]);
    if (!$image) {
        jsonResponse(['error' => 'Image not found'], 404);
    }

    execute('UPDATE beach_images SET alt_text = :alt_text WHERE id = :id', [
        ':alt_text' => $altText !== '' ? $altText : null,
        ':id' => $imageId
    ]);

    jsonResponse(['success' => true, 'message' => 'Alt text updated']);
}

/**
 * Update the beach's cover_image field
 */
function updateBeachCoverImage(string $beachId, string $imageUrl): void
{
    execute('UPDATE beaches SET cover_image = :cover_image, updated_at = datetime(\'now\') WHERE id = :id', [
        ':cover_image' => $imageUrl,
        ':id' => $beachId
    ]);
}
