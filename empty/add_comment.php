<?php
/**
 * Add Comment Processing
 * Handles adding new comments to the sidebar
 */

session_start();
require_once 'config/database.php';
require_once 'config/auth.php';

// Require login to post comments
requireLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment = isset($_POST['comment']) ? htmlspecialchars(trim($_POST['comment']), ENT_QUOTES, 'UTF-8') : '';
    $book_id = isset($_POST['book_id']) ? (int)$_POST['book_id'] : null;
    $user_id = getCurrentUserId();
    
    if (empty($comment)) {
        $error = 'Comment cannot be empty.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO comments (user_id, book_id, comment_text) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $book_id, $comment]);
            
            $success = 'Comment posted successfully!';
            
            // Redirect back to the referring page or home
            $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
            header("Location: $redirect");
            exit();
        } catch (PDOException $e) {
            $error = 'Failed to post comment. Please try again.';
            error_log("Comment error: " . $e->getMessage());
        }
    }
}

// If there's an error, redirect back with error message
if ($error) {
    $_SESSION['error'] = $error;
    $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
    header("Location: $redirect");
    exit();
}
?>
