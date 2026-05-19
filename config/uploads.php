<?php
/**
 * Book cover / thumbnail uploads.
 */

require_once __DIR__ . '/paths.php';

define('BOOK_UPLOAD_DIR', APP_ROOT . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'books');
define('BOOK_UPLOAD_MAX_BYTES', 2 * 1024 * 1024);

const BOOK_UPLOAD_MIME_MAP = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
];

function ensureBookUploadDir(): void
{
    if (!is_dir(BOOK_UPLOAD_DIR)) {
        mkdir(BOOK_UPLOAD_DIR, 0755, true);
    }
}

/**
 * Public URL for a stored cover path (or external URL).
 */
function bookCoverUrl(?string $imagePath): ?string
{
    if ($imagePath === null || $imagePath === '') {
        return null;
    }
    if (preg_match('#^https?://#i', $imagePath)) {
        return $imagePath;
    }
    return url($imagePath);
}

function deleteBookThumbnailFile(?string $imagePath): void
{
    if ($imagePath === null || $imagePath === '') {
        return;
    }
    if (preg_match('#^https?://#i', $imagePath)) {
        return;
    }
    $full = APP_ROOT . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $imagePath);
    if (is_file($full)) {
        @unlink($full);
    }
}

/**
 * @return array{ok: true, path: string}|array{ok: false, error: string}
 */
function saveBookThumbnail(int $bookId, array $file, ?string $oldImagePath = null): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'No file uploaded.'];
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Upload failed. Please try again.'];
    }
    if (($file['size'] ?? 0) > BOOK_UPLOAD_MAX_BYTES) {
        return ['ok' => false, 'error' => 'Image must be 2 MB or smaller.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!isset(BOOK_UPLOAD_MIME_MAP[$mime])) {
        return ['ok' => false, 'error' => 'Only JPG, PNG, GIF, and WebP images are allowed.'];
    }

    ensureBookUploadDir();

    $ext = BOOK_UPLOAD_MIME_MAP[$mime];
    $relative = 'assets/uploads/books/book_' . $bookId . '.' . $ext;
    $target = APP_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return ['ok' => false, 'error' => 'Could not save the uploaded image.'];
    }

    if ($oldImagePath && $oldImagePath !== $relative) {
        deleteBookThumbnailFile($oldImagePath);
    }

    return ['ok' => true, 'path' => $relative];
}

function updateBookImageUrl(PDO $pdo, int $bookId, ?string $path): void
{
    $stmt = $pdo->prepare('UPDATE books SET image_url = ? WHERE id = ?');
    $stmt->execute([$path, $bookId]);
}
