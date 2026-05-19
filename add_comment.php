<?php
/**
 * Add comment (POST + CSRF).
 */

require_once 'config/database.php';
require_once 'config/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('index.php');
}

requireCsrf();

$comment = trim($_POST['comment'] ?? '');
$book_id = isset($_POST['book_id']) && $_POST['book_id'] !== '' ? (int) $_POST['book_id'] : null;
if ($book_id === 0) {
    $book_id = null;
}

if ($comment === '') {
    $_SESSION['error'] = 'Comment cannot be empty.';
    redirectBack('index.php');
}

try {
    $stmt = $pdo->prepare('INSERT INTO comments (user_id, book_id, comment_text) VALUES (?, ?, ?)');
    $stmt->execute([getCurrentUserId(), $book_id, $comment]);
    $_SESSION['success'] = 'Comment posted successfully!';
} catch (PDOException $e) {
    $_SESSION['error'] = 'Failed to post comment. Please try again.';
    error_log('Comment error: ' . $e->getMessage());
}

redirectBack('index.php');
