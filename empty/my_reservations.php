<?php
/**
 * My Reservations Page
 * Displays current user's active and past reservations
 */

require_once 'config/database.php';
require_once 'config/auth.php';

// Require login to access this page
requireLogin();

// Handle return/cancel actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $reservation_id = (int)$_GET['id'];
    $user_id = getCurrentUserId();
    
    try {
        if ($action === 'return') {
            // Start transaction
            $pdo->beginTransaction();
            
            // Get reservation details
            $stmt = $pdo->prepare("
                SELECT r.*, b.available_copies 
                FROM reservations r 
                JOIN books b ON r.book_id = b.id 
                WHERE r.id = ? AND r.user_id = ? AND r.status = 'active'
            ");
            $stmt->execute([$reservation_id, $user_id]);
            $reservation = $stmt->fetch();
            
            if ($reservation) {
                // Update reservation status
                $stmt = $pdo->prepare("
                    UPDATE reservations 
                    SET status = 'returned', return_date = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$reservation_id]);
                
                // Update available copies
                $stmt = $pdo->prepare("
                    UPDATE books 
                    SET available_copies = available_copies + 1 
                    WHERE id = ?
                ");
                $stmt->execute([$reservation['book_id']]);
                
                $pdo->commit();
                $_SESSION['success'] = 'Book returned successfully!';
            }
        } elseif ($action === 'cancel') {
            // Start transaction
            $pdo->beginTransaction();
            
            // Get reservation details
            $stmt = $pdo->prepare("
                SELECT r.*, b.available_copies 
                FROM reservations r 
                JOIN books b ON r.book_id = b.id 
                WHERE r.id = ? AND r.user_id = ? AND r.status = 'active'
            ");
            $stmt->execute([$reservation_id, $user_id]);
            $reservation = $stmt->fetch();
            
            if ($reservation) {
                // Update reservation status
                $stmt = $pdo->prepare("
                    UPDATE reservations 
                    SET status = 'cancelled', return_date = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$reservation_id]);
                
                // Update available copies
                $stmt = $pdo->prepare("
                    UPDATE books 
                    SET available_copies = available_copies + 1 
                    WHERE id = ?
                ");
                $stmt->execute([$reservation['book_id']]);
                
                $pdo->commit();
                $_SESSION['success'] = 'Reservation cancelled successfully!';
            }
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Operation failed. Please try again.';
        error_log("Reservation action error: " . $e->getMessage());
    }
    
    header('Location: my_reservations.php');
    exit();
}

// Get user's reservations
try {
    $stmt = $pdo->prepare("
        SELECT r.*, b.title, b.author, b.isbn 
        FROM reservations r 
        JOIN books b ON r.book_id = b.id 
        WHERE r.user_id = ? 
        ORDER BY r.reservation_date DESC
    ");
    $stmt->execute([getCurrentUserId()]);
    $reservations = $stmt->fetchAll();
} catch (PDOException $e) {
    $reservations = [];
    error_log("Error fetching reservations: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reservations - Book Reservation System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="main-content">
            <h1>My Reservations</h1>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($reservations)): ?>
                <div class="alert alert-info">
                    <p>You don't have any reservations yet.</p>
                    <p><a href="reservations.php" class="btn btn-primary">Browse Books</a></p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Book Title</th>
                            <th>Author</th>
                            <th>ISBN</th>
                            <th>Reserved Date</th>
                            <th>Status</th>
                            <th>Return Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $reservation): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reservation['title']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['author']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['isbn'] ?? 'N/A'); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($reservation['reservation_date'])); ?></td>
                                <td>
                                    <?php if ($reservation['status'] === 'active'): ?>
                                        <span style="color: #28a745; font-weight: bold;">Active</span>
                                    <?php elseif ($reservation['status'] === 'returned'): ?>
                                        <span style="color: #667eea;">Returned</span>
                                    <?php else: ?>
                                        <span style="color: #dc3545;">Cancelled</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $reservation['return_date'] 
                                        ? date('M d, Y', strtotime($reservation['return_date'])) 
                                        : '-'; ?>
                                </td>
                                <td>
                                    <?php if ($reservation['status'] === 'active'): ?>
                                        <div class="action-buttons">
                                            <a href="?action=return&id=<?php echo $reservation['id']; ?>" 
                                               class="btn btn-success btn-small"
                                               onclick="return confirm('Are you sure you want to return this book?');">
                                                Return
                                            </a>
                                            <a href="?action=cancel&id=<?php echo $reservation['id']; ?>" 
                                               class="btn btn-danger btn-small"
                                               onclick="return confirm('Are you sure you want to cancel this reservation?');">
                                                Cancel
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #888;">No actions available</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div style="margin-top: 30px;">
                    <a href="reservations.php" class="btn btn-primary">Reserve More Books</a>
                </div>
            <?php endif; ?>
        </div>
        
        <?php include 'includes/sidebar.php'; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
