<?php
/**
 * Admin Dashboard
 * Provides CRUD operations for books management
 */

require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/uploads.php';

// Require admin access
requireAdmin();

/**
 * After POST, redirect (PRG) so Back does not resubmit the form.
 */
function finishAdminPost(string $message, string $type, ?int $reopenEditId = null): void
{
    $_SESSION['admin_flash'] = ['message' => $message, 'type' => $type];

    if ($type === 'error' && $reopenEditId === null && isset($_POST['save_book']) && (int) ($_POST['edit_id'] ?? 0) === 0) {
        $_SESSION['admin_show_add_form'] = true;
    }

    $query = [];
    if (!empty($_POST['show_archived'])) {
        $query['show_archived'] = 1;
    }
    if ($type === 'error' && ($reopenEditId ?? 0) > 0) {
        $query['action'] = 'edit';
        $query['id'] = $reopenEditId;
    }

    $target = url('admin/dashboard.php');
    if ($query !== []) {
        $target .= '?' . http_build_query($query);
    }

    header('Location: ' . $target, true, 303);
    exit();
}

$action = $_GET['action'] ?? '';
$book_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$message = '';
$message_type = '';
$show_add_form = false;

if (!empty($_SESSION['admin_flash'])) {
    $message = $_SESSION['admin_flash']['message'];
    $message_type = $_SESSION['admin_flash']['type'];
    unset($_SESSION['admin_flash']);
}
if (!empty($_SESSION['admin_show_add_form'])) {
    $show_add_form = true;
    unset($_SESSION['admin_show_add_form']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    if (isset($_POST['save_book'])) {
        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $isbn = trim($_POST['isbn'] ?? '');
        $category_id = (int) ($_POST['category_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $total_copies = (int) ($_POST['total_copies'] ?? 0);
        $available_copies = (int) ($_POST['available_copies'] ?? 0);
        $edit_id = (int) ($_POST['edit_id'] ?? 0);

        if ($title === '' || $author === '' || $category_id <= 0) {
            finishAdminPost('Title, author, and category are required.', 'error', $edit_id > 0 ? $edit_id : null);
        } elseif ($total_copies < 1) {
            finishAdminPost('Total copies must be at least 1.', 'error', $edit_id > 0 ? $edit_id : null);
        } elseif ($available_copies < 0 || $available_copies > $total_copies) {
            finishAdminPost('Available copies must be between 0 and total copies.', 'error', $edit_id > 0 ? $edit_id : null);
        } else {
            try {
                $old_image = null;
                if ($edit_id > 0) {
                    $stmt = $pdo->prepare('SELECT image_url FROM books WHERE id = ?');
                    $stmt->execute([$edit_id]);
                    $old_image = $stmt->fetchColumn() ?: null;

                    $stmt = $pdo->prepare('
                        UPDATE books
                        SET title=?, author=?, isbn=?, category_id=?, description=?,
                            total_copies=?, available_copies=?, updated_at=NOW()
                        WHERE id=?
                    ');
                    $stmt->execute([$title, $author, $isbn, $category_id, $description,
                        $total_copies, $available_copies, $edit_id]);
                    $saved_id = $edit_id;
                    $message = 'Book updated successfully!';
                } else {
                    $stmt = $pdo->prepare('
                        INSERT INTO books (title, author, isbn, category_id, description, total_copies, available_copies)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ');
                    $stmt->execute([$title, $author, $isbn, $category_id, $description,
                        $total_copies, $available_copies]);
                    $saved_id = (int) $pdo->lastInsertId();
                    $message = 'Book added successfully!';
                }

                if (!empty($_POST['remove_thumbnail']) && $old_image !== null) {
                    deleteBookThumbnailFile($old_image);
                    updateBookImageUrl($pdo, $saved_id, null);
                    $old_image = null;
                }

                if (!empty($_FILES['thumbnail']['name'])) {
                    $upload = saveBookThumbnail($saved_id, $_FILES['thumbnail'], $old_image);
                    if ($upload['ok']) {
                        updateBookImageUrl($pdo, $saved_id, $upload['path']);
                    } else {
                        $message .= ' ' . $upload['error'];
                        $message_type = 'error';
                    }
                }

                if (($message_type ?? '') === 'error') {
                    finishAdminPost($message, 'error', $edit_id > 0 ? $edit_id : null);
                }
                finishAdminPost($message, 'success');
            } catch (PDOException $e) {
                error_log('Book save error: ' . $e->getMessage());
                finishAdminPost('Error saving book. Please try again.', 'error', $edit_id > 0 ? $edit_id : null);
            }
        }
    } elseif (isset($_POST['book_action'], $_POST['book_id'])) {
        $book_action = $_POST['book_action'];
        $target_id = (int) $_POST['book_id'];

        if ($target_id > 0 && in_array($book_action, ['archive', 'restore'], true)) {
            try {
                $is_archived = $book_action === 'archive' ? 1 : 0;
                $stmt = $pdo->prepare('UPDATE books SET is_archived = ? WHERE id = ?');
                $stmt->execute([$is_archived, $target_id]);
                finishAdminPost(
                    $book_action === 'archive' ? 'Book archived successfully!' : 'Book restored successfully!',
                    'success'
                );
            } catch (PDOException $e) {
                finishAdminPost('Error updating book status.', 'error');
            }
        }
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
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
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
            <div id="bookForm" style="display: <?php echo ($edit_book || $show_add_form) ? 'block' : 'none'; ?>; margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <h3><?php echo $edit_book ? 'Edit Book' : 'Add New Book'; ?></h3>
                <form method="POST" action="<?php echo url('admin/dashboard.php'); ?>" enctype="multipart/form-data">
                    <?php echo csrfField(); ?>
                    <?php if (!empty($show_archived)): ?>
                        <input type="hidden" name="show_archived" value="1">
                    <?php endif; ?>
                    <input type="hidden" name="edit_id" value="<?php echo $edit_book ? (int) $edit_book['id'] : ''; ?>">
                    
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

                    <div class="form-group">
                        <label for="thumbnail">Cover thumbnail (JPG, PNG, GIF, WebP — max 2 MB):</label>
                        <input type="file" id="thumbnail" name="thumbnail" accept="image/jpeg,image/png,image/gif,image/webp">
                        <?php if ($edit_book && !empty($edit_book['image_url'])): ?>
                            <div class="thumbnail-preview" style="margin-top: 12px;">
                                <img src="<?php echo htmlspecialchars(bookCoverUrl($edit_book['image_url']), ENT_QUOTES, 'UTF-8'); ?>"
                                     alt="Current cover" style="max-width: 120px; max-height: 160px; border-radius: 4px; display: block;">
                                <label style="display: block; margin-top: 8px;">
                                    <input type="checkbox" name="remove_thumbnail" value="1">
                                    Remove current cover
                                </label>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" name="save_book" class="btn btn-primary">
                        <?php echo $edit_book ? 'Update Book' : 'Add Book'; ?>
                    </button>
                    <button type="button" onclick="cancelBookForm()" class="btn btn-secondary">Cancel</button>
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
                            <th>Cover</th>
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
                                <td>
                                    <?php if (!empty($book['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars(bookCoverUrl($book['image_url']), ENT_QUOTES, 'UTF-8'); ?>"
                                             alt="" class="book-thumb-admin">
                                    <?php else: ?>
                                        <span class="book-thumb-admin book-thumb-admin--empty">—</span>
                                    <?php endif; ?>
                                </td>
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
                                        <?php
                                        $editQuery = ['action' => 'edit', 'id' => (int) $book['id']];
                                        if (!empty($show_archived)) {
                                            $editQuery['show_archived'] = 1;
                                        }
                                        ?>
                                        <a href="<?php echo url('admin/dashboard.php') . '?' . http_build_query($editQuery); ?>"
                                           class="btn btn-primary btn-small">Edit</a>
                                        <?php if ($book['is_archived']): ?>
                                            <form method="POST" action="<?php echo url('admin/dashboard.php'); ?>" style="display:inline">
                                                <?php echo csrfField(); ?>
                                                <?php if (!empty($show_archived)): ?>
                                                    <input type="hidden" name="show_archived" value="1">
                                                <?php endif; ?>
                                                <input type="hidden" name="book_action" value="restore">
                                                <input type="hidden" name="book_id" value="<?php echo (int) $book['id']; ?>">
                                                <button type="submit" class="btn btn-success btn-small">Restore</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" action="<?php echo url('admin/dashboard.php'); ?>" style="display:inline"
                                                  onsubmit="return confirm('Are you sure you want to archive this book?');">
                                                <?php echo csrfField(); ?>
                                                <?php if (!empty($show_archived)): ?>
                                                    <input type="hidden" name="show_archived" value="1">
                                                <?php endif; ?>
                                                <input type="hidden" name="book_action" value="archive">
                                                <input type="hidden" name="book_id" value="<?php echo (int) $book['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-small">Archive</button>
                                            </form>
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

        function cancelBookForm() {
            window.location.href = <?php echo json_encode(
                url('admin/dashboard.php') . (!empty($show_archived) ? '?show_archived=1' : ''),
                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
            ); ?>;
        }
    </script>
</body>
</html>
