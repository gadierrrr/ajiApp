<?php
/**
 * Beach Photos API
 *
 * POST: Upload a photo
 * GET: Get photos for a beach
 * DELETE: Remove a photo
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/session.php';
session_start();
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    getPhotos();
} elseif ($method === 'POST') {
    $action = $_POST['action'] ?? 'upload';
    switch ($action) {
        case 'delete':
            deletePhoto();
            break;
        case 'upload':
        default:
            uploadPhoto();
            break;
    }
} else {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

function getPhotos() {
    $beachId = $_GET['beach_id'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(50, max(1, intval($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;

    if (!$beachId) {
        jsonResponse(['error' => 'Beach ID required'], 400);
    }

    $photos = query("
        SELECT
            p.id, p.filename, p.caption, p.width, p.height, p.created_at,
            u.name as user_name, u.avatar_url,
            r.id as review_id, r.rating as review_rating
        FROM beach_photos p
        LEFT JOIN users u ON p.user_id = u.id
        LEFT JOIN beach_reviews r ON p.review_id = r.id
        WHERE p.beach_id = :beach_id AND p.status = 'published'
        ORDER BY p.is_featured DESC, p.created_at DESC
        LIMIT :limit OFFSET :offset
    ", [':beach_id' => $beachId, ':limit' => $limit, ':offset' => $offset]);

    $total = queryOne("
        SELECT COUNT(*) as count FROM beach_photos
        WHERE beach_id = :beach_id AND status = 'published'
    ", [':beach_id' => $beachId]);

    foreach ($photos as &$photo) {
        $photo['url'] = '/uploads/photos/' . $photo['filename'];
        $photo['thumb_url'] = '/uploads/photos/thumbs/' . $photo['filename'];
        $photo['time_ago'] = timeAgo($photo['created_at']);
    }

    if (isHtmx()) {
        header('Content-Type: text/html');
        if (empty($photos)) {
            echo '<div class="col-span-full text-center py-8 text-gray-500">
                <p>No photos yet. Be the first to share!</p>
            </div>';
            return;
        }
        foreach ($photos as $photo) {
            renderPhotoThumbnail($photo);
        }
        return;
    }

    jsonResponse([
        'photos' => $photos,
        'total' => $total['count'],
        'page' => $page,
        'pages' => ceil($total['count'] / $limit)
    ]);
}

function uploadPhoto() {
    if (!isAuthenticated()) {
        jsonResponse(['error' => 'Please sign in to upload photos'], 401);
    }

    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrf($csrfToken)) {
        jsonResponse(['error' => 'Invalid request'], 403);
    }

    $beachId = $_POST['beach_id'] ?? '';
    $reviewId = !empty($_POST['review_id']) ? intval($_POST['review_id']) : null;
    $caption = trim($_POST['caption'] ?? '');
    $userId = $_SESSION['user_id'];

    // Validate beach exists
    $beach = queryOne('SELECT id FROM beaches WHERE id = :id', [':id' => $beachId]);
    if (!$beach) {
        jsonResponse(['error' => 'Beach not found'], 404);
    }

    // If review_id provided, verify ownership
    if ($reviewId) {
        $review = queryOne("
            SELECT id FROM beach_reviews
            WHERE id = :id AND user_id = :user_id AND beach_id = :beach_id
        ", [':id' => $reviewId, ':user_id' => $userId, ':beach_id' => $beachId]);
        if (!$review) {
            $reviewId = null;
        }
    }

    // Check file upload
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File too large',
            UPLOAD_ERR_FORM_SIZE => 'File too large',
            UPLOAD_ERR_PARTIAL => 'Upload incomplete',
            UPLOAD_ERR_NO_FILE => 'No file selected',
            UPLOAD_ERR_NO_TMP_DIR => 'Server error',
            UPLOAD_ERR_CANT_WRITE => 'Server error',
            UPLOAD_ERR_EXTENSION => 'File type not allowed'
        ];
        $errorCode = $_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE;
        jsonResponse(['error' => $errors[$errorCode] ?? 'Upload failed'], 400);
    }

    $file = $_FILES['photo'];

    // Validate file signature and MIME type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $signature = detectImageSignature($file['tmp_name']);
    $mimeType = (string) ($signature['mime'] ?? '');

    if ($signature === null || !in_array($mimeType, $allowedTypes, true)) {
        jsonResponse(['error' => 'Only JPEG, PNG, and WebP images are allowed'], 400);
    }

    // Validate file size (max 10MB)
    $maxSize = 10 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        jsonResponse(['error' => 'File must be less than 10MB'], 400);
    }

    // Rate limit: max 20 photos per day per user
    $dailyCount = queryOne("
        SELECT COUNT(*) as count FROM beach_photos
        WHERE user_id = :user_id AND created_at > datetime('now', '-1 day')
    ", [':user_id' => $userId]);

    if ($dailyCount['count'] >= 20) {
        jsonResponse(['error' => 'Daily upload limit reached (20 photos)'], 429);
    }

    // Generate unique filename
    $ext = match($mimeType) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        default => 'jpg'
    };
    $filename = uniqid('beach_') . '_' . time() . '.' . $ext;

    $uploadDir = APP_ROOT . '/uploads/photos/';
    $thumbDir = $uploadDir . 'thumbs/';
    $uploadPath = $uploadDir . $filename;
    $thumbPath = $thumbDir . $filename;

    // Get image dimensions
    $imageInfo = @getimagesize($file['tmp_name']);
    if (!is_array($imageInfo)) {
        jsonResponse(['error' => 'Could not read image dimensions'], 400);
    }
    $width = $imageInfo[0] ?? 0;
    $height = $imageInfo[1] ?? 0;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        jsonResponse(['error' => 'Failed to save file'], 500);
    }

    // Create thumbnail
    createThumbnail($uploadPath, $thumbPath, 400, 400, $mimeType);

    // Insert record
    $result = execute("
        INSERT INTO beach_photos (beach_id, user_id, review_id, filename, original_filename, file_size, mime_type, width, height, caption, created_at)
        VALUES (:beach_id, :user_id, :review_id, :filename, :original_filename, :file_size, :mime_type, :width, :height, :caption, datetime('now'))
    ", [
        ':beach_id' => $beachId,
        ':user_id' => $userId,
        ':review_id' => $reviewId,
        ':filename' => $filename,
        ':original_filename' => $file['name'],
        ':file_size' => $file['size'],
        ':mime_type' => $mimeType,
        ':width' => $width,
        ':height' => $height,
        ':caption' => $caption ?: null
    ]);

    if ($result) {
        $photoId = getDB()->lastInsertRowID();
        jsonResponse([
            'success' => true,
            'message' => 'Photo uploaded!',
            'photo' => [
                'id' => $photoId,
                'filename' => $filename,
                'url' => '/uploads/photos/' . $filename,
                'thumb_url' => '/uploads/photos/thumbs/' . $filename
            ]
        ]);
    } else {
        // Clean up files on failure
        @unlink($uploadPath);
        @unlink($thumbPath);
        jsonResponse(['error' => 'Failed to save photo'], 500);
    }
}

function deletePhoto() {
    if (!isAuthenticated()) {
        jsonResponse(['error' => 'Please sign in'], 401);
    }

    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrf($csrfToken)) {
        jsonResponse(['error' => 'Invalid request'], 403);
    }

    $photoId = intval($_POST['photo_id'] ?? 0);
    $userId = $_SESSION['user_id'];

    // Verify ownership
    $photo = queryOne("
        SELECT id, filename FROM beach_photos
        WHERE id = :id AND user_id = :user_id
    ", [':id' => $photoId, ':user_id' => $userId]);

    if (!$photo) {
        jsonResponse(['error' => 'Photo not found or not authorized'], 404);
    }

    // Delete files
    $uploadDir = APP_ROOT . '/uploads/photos/';
    @unlink($uploadDir . $photo['filename']);
    @unlink($uploadDir . 'thumbs/' . $photo['filename']);

    // Delete record
    execute('DELETE FROM beach_photos WHERE id = :id', [':id' => $photoId]);

    jsonResponse(['success' => true, 'message' => 'Photo deleted']);
}

function createThumbnail($sourcePath, $destPath, $maxWidth, $maxHeight, $mimeType) {
    // Load source image
    $source = match($mimeType) {
        'image/jpeg' => imagecreatefromjpeg($sourcePath),
        'image/png' => imagecreatefrompng($sourcePath),
        'image/webp' => imagecreatefromwebp($sourcePath),
        default => null
    };

    if (!$source) {
        // Fall back to copying original
        copy($sourcePath, $destPath);
        return;
    }

    $origWidth = imagesx($source);
    $origHeight = imagesy($source);

    // Calculate new dimensions maintaining aspect ratio
    $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight);

    if ($ratio >= 1) {
        // Image is smaller than thumbnail size, just copy
        copy($sourcePath, $destPath);
        imagedestroy($source);
        return;
    }

    $newWidth = intval($origWidth * $ratio);
    $newHeight = intval($origHeight * $ratio);

    // Create thumbnail
    $thumb = imagecreatetruecolor($newWidth, $newHeight);

    // Preserve transparency for PNG
    if ($mimeType === 'image/png') {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
        imagefilledrectangle($thumb, 0, 0, $newWidth, $newHeight, $transparent);
    }

    imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

    // Save thumbnail
    switch ($mimeType) {
        case 'image/jpeg':
            imagejpeg($thumb, $destPath, 85);
            break;
        case 'image/png':
            imagepng($thumb, $destPath, 8);
            break;
        case 'image/webp':
            imagewebp($thumb, $destPath, 85);
            break;
    }

    imagedestroy($source);
    imagedestroy($thumb);
}

function renderPhotoThumbnail($photo) {
    ?>
    <button data-action="openPhotoModal" data-action-args='["<?= h($photo['filename']) ?>","<?= h($photo['caption'] ?? '') ?>"]'
            class="aspect-square rounded-lg overflow-hidden hover:opacity-90 transition-opacity group relative">
        <img src="<?= h($photo['thumb_url']) ?>"
             alt="<?= h($photo['caption'] ?? 'Beach photo') ?>"
             class="w-full h-full object-cover"
             loading="lazy">
        <?php if ($photo['user_name']): ?>
        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent p-2 opacity-0 group-hover:opacity-100 transition-opacity">
            <span class="text-white text-xs truncate block"><?= h($photo['user_name']) ?></span>
        </div>
        <?php endif; ?>
    </button>
    <?php
}
