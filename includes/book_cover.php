<?php
/**
 * Render book cover thumbnail or placeholder.
 * Expects $book with title and optional image_url.
 */

require_once dirname(__DIR__) . '/config/uploads.php';

$coverUrl = bookCoverUrl($book['image_url'] ?? null);
$title = $book['title'] ?? 'Book';
?>
<?php if ($coverUrl): ?>
    <div class="book-image">
        <img src="<?php echo htmlspecialchars($coverUrl, ENT_QUOTES, 'UTF-8'); ?>"
             alt="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?> cover"
             loading="lazy">
    </div>
<?php else: ?>
    <div class="book-image book-image--placeholder">
        <span>No Cover</span>
    </div>
<?php endif; ?>
