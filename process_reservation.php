<?php
/**
 * Reservation processing (POST + CSRF + transaction).
 */

require_once 'config/database.php';
require_once 'config/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['book_id'])) {
    redirectTo('reservations.php');
}

requireCsrf();

$book_id = (int) $_POST['book_id'];
$user_id = getCurrentUserId();
$error = '';
$success = '';

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT * FROM books WHERE id = ? AND is_archived = 0 FOR UPDATE');
    $stmt->execute([$book_id]);
    $book = $stmt->fetch();

    if (!$book) {
        $error = 'Book not found.';
    } elseif ($book['available_copies'] <= 0) {
        $error = 'This book is currently unavailable.';
    } else {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM reservations
            WHERE user_id = ? AND book_id = ? AND status = 'active'
        ");
        $stmt->execute([$user_id, $book_id]);

        if ($stmt->fetchColumn() > 0) {
            $error = 'You already have an active reservation for this book.';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO reservations (user_id, book_id, reservation_date, status)
                VALUES (?, ?, NOW(), 'active')
            ");
            $stmt->execute([$user_id, $book_id]);

            $stmt = $pdo->prepare('
                UPDATE books SET available_copies = available_copies - 1 WHERE id = ?
            ');
            $stmt->execute([$book_id]);

            $success = 'Book reserved successfully!';
        }
    }

    if ($error) {
        rollbackIfActive($pdo);
    } else {
        $pdo->commit();
    }
} catch (PDOException $e) {
    rollbackIfActive($pdo);
    $error = 'Reservation failed. Please try again.';
    error_log('Reservation error: ' . $e->getMessage());
}

if ($error) {
    $_SESSION['error'] = $error;
} else {
    $_SESSION['success'] = $success;
}

redirectTo('my_reservations.php');
