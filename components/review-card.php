<?php
/**
 * Review Card Component
 *
 * @param array $review - Review data with photos
 */

$review = $review ?? [];

$reviewId = $review['id'] ?? '';
$rating = $review['rating'] ?? 0;
$title = $review['title'] ?? '';
$reviewText = $review['review_text'] ?? '';
$pros = $review['pros'] ?? '';
$cons = $review['cons'] ?? '';
$visitDate = $review['visit_date'] ?? '';
$visitedWith = $review['visited_with'] ?? $review['visit_type'] ?? '';
$helpfulCount = $review['helpful_count'] ?? 0;
$createdAt = $review['created_at'] ?? '';
$userName = $review['user_name'] ?? __('reviews.default_visitor');
$userAvatar = $review['avatar_url'] ?? null;
$userInitial = strtoupper(substr($userName, 0, 1));
$userId = $review['user_id'] ?? null;
$isOwnReview = isAuthenticated() && $userId === ($_SESSION['user_id'] ?? null);

// Check if user has voted on this review
$userVoted = false;
if (isAuthenticated() && $reviewId) {
    $vote = queryOne('SELECT id FROM review_votes WHERE review_id = :review_id AND user_id = :user_id', [
        ':review_id' => $reviewId,
        ':user_id' => $_SESSION['user_id']
    ]);
    $userVoted = (bool)$vote;
}

// Get photos for this review
$photos = [];
if ($reviewId) {
    $photos = query("
        SELECT id, filename, caption FROM beach_photos
        WHERE review_id = :review_id AND status = 'published'
        ORDER BY created_at ASC
    ", [':review_id' => $reviewId]);
}

// Format dates
$timeAgo = $createdAt ? timeAgo($createdAt) : '';
$visitDateFormatted = $visitDate ? date('M Y', strtotime($visitDate)) : '';

// Visit type labels (use i18n)
$visitedWithMap = [
    'solo' => 'solo',
    'partner' => 'couple',
    'couple' => 'couple',
    'family' => 'family',
    'friends' => 'friends',
    'group' => 'group'
];
$visitedWithKey = $visitedWithMap[$visitedWith] ?? '';
$visitedWithLabel = $visitedWithKey ? __('reviews.trip_types.' . $visitedWithKey) : '';

// Star display
$stars = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
?>

<div class="review-card bg-white border border-gray-200 rounded-xl p-4 mb-4" data-review-id="<?= h($reviewId) ?>">
    <!-- Header -->
    <div class="flex items-start gap-3 mb-3">
        <!-- Avatar -->
        <?php if ($userAvatar): ?>
        <img src="<?= h($userAvatar) ?>" alt="" class="w-10 h-10 rounded-full flex-shrink-0">
        <?php else: ?>
        <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-semibold flex-shrink-0">
            <?= h($userInitial) ?>
        </div>
        <?php endif; ?>

        <div class="flex-1 min-w-0">
            <!-- User name and badges -->
            <div class="flex items-center gap-2 flex-wrap">
                <span class="font-medium text-gray-900"><?= h($userName) ?></span>
                <?php if ($visitedWithLabel): ?>
                <span class="text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full"><?= h($visitedWithLabel) ?></span>
                <?php endif; ?>
            </div>

            <!-- Rating and date -->
            <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                <span class="text-amber-500 text-sm tracking-tight"><?= $stars ?></span>
                <?php if ($timeAgo): ?>
                <span class="text-xs text-gray-400"><?= h($timeAgo) ?></span>
                <?php endif; ?>
                <?php if ($visitDateFormatted): ?>
                <span class="text-xs text-gray-400"><?= h(__('reviews.visited_on', ['date' => $visitDateFormatted])) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Delete button for own reviews -->
        <?php if ($isOwnReview): ?>
        <button data-action="deleteReview" data-action-args='[<?= intval($reviewId) ?>]'
                class="text-gray-400 hover:text-red-500 p-1 transition-colors"
                title="<?= h(__('reviews.delete_review')) ?>">
            <i data-lucide="trash-2" class="w-4 h-4"></i>
        </button>
        <?php endif; ?>
    </div>

    <!-- Title -->
    <?php if ($title): ?>
    <h4 class="font-semibold text-gray-900 mb-2"><?= h($title) ?></h4>
    <?php endif; ?>

    <!-- Review text -->
    <?php if ($reviewText): ?>
    <p class="text-gray-700 text-sm leading-relaxed mb-3"><?= nl2br(h($reviewText)) ?></p>
    <?php endif; ?>

    <!-- Pros/Cons -->
    <?php if ($pros || $cons): ?>
    <div class="flex flex-col sm:flex-row gap-3 mb-3 text-sm">
        <?php if ($pros): ?>
        <div class="flex-1 bg-green-50 rounded-lg p-3">
            <div class="flex items-center gap-1 text-green-700 font-medium mb-1">
                <i data-lucide="thumbs-up" class="w-3.5 h-3.5"></i>
                <span><?= h(__('reviews.pros')) ?></span>
            </div>
            <p class="text-green-800 text-xs"><?= h($pros) ?></p>
        </div>
        <?php endif; ?>
        <?php if ($cons): ?>
        <div class="flex-1 bg-red-50 rounded-lg p-3">
            <div class="flex items-center gap-1 text-red-700 font-medium mb-1">
                <i data-lucide="thumbs-down" class="w-3.5 h-3.5"></i>
                <span><?= h(__('reviews.cons')) ?></span>
            </div>
            <p class="text-red-800 text-xs"><?= h($cons) ?></p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Photos -->
    <?php if (!empty($photos)): ?>
    <div class="flex gap-2 mb-3 overflow-x-auto pb-2 -mx-1 px-1">
        <?php foreach ($photos as $photo): ?>
        <button data-action="openPhotoModal" data-action-args='["/uploads/photos/<?= h($photo['filename']) ?>","<?= h(addslashes($photo['caption'] ?? '')) ?>"]'
                class="flex-shrink-0 w-20 h-20 rounded-lg overflow-hidden hover:opacity-90 transition-opacity">
            <img src="/uploads/photos/thumbs/<?= h($photo['filename']) ?>"
                 alt="<?= h($photo['caption'] ?? __('reviews.beach_photo')) ?>"
                 class="w-full h-full object-cover"
                 loading="lazy">
        </button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Footer actions -->
    <div class="flex items-center gap-4 pt-3 border-t border-gray-100">
        <!-- Helpful button -->
        <button data-action="voteReview" data-action-args='[<?= intval($reviewId) ?>,"__this__"]'
                class="helpful-btn flex items-center gap-1.5 text-sm <?= $userVoted ? 'text-blue-600' : 'text-gray-500 hover:text-blue-600' ?> transition-colors"
                data-voted="<?= $userVoted ? 'true' : 'false' ?>"
                data-review-id="<?= h($reviewId) ?>">
            <i data-lucide="thumbs-up" class="w-4 h-4"></i>
            <span><?= h(__('reviews.helpful')) ?></span>
            <?php if ($helpfulCount > 0): ?>
            <span class="helpful-count text-xs bg-gray-100 px-1.5 py-0.5 rounded-full"><?= $helpfulCount ?></span>
            <?php endif; ?>
        </button>

        <!-- Share button -->
        <button data-action="shareReview" data-action-args='[<?= intval($reviewId) ?>]'
                class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 transition-colors">
            <i data-lucide="share-2" class="w-4 h-4"></i>
            <span><?= h(__('common.share')) ?></span>
        </button>

        <!-- Report button (only for others' reviews) -->
        <?php if (!$isOwnReview): ?>
        <button data-action="reportReview" data-action-args='[<?= intval($reviewId) ?>]'
                class="ml-auto text-gray-400 hover:text-gray-600 text-xs transition-colors">
            <?= h(__('reviews.report')) ?>
        </button>
        <?php endif; ?>
    </div>
</div>
