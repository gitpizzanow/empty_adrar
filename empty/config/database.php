<?php
/**
 * Database Connection Configuration
 * Uses PDO for secure database connections with prepared statements
 */

// Database configuration
define('DB_HOST', '127.0.0.1'); // Using the IP is more stable than 'localhost'
define('DB_PORT', '3306');
define('DB_NAME', 'book_reservation');
define('DB_USER', 'root');
define('DB_PASS', '1234'); // Double check this is your Workbench password!
define('DB_CHARSET', 'utf8mb4');

/**
 * Get PDO database connection
 * @return PDO
 */
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

// Global database connection
$pdo = getDBConnection();
?>
