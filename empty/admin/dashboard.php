<?php
/**
 * Admin Dashboard
 * Provides CRUD operations for books management
 */

require_once '../config/database.php';
require_once '../config/auth.php';

// Require admin access
requireAdmin();

// Handle CRUD operations
$action = isset($_GET['action']) ? $_GET['action'] : '';
$book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$message_type = '';

// Add/Edit Book
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_book'])) {
        $title = isset($_POST['title']) ? htmlspecialchars(trim($_POST['title']), ENT_QUOTES, 'UTF-8') : '';
        $author = isset($_POST['author']) ? htmlspecialchars(trim($_POST['author']), ENT_QUOTES, 'UTF-8') : '';
        $isbn = isset($_POST['isbn']) ? htmlspecialchars(trim($_POST['isbn']), ENT_QUOTES, 'UTF-8') : '';
        $category_id = (int)$_POST['category_id'];
        $description = isset($_POST['description']) ? htmlspecialchars(trim($_POST['description']), ENT_QUOTES, 'UTF-8') : '';
        $total_copies = (int)$_POST['total_copies'];
        $available_copies = (int)$_POST['available_copies'];
        $edit_id = isset($_POST['edit_id']) ? (int)$_POST['edit_id'] : 0;
        
        try {
            if ($edit_id > 0) {
                // Update existing book
                $stmt = $pdo->prepare("
                    UPDATE books 
                    SET title=?, author=?, isbn=?, category_id=?, description=?, 
                        total_copies=?, available_copies=?, updated_at=NOW() 
                    WHERE id=?
                ");
                $stmt->execute([$title, $author, $isbn, $category_id, $description, 
                               $total_copies, $available_copies, $edit_id]);
                $message = 'Book updated successfully!';
                $message_type = 'success';
            } else {
                // Add new book
                $stmt = $pdo->prepare("
                    INSERT INTO books (title, author, isbn, category_id, description, total_copies, available_copies) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$title, $author, $isbn, $category_id, $description, 
                               $total_copies, $available_copies]);
                $message = 'Book added successfully!';
                $message_type = 'success';
            }
        } catch (PDOException $e) {
            $message = 'Error saving book. Please try again.';
            $message_type = 'error';
            error_log("Book save error: " . $e->getMessage());
        }
    }
}

// Archive/Delete Book
if ($action === 'archive' && $book_id > 0) {
    try {
        $stmt = $pdo->prepare("UPDATE books SET is_archived = 1 WHERE id = ?");
        $stmt->execute([$book_id]);
        $message = 'Book archived successfully!';
        $message_type = 'success';
    } catch (PDOException $e) {
        $message = 'Error archiving book.';
        $message_type = 'error';
    }
}

// Restore Book
if ($action === 'restore' && $book_id > 0) {
    try {
        $stmt = $pdo->prepare("UPDATE books SET is_archived = 0 WHERE id = ?");
        $stmt->execute([$book_id]);
        $message = 'Book restored successfully!';
        $message_type = 'success';
    } catch (PDOException $e) {
        $message = 'Error restoring book.';
        $message_type = 'error';
    }
}

// Get categories for dropdown
try {
    $categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// Get books with category names
try {
    $show_archived = isset($_GET['show_archived']);
    $query = "
        SELECT b.*, c.name as category_name 
        FROM books b 
        LEFT JOIN categories c ON b.category_id = c.id 
    ";
    if (!$show_archived) {
        $query .= " WHERE b.is_archived = 0";
    }
    $query .= " ORDER BY b.created_at DESC";
    
    $books = $pdo->query($query)->fetchAll();
} catch (PDOException $e) {
    $books = [];
}

// Get book to edit
$edit_book = null;
if ($action === 'edit' && $book_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
        $stmt->execute([$book_id]);
        $edit_book = $stmt->fetch();
    } catch (PDOException $e) {
        $edit_book = null;
    }
}

// Get statistics
try {
    $total_books = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
    $active_books = $pdo->query("SELECT COUNT(*) FROM books WHERE is_archived = 0")->fetchColumn();
    $total_reservations = $pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'active'")->fetchColumn();
    $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
} catch (PDOException $e) {
    $total_books = 0;
    $active_books = 0;
    $total_reservations = 0;
    $total_users = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Book Reservation System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="main-content">
            <h1>Admin Dashboard</h1>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_books; ?></div>
                    <div class="stat-label">Total Books</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $active_books; ?></div>
                    <div class="stat-label">Active Books</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_reservations; ?></div>
                    <div class="stat-label">Active Reservations</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_users; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>
            
            <h2>Manage Books</h2>
            
            <div class="action-buttons" style="margin-bottom: 20px;">
                <button onclick="showAddForm()" class="btn btn-primary">Add New Book</button>
                <a href="?show_archived=1" class="btn btn-secondary">Show Archived</a>
                <a href="dashboard.php" class="btn btn-secondary">Show Active Only</a>
            </div>
            
            <!-- Add/Edit Book Form -->
            <div id="bookForm" style="display: <?php echo $edit_book ? 'block' : 'none'; ?>; margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <h3><?php echo $edit_book ? 'Edit Book' : 'Add New Book'; ?></h3>
                <form method="POST" action="">
                    <input type="hidden" name="edit_id" value="<?php echo $edit_book ? $edit_book['id'] : ''; ?>">
                    
                    <div class="form-group">
                        <label for="title">Title:</label>
                        <input type="text" id="title" name="title" required 
                               value="<?php echo $edit_book ? htmlspecialchars($edit_book['title']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="author">Author:</label>
                        <input type="text" id="author" name="author" required 
                               value="<?php echo $edit_book ? htmlspecialchars($edit_book['author']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="isbn">ISBN:</label>
                        <input type="text" id="isbn" name="isbn" 
                               value="<?php echo $edit_book ? htmlspecialchars($edit_book['isbn']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id">Category:</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" 
                                        <?php echo ($edit_book && $edit_book['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description" rows="4"><?php echo $edit_book ? htmlspecialchars($edit_book['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="total_copies">Total Copies:</label>
                        <input type="number" id="total_copies" name="total_copies" required min="1" 
                               value="<?php echo $edit_book ? $edit_book['total_copies'] : 1; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="available_copies">Available Copies:</label>
                        <input type="number" id="available_copies" name="available_copies" required min="0" 
                               value="<?php echo $edit_book ? $edit_book['available_copies'] : 1; ?>">
                    </div>
                    
                    <button type="submit" name="save_book" class="btn btn-primary">
                        <?php echo $edit_book ? 'Update Book' : 'Add Book'; ?>
                    </button>
                    <button type="button" onclick="hideForm()" class="btn btn-secondary">Cancel</button>
                </form>
            </div>
            
            <!-- Books Table -->
            <?php if (empty($books)): ?>
                <p>No books found.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Category</th>
                            <th>Total</th>
                            <th>Available</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($books as $book): ?>
                            <tr>
                                <td><?php echo $book['id']; ?></td>
                                <td><?php echo htmlspecialchars($book['title']); ?></td>
                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                <td><?php echo htmlspecialchars($book['category_name'] ?? 'N/A'); ?></td>
                                <td><?php echo $book['total_copies']; ?></td>
                                <td><?php echo $book['available_copies']; ?></td>
                                <td>
                                    <?php if ($book['is_archived']): ?>
                                        <span style="color: #dc3545;">Archived</span>
                                    <?php else: ?>
                                        <span style="color: #28a745;">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?action=edit&id=<?php echo $book['id']; ?>" class="btn btn-primary btn-small">Edit</a>
                                        <?php if ($book['is_archived']): ?>
                                            <a href="?action=restore&id=<?php echo $book['id']; ?>" class="btn btn-success btn-small">Restore</a>
                                        <?php else: ?>
                                            <a href="?action=archive&id=<?php echo $book['id']; ?>" class="btn btn-danger btn-small" 
                                               onclick="return confirm('Are you sure you want to archive this book?');">Archive</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <?php include '../includes/sidebar.php'; ?>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        function showAddForm() {
            document.getElementById('bookForm').style.display = 'block';
            document.querySelector('#bookForm h3').textContent = 'Add New Book';
            document.querySelector('#bookForm form').reset();
            document.querySelector('input[name="edit_id"]').value = '';
            window.scrollTo(0, document.getElementById('bookForm').offsetTop);
        }
        
        function hideForm() {
            document.getElementById('bookForm').style.display = 'none';
        }
        
        <?php if ($edit_book): ?>
            document.getElementById('bookForm').style.display = 'block';
        <?php endif; ?>
    </script>
</body>
</html>
