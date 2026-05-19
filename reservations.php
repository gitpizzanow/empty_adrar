<?php
/**
 * User Reservation Page
 * Allows users to search and browse books, and make reservations
 */

require_once 'config/database.php';
require_once 'config/auth.php';

// Require login to access this page
requireLogin();

$search = isset($_GET['search']) ? htmlspecialchars(trim($_GET['search']), ENT_QUOTES, 'UTF-8') : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Get categories for filter
try {
    $categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// Build query for books
try {
    $query = "
        SELECT b.*, c.name as category_name 
        FROM books b 
        LEFT JOIN categories c ON b.category_id = c.id 
        WHERE b.is_archived = 0 
    ";
    $params = [];
    
    if (!empty($search)) {
        $query .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.description LIKE ?)";
        $search_param = "%$search%";
        $params = [$search_param, $search_param, $search_param];
    }
    
    if ($category_filter > 0) {
        $query .= " AND b.category_id = ?";
        $params[] = $category_filter;
    }
    
    $query .= " ORDER BY b.title ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $books = $stmt->fetchAll();
} catch (PDOException $e) {
    $books = [];
    error_log("Error fetching books: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Books - Book Reservation System</title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="main-content">
            <h1>Browse & Reserve Books</h1>
            
            <!-- Search and Filter -->
            <div class="search-box">
                <form method="GET" action="">
                    <input type="text" name="search" placeholder="Search by title, author, or description..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    
                    <div class="form-group" style="margin-top: 15px;">
                        <label for="category">Filter by Category:</label>
                        <select id="category" name="category">
                            <option value="0">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" 
                                        <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Search</button>
                    <a href="<?php echo url('reservations.php'); ?>" class="btn btn-secondary" style="margin-top: 10px;">Clear Filters</a>
                </form>
            </div>
            
            <!-- Results -->
            <?php if (empty($books)): ?>
                <div class="alert alert-info">
                    <?php if (!empty($search) || $category_filter > 0): ?>
                        No books found matching your criteria. <a href="<?php echo url('reservations.php'); ?>">View all books</a>.
                    <?php else: ?>
                        No books available at the moment.
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p style="margin-bottom: 20px;">Found <?php echo count($books); ?> book(s).</p>
                
                <div class="books-grid">
                    <?php foreach ($books as $book): ?>
                        <div class="book-card">
                            <div class="book-image" style="display: flex; align-items: center; justify-content: center; color: #999;">
                                No Cover
                            </div>
                            <div class="book-info">
                                <div class="book-title"><?php echo htmlspecialchars($book['title']); ?></div>
                                <div class="book-author">by <?php echo htmlspecialchars($book['author']); ?></div>
                                <div class="book-category"><?php echo htmlspecialchars($book['category_name'] ?? 'Uncategorized'); ?></div>
                                <div class="book-availability <?php echo $book['available_copies'] > 0 ? '' : 'unavailable'; ?>">
                                    <?php echo $book['available_copies'] > 0 
                                        ? $book['available_copies'] . ' of ' . $book['total_copies'] . ' available' 
                                        : 'Currently unavailable'; ?>
                                </div>
                                <?php if ($book['description']): ?>
                                    <div class="book-description">
                                        <?php echo htmlspecialchars(substr($book['description'], 0, 150)); ?>
                                        <?php echo strlen($book['description']) > 150 ? '...' : ''; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($book['available_copies'] > 0): ?>
                                    <button type="button"
                                            onclick="reserveBook(<?php echo (int) $book['id']; ?>, <?php echo json_encode($book['title'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)"
                                            class="btn btn-primary btn-small" style="width: 100%; margin-top: 10px;">
                                        Reserve
                                    </button>
                                <?php else: ?>
                                    <button disabled class="btn btn-secondary btn-small" style="width: 100%; margin-top: 10px;">
                                        Not Available
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <?php include 'includes/sidebar.php'; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        const csrfToken = <?php echo json_encode(csrfToken(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
        const reserveUrl = <?php echo json_encode(url('process_reservation.php'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

        function reserveBook(bookId, bookTitle) {
            if (confirm('Are you sure you want to reserve "' + bookTitle + '"?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = reserveUrl;

                const fields = {
                    book_id: bookId,
                    csrf_token: csrfToken
                };
                for (const [name, value] of Object.entries(fields)) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = value;
                    form.appendChild(input);
                }

                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
