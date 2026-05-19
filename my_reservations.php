<?php
/**
 * My Reservations — view and manage reservations (POST + CSRF).
 */

require_once 'config/database.php';
require_once 'config/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    requireCsrf();

    $action = $_POST['action'];
    $reservation_id = (int) $_POST['id'];
    $user_id = getCurrentUserId();

    if (!in_array($action, ['return', 'cancel'], true)) {
        $_SESSION['error'] = 'Invalid action.';
        redirectTo('my_reservations.php');
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            SELECT r.*, b.available_copies
            FROM reservations r
            JOIN books b ON r.book_id = b.id
            WHERE r.id = ? AND r.user_id = ? AND r.status = 'active'
        ");
        $stmt->execute([$reservation_id, $user_id]);
        $reservation = $stmt->fetch();

        if (!$reservation) {
            rollbackIfActive($pdo);
            $_SESSION['error'] = 'Reservation not found or already closed.';
        } else {
            $newStatus = $action === 'return' ? 'returned' : 'cancelled';
            $stmt = $pdo->prepare("
                UPDATE reservations SET status = ?, return_date = NOW() WHERE id = ?
            ");
            $stmt->execute([$newStatus, $reservation_id]);

            $stmt = $pdo->prepare('
                UPDATE books SET available_copies = available_copies + 1 WHERE id = ?
            ');
            $stmt->execute([$reservation['book_id']]);

            $pdo->commit();
            $_SESSION['success'] = $action === 'return'
                ? 'Book returned successfully!'
                : 'Reservation cancelled successfully!';
        }
    } catch (PDOException $e) {
        rollbackIfActive($pdo);
        $_SESSION['error'] = 'Operation failed. Please try again.';
        error_log('Reservation action error: ' . $e->getMessage());
    }

    redirectTo('my_reservations.php');
}

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
    error_log('Error fetching reservations: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reservations - Book Reservation System</title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="main-content">
            <h1>My Reservations</h1>

            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($reservations)): ?>
                <div class="alert alert-info">
                    <p>You don't have any reservations yet.</p>
                    <p><a href="<?php echo url('reservations.php'); ?>" class="btn btn-primary">Browse Books</a></p>
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
                                <td><?php echo htmlspecialchars($reservation['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($reservation['author'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($reservation['isbn'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
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
                                            <form method="POST" action="<?php echo url('my_reservations.php'); ?>" style="display:inline">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="action" value="return">
                                                <input type="hidden" name="id" value="<?php echo (int) $reservation['id']; ?>">
                                                <button type="submit" class="btn btn-success btn-small"
                                                    onclick="return confirm('Are you sure you want to return this book?');">Return</button>
                                            </form>
                                            <form method="POST" action="<?php echo url('my_reservations.php'); ?>" style="display:inline">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="action" value="cancel">
                                                <input type="hidden" name="id" value="<?php echo (int) $reservation['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-small"
                                                    onclick="return confirm('Are you sure you want to cancel this reservation?');">Cancel</button>
                                            </form>
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
                    <a href="<?php echo url('reservations.php'); ?>" class="btn btn-primary">Reserve More Books</a>
                </div>
            <?php endif; ?>
        </div>

        <?php include 'includes/sidebar.php'; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
