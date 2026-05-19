<?php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/auth.php';
?>

<div class="sidebar">
    <h3>Comments & Questions</h3>

    <?php if (isLoggedIn()): ?>
        <div class="comment-form">
            <form method="POST" action="<?php echo url('add_comment.php'); ?>">
                <?php echo csrfField(); ?>
                <div class="form-group">
                    <textarea name="comment" placeholder="Ask a question or leave a comment..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-small">Post Comment</button>
            </form>
        </div>
    <?php else: ?>
        <p><a href="<?php echo url('auth/login.php'); ?>">Login</a> to post comments.</p>
    <?php endif; ?>

    <div class="comments-list">
        <?php
        try {
            $stmt = $pdo->query("
                SELECT c.*, u.full_name, b.title as book_title
                FROM comments c
                LEFT JOIN users u ON c.user_id = u.id
                LEFT JOIN books b ON c.book_id = b.id
                ORDER BY c.created_at DESC
                LIMIT 10
            ");
            $comments = $stmt->fetchAll();

            if (empty($comments)): ?>
                <p>No comments yet.</p>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="comment-item">
                        <div class="comment-author">
                            <?php echo htmlspecialchars($comment['full_name'] ?? 'Anonymous', ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <div class="comment-date">
                            <?php echo date('M d, Y H:i', strtotime($comment['created_at'])); ?>
                        </div>
                        <?php if ($comment['book_title']): ?>
                            <div class="comment-book">
                                <small>About: <?php echo htmlspecialchars($comment['book_title'], ENT_QUOTES, 'UTF-8'); ?></small>
                            </div>
                        <?php endif; ?>
                        <div class="comment-text">
                            <?php echo htmlspecialchars($comment['comment_text'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif;
        } catch (PDOException $e) {
            echo '<p>Error loading comments.</p>';
        }
        ?>
    </div>
</div>
