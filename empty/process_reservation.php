<?php
/**
 * Reservation Processing Script
 * Handles book reservation requests from users
 */

session_start();
require_once 'config/database.php';
require_once 'config/auth.php';

// Require login to make reservations
requireLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_id'])) {
    $book_id = (int)$_POST['book_id'];
    $user_id = getCurrentUserId();
    
    try {
        // Start transaction for data consistency
        $pdo->beginTransaction();
        
        // Check if book exists and is available
        $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ? AND is_archived = 0");
        $stmt->execute([$book_id]);
        $book = $stmt->fetch();
        
        if (!$book) {
            $error = 'Book not found.';
        } elseif ($book['available_copies'] <= 0) {
            $error = 'This book is currently unavailable.';
        } else {
            // Check if user already has an active reservation for this book
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM reservations 
                WHERE user_id = ? AND book_id = ? AND status = 'active'
            ");
            $stmt->execute([$user_id, $book_id]);
            
            if ($stmt->fetchColumn() > 0) {
                $error = 'You already have an active reservation for this book.';
            } else {
                // Create reservation
                $stmt = $pdo->prepare("
                    INSERT INTO reservations (user_id, book_id, reservation_date, status) 
                    VALUES (?, ?, NOW(), 'active')
                ");
                $stmt->execute([$user_id, $book_id]);
                
                // Update available copies
                $stmt = $pdo->prepare("
                    UPDATE books 
                    SET available_copies = available_copies - 1 
                    WHERE id = ?
                ");
                $stmt->execute([$book_id]);
                
                // Commit transaction
                $pdo->commit();
                
                $success = 'Book reserved successfully!';
            }
        }
    } catch (PDOException $e) {
        // Rollback on error
        $pdo->rollBack();
        $error = 'Reservation failed. Please try again.';
        error_log("Reservation error: " . $e->getMessage());
    }
    
    // Set session message and redirect
    if ($error) {
        $_SESSION['error'] = $error;
    } else {
        $_SESSION['success'] = $success;
    }
    
    header('Location: my_reservations.php');
    exit();
} else {
    // Redirect if accessed without POST data
    header('Location: reservations.php');
    exit();
}
?>
