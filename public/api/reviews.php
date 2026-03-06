<?php
/**
 * Beach Reviews API
 *
 * GET: Fetch reviews for a beach
 * POST: Submit a new review or perform actions (vote, delete)
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/session.php';
session_start();
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    getReviews();
} elseif ($method === 'POST') {
    $action = $_POST['action'] ?? 'create';
    switch ($action) {
        case 'vote':
            voteReview();
            break;
        case 'delete':
            deleteReview();
            break;
        case 'create':
        default:
            createReview();
            break;
    }
} else {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

function getReviews() {
    $beachId = $_GET['beach_id'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(20, max(1, intval($_GET['limit'] ?? 10)));
    $sort = $_GET['sort'] ?? 'recent';
    $offset = ($page - 1) * $limit;

    if (!$beachId) {
        jsonResponse(['error' => 'Beach ID required'], 400);
    }

    // Build ORDER BY based on sort
    $orderBy = match($sort) {
        'helpful' => 'r.helpful_count DESC, r.created_at DESC',
        'highest' => 'r.rating DESC, r.created_at DESC',
        'lowest' => 'r.rating ASC, r.created_at DESC',
        default => 'r.created_at DESC'
    };

    // Get reviews
    $reviews = query("
        SELECT
            r.id, r.rating, r.title, r.review_text, r.visit_date, r.visited_with,
            r.pros, r.cons, r.helpful_count, r.created_at,
            u.id as user_id, u.name as user_name, u.avatar_url
        FROM beach_reviews r
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.beach_id = :beach_id AND r.status = 'published'
        ORDER BY $orderBy
        LIMIT :limit OFFSET :offset
    ", [':beach_id' => $beachId, ':limit' => $limit, ':offset' => $offset]);

    // Get total count
    $total = queryOne("
        SELECT COUNT(*) as count FROM beach_reviews
        WHERE beach_id = :beach_id AND status = 'published'
    ", [':beach_id' => $beachId]);

    // Get rating distribution
    $distribution = query("
        SELECT rating, COUNT(*) as count
        FROM beach_reviews
        WHERE beach_id = :beach_id AND status = 'published'
        GROUP BY rating
    ", [':beach_id' => $beachId]);

    $ratingCounts = array_fill(1, 5, 0);
    foreach ($distribution as $row) {
        $ratingCounts[$row['rating']] = $row['count'];
    }

    // Get average rating
    $avgRating = queryOne("
        SELECT AVG(rating) as avg, COUNT(*) as count
        FROM beach_reviews
        WHERE beach_id = :beach_id AND status = 'published'
    ", [':beach_id' => $beachId]);

    // Get photos for each review
    $reviewIds = array_column($reviews, 'id');
    $photosByReview = [];
    if (!empty($reviewIds)) {
        $placeholders = implode(',', array_fill(0, count($reviewIds), '?'));
        $photos = query("
            SELECT id, review_id, filename, caption
            FROM beach_photos
            WHERE review_id IN ($placeholders) AND status = 'published'
            ORDER BY created_at ASC
        ", $reviewIds);
        foreach ($photos as $photo) {
            $photosByReview[$photo['review_id']][] = $photo;
        }
    }

    // Check which reviews current user has voted on
    $userVotes = [];
    if (isAuthenticated()) {
        $userId = $_SESSION['user_id'];
        if (!empty($reviewIds)) {
            $placeholders = implode(',', array_fill(0, count($reviewIds), '?'));
            $votes = query("
                SELECT review_id FROM review_votes
                WHERE review_id IN ($placeholders) AND user_id = ?
            ", [...$reviewIds, $userId]);
            $userVotes = array_column($votes, 'review_id');
        }
    }

    // Format reviews
    foreach ($reviews as &$review) {
        $review['time_ago'] = timeAgo($review['created_at']);
        $review['photos'] = $photosByReview[$review['id']] ?? [];
        $review['user_voted'] = in_array($review['id'], $userVotes);
        $review['is_own'] = isAuthenticated() && $review['user_id'] === $_SESSION['user_id'];
    }

    if (isHtmx()) {
        header('Content-Type: text/html');
        if (empty($reviews)) {
            echo '<div class="text-center py-8 text-gray-500">
                <p>No reviews yet. Be the first to share your experience!</p>
            </div>';
            return;
        }
        foreach ($reviews as $review) {
            renderReviewCard($review);
        }
        return;
    }

    jsonResponse([
        'reviews' => $reviews,
        'total' => $total['count'],
        'page' => $page,
        'pages' => ceil($total['count'] / $limit),
        'average_rating' => round($avgRating['avg'] ?? 0, 1),
        'review_count' => $avgRating['count'],
        'distribution' => $ratingCounts
    ]);
}

function createReview() {
    if (!isAuthenticated()) {
        jsonResponse(['error' => 'Please sign in to write a review'], 401);
    }

    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrf($csrfToken)) {
        jsonResponse(['error' => 'Invalid request'], 403);
    }

    $beachId = $_POST['beach_id'] ?? '';
    $rating = intval($_POST['rating'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $reviewText = trim($_POST['review_text'] ?? '');
    $visitDate = $_POST['visit_date'] ?? null;
    $visitedWith = $_POST['visited_with'] ?? null;
    $pros = trim($_POST['pros'] ?? '');
    $cons = trim($_POST['cons'] ?? '');
    $userId = $_SESSION['user_id'];

    // Validate beach exists
    $beach = queryOne('SELECT id, name FROM beaches WHERE id = :id', [':id' => $beachId]);
    if (!$beach) {
        jsonResponse(['error' => 'Beach not found'], 404);
    }

    // Validate rating
    if ($rating < 1 || $rating > 5) {
        jsonResponse(['error' => 'Rating must be between 1 and 5'], 400);
    }

    // Validate review text
    if (strlen($reviewText) < 20) {
        jsonResponse(['error' => 'Review must be at least 20 characters'], 400);
    }

    if (strlen($reviewText) > 5000) {
        jsonResponse(['error' => 'Review must be less than 5000 characters'], 400);
    }

    // Validate visited_with
    $validVisitedWith = ['solo', 'partner', 'family', 'friends', 'group'];
    if ($visitedWith && !in_array($visitedWith, $validVisitedWith)) {
        $visitedWith = null;
    }

    // Check if user already reviewed this beach
    $existing = queryOne("
        SELECT id FROM beach_reviews
        WHERE beach_id = :beach_id AND user_id = :user_id
    ", [':beach_id' => $beachId, ':user_id' => $userId]);

    if ($existing) {
        jsonResponse(['error' => 'You have already reviewed this beach. You can edit your existing review.'], 400);
    }

    // Insert review
    $result = execute("
        INSERT INTO beach_reviews (beach_id, user_id, rating, title, review_text, visit_date, visited_with, pros, cons, created_at, updated_at)
        VALUES (:beach_id, :user_id, :rating, :title, :review_text, :visit_date, :visited_with, :pros, :cons, datetime('now'), datetime('now'))
    ", [
        ':beach_id' => $beachId,
        ':user_id' => $userId,
        ':rating' => $rating,
        ':title' => $title ?: null,
        ':review_text' => $reviewText,
        ':visit_date' => $visitDate ?: null,
        ':visited_with' => $visitedWith,
        ':pros' => $pros ?: null,
        ':cons' => $cons ?: null
    ]);

    if ($result) {
        $reviewId = getDB()->lastInsertRowID();
        jsonResponse([
            'success' => true,
            'message' => 'Thanks for your review!',
            'review_id' => $reviewId
        ]);
    } else {
        jsonResponse(['error' => 'Failed to save review'], 500);
    }
}

function voteReview() {
    if (!isAuthenticated()) {
        jsonResponse(['error' => 'Please sign in to vote'], 401);
    }

    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrf($csrfToken)) {
        jsonResponse(['error' => 'Invalid request'], 403);
    }

    $reviewId = intval($_POST['review_id'] ?? 0);
    $userId = $_SESSION['user_id'];

    // Verify review exists
    $review = queryOne('SELECT id, user_id FROM beach_reviews WHERE id = :id', [':id' => $reviewId]);
    if (!$review) {
        jsonResponse(['error' => 'Review not found'], 404);
    }

    // Can't vote on own review
    if ($review['user_id'] === $userId) {
        jsonResponse(['error' => 'You cannot vote on your own review'], 400);
    }

    // Check if already voted
    $existing = queryOne("
        SELECT id FROM review_votes
        WHERE review_id = :review_id AND user_id = :user_id
    ", [':review_id' => $reviewId, ':user_id' => $userId]);

    if ($existing) {
        // Remove vote
        execute('DELETE FROM review_votes WHERE id = :id', [':id' => $existing['id']]);
        execute('UPDATE beach_reviews SET helpful_count = helpful_count - 1 WHERE id = :id', [':id' => $reviewId]);
        jsonResponse(['success' => true, 'action' => 'removed', 'message' => 'Vote removed']);
    } else {
        // Add vote
        execute("
            INSERT INTO review_votes (review_id, user_id, created_at)
            VALUES (:review_id, :user_id, datetime('now'))
        ", [':review_id' => $reviewId, ':user_id' => $userId]);
        execute('UPDATE beach_reviews SET helpful_count = helpful_count + 1 WHERE id = :id', [':id' => $reviewId]);
        jsonResponse(['success' => true, 'action' => 'added', 'message' => 'Marked as helpful']);
    }
}

function deleteReview() {
    if (!isAuthenticated()) {
        jsonResponse(['error' => 'Please sign in'], 401);
    }

    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrf($csrfToken)) {
        jsonResponse(['error' => 'Invalid request'], 403);
    }

    $reviewId = intval($_POST['review_id'] ?? 0);
    $userId = $_SESSION['user_id'];

    // Verify ownership
    $review = queryOne("
        SELECT id FROM beach_reviews
        WHERE id = :id AND user_id = :user_id
    ", [':id' => $reviewId, ':user_id' => $userId]);

    if (!$review) {
        jsonResponse(['error' => 'Review not found or not authorized'], 404);
    }

    // Delete associated photos first
    $photos = query('SELECT filename FROM beach_photos WHERE review_id = :review_id', [':review_id' => $reviewId]);
    foreach ($photos as $photo) {
        $path = APP_ROOT . '/uploads/photos/' . $photo['filename'];
        if (file_exists($path)) {
            unlink($path);
        }
    }
    execute('DELETE FROM beach_photos WHERE review_id = :review_id', [':review_id' => $reviewId]);

    // Delete review
    execute('DELETE FROM beach_reviews WHERE id = :id', [':id' => $reviewId]);

    jsonResponse(['success' => true, 'message' => 'Review deleted']);
}

function renderReviewCard($review) {
    $avatar = $review['avatar_url'] ?: null;
    $name = $review['user_name'] ?: 'Beach Visitor';
    $initial = strtoupper(substr($name, 0, 1));
    $stars = str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']);
    ?>
    <div class="review-card bg-white rounded-lg border border-gray-200 p-4 mb-4" data-review-id="<?= $review['id'] ?>">
        <!-- Header -->
        <div class="flex items-start gap-3 mb-3">
            <?php if ($avatar): ?>
            <img src="<?= h($avatar) ?>" alt="" class="w-10 h-10 rounded-full flex-shrink-0">
            <?php else: ?>
            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-medium flex-shrink-0">
                <?= $initial ?>
            </div>
            <?php endif; ?>

            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-medium text-gray-900"><?= h($name) ?></span>
                    <?php if ($review['visited_with']): ?>
                    <span class="text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">
                        <?= h(ucfirst($review['visited_with'])) ?>
                    </span>
                    <?php endif; ?>
                </div>
                <div class="flex items-center gap-2 mt-0.5">
                    <span class="text-amber-500 text-sm tracking-tight"><?= $stars ?></span>
                    <span class="text-xs text-gray-400"><?= h($review['time_ago']) ?></span>
                    <?php if ($review['visit_date']): ?>
                    <span class="text-xs text-gray-400">Visited <?= date('M Y', strtotime($review['visit_date'])) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($review['is_own']): ?>
            <button data-action="deleteReview" data-action-args='[<?= $review['id'] ?>]'
                    class="text-gray-400 hover:text-red-500 p-1"
                    title="Delete review">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
            </button>
            <?php endif; ?>
        </div>

        <!-- Title -->
        <?php if ($review['title']): ?>
        <h4 class="font-semibold text-gray-900 mb-2"><?= h($review['title']) ?></h4>
        <?php endif; ?>

        <!-- Review text -->
        <p class="text-gray-700 text-sm leading-relaxed mb-3"><?= nl2br(h($review['review_text'])) ?></p>

        <!-- Pros/Cons -->
        <?php if ($review['pros'] || $review['cons']): ?>
        <div class="flex flex-col sm:flex-row gap-3 mb-3 text-sm">
            <?php if ($review['pros']): ?>
            <div class="flex-1 bg-green-50 rounded-lg p-3">
                <div class="flex items-center gap-1 text-green-700 font-medium mb-1">
                    <i data-lucide="thumbs-up" class="w-3.5 h-3.5"></i>
                    <span>Pros</span>
                </div>
                <p class="text-green-800 text-xs"><?= h($review['pros']) ?></p>
            </div>
            <?php endif; ?>
            <?php if ($review['cons']): ?>
            <div class="flex-1 bg-red-50 rounded-lg p-3">
                <div class="flex items-center gap-1 text-red-700 font-medium mb-1">
                    <i data-lucide="thumbs-down" class="w-3.5 h-3.5"></i>
                    <span>Cons</span>
                </div>
                <p class="text-red-800 text-xs"><?= h($review['cons']) ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Photos -->
        <?php if (!empty($review['photos'])): ?>
        <div class="flex gap-2 mb-3 overflow-x-auto pb-2">
            <?php foreach ($review['photos'] as $photo): ?>
            <button data-action="openPhotoModal" data-action-args='["<?= h($photo['filename']) ?>","<?= h($photo['caption'] ?? '') ?>"]'
                    class="flex-shrink-0 w-20 h-20 rounded-lg overflow-hidden hover:opacity-90 transition-opacity">
                <img src="/uploads/photos/thumbs/<?= h($photo['filename']) ?>"
                     alt="<?= h($photo['caption'] ?? 'Beach photo') ?>"
                     class="w-full h-full object-cover">
            </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="flex items-center gap-4 pt-2 border-t border-gray-100">
            <button data-action="voteReview" data-action-args='[<?= $review['id'] ?>,"__this__"]'
                    class="helpful-btn flex items-center gap-1.5 text-sm <?= $review['user_voted'] ? 'text-blue-600' : 'text-gray-500 hover:text-blue-600' ?> transition-colors"
                    data-voted="<?= $review['user_voted'] ? 'true' : 'false' ?>">
                <i data-lucide="thumbs-up" class="w-4 h-4"></i>
                <span>Helpful</span>
                <?php if ($review['helpful_count'] > 0): ?>
                <span class="helpful-count text-xs bg-gray-100 px-1.5 py-0.5 rounded-full"><?= $review['helpful_count'] ?></span>
                <?php endif; ?>
            </button>
            <button data-action="shareReview" data-action-args='[<?= $review['id'] ?>]'
                    class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 transition-colors">
                <i data-lucide="share-2" class="w-4 h-4"></i>
                <span>Share</span>
            </button>
        </div>
    </div>
    <?php
}
