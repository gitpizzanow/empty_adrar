<?php
/**
 * Home Page - Public Landing Page
 * Displays featured books and system information
 */

require_once 'config/database.php';
require_once 'config/auth.php';

// Get featured books (latest additions, not archived)
try {
    $stmt = $pdo->query("
        SELECT b.*, c.name as category_name 
        FROM books b 
        LEFT JOIN categories c ON b.category_id = c.id 
        WHERE b.is_archived = 0 
        ORDER BY b.created_at DESC 
        LIMIT 6
    ");
    $featured_books = $stmt->fetchAll();
} catch (PDOException $e) {
    $featured_books = [];
    error_log("Error fetching books: " . $e->getMessage());
}

// Get statistics
try {
    $total_books = $pdo->query("SELECT COUNT(*) FROM books WHERE is_archived = 0")->fetchColumn();
    $total_categories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
} catch (PDOException $e) {
    $total_books = 0;
    $total_categories = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Book Reservation System</title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="main-content">
            <h1>Welcome to the Book Reservation System</h1>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_books; ?></div>
                    <div class="stat-label">Available Books</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_categories; ?></div>
                    <div class="stat-label">Categories</div>
                </div>
            </div>
            
            <section class="intro">
                <h2>About Our System</h2>
                <p>
                    Welcome to the University Book Reservation System! This platform allows students and faculty 
                    to browse, search, and reserve books from our library collection. Simply create an account, 
                    search for books by title, author, or category, and reserve your desired books with just a few clicks.
                </p>
                
                <?php if (!isLoggedIn()): ?>
                    <div class="cta-section">
                        <h3>Get Started Today</h3>
                        <p>Register for free to start reserving books.</p>
                        <div class="action-buttons">
                            <a href="<?php echo url('auth/register.php'); ?>" class="btn btn-primary">Register Now</a>
                            <a href="<?php echo url('auth/login.php'); ?>" class="btn btn-secondary">Login</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="cta-section">
                        <h3>Welcome back, <?php echo htmlspecialchars(getCurrentUserName()); ?>!</h3>
                        <div class="action-buttons">
                            <?php if (isAdmin()): ?>
                                <a href="<?php echo url('admin/dashboard.php'); ?>" class="btn btn-primary">Go to Admin Dashboard</a>
                            <?php else: ?>
                                <a href="<?php echo url('reservations.php'); ?>" class="btn btn-primary">Browse Books</a>
                                <a href="<?php echo url('my_reservations.php'); ?>" class="btn btn-secondary">My Reservations</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
            
            <section class="featured-books">
                <h2>Featured Books</h2>
                
                <?php if (empty($featured_books)): ?>
                    <p>No books available at the moment.</p>
                <?php else: ?>
                    <div class="books-grid">
                        <?php foreach ($featured_books as $book): ?>
                            <div class="book-card">
                                <div class="book-image" style="display: flex; align-items: center; justify-content: center; color: #999;">
                                    No Cover
                                </div>
                                <div class="book-info">
                                    <div class="book-title"><?php echo htmlspecialchars($book['title']); ?></div>
                                    <div class="book-author">by <?php echo htmlspecialchars($book['author']); ?></div>
                                    <div class="book-category"><?php echo htmlspecialchars($book['category_name'] ?? 'Uncategorized'); ?></div>
                                    <div class="book-availability <?php echo $book['available_copies'] > 0 ? '' : 'unavailable'; ?>">
                                        <?php echo $book['available_copies'] > 0 ? $book['available_copies'] . ' available' : 'Not available'; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (isLoggedIn() && !isAdmin()): ?>
                        <div style="text-align: center; margin-top: 30px;">
                            <a href="<?php echo url('reservations.php'); ?>" class="btn btn-primary">View All Books</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </div>
        
        <?php include 'includes/sidebar.php'; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
