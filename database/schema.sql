-- =============================================================================
-- Book Reservation System — portable database setup
-- =============================================================================
-- Run once on any PC (phpMyAdmin → Import, or command line):
--   mysql -u root -p < database/schema.sql
--
-- Default admin after import:
--   Email:    admin@university.edu
--   Password: admin123
--
-- App database settings: config/database.php (default root, no password)
-- Per-machine override: copy config/database.local.php.example → database.local.php
-- =============================================================================

CREATE DATABASE IF NOT EXISTS book_reservation
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE book_reservation;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS reservations;
DROP TABLE IF EXISTS books;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    isbn VARCHAR(20),
    category_id INT,
    description TEXT,
    image_url VARCHAR(255),
    total_copies INT DEFAULT 1,
    available_copies INT DEFAULT 1,
    is_archived TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    reservation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'returned', 'cancelled') DEFAULT 'active',
    return_date TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    book_id INT,
    comment_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin password: admin123 (bcrypt)
INSERT INTO users (email, password, full_name, role) VALUES
('admin@university.edu', '$2y$10$A01Sz5pZqXzr7p389tDH0Obi0Y7PIvpHUvk9XYqlWNpFbwSgkkFhW', 'System Administrator', 'admin');

INSERT INTO categories (name, description) VALUES
('Computer Science', 'Books related to programming, algorithms, and computer science'),
('Mathematics', 'Mathematics textbooks and reference books'),
('Physics', 'Physics textbooks and reference materials'),
('Literature', 'Classic and contemporary literature'),
('History', 'Historical books and documents');

INSERT INTO books (title, author, isbn, category_id, description, total_copies, available_copies) VALUES
('Introduction to Algorithms', 'Thomas H. Cormen', '978-0262033848', 1, 'Comprehensive introduction to modern algorithm design and analysis', 5, 5),
('Clean Code', 'Robert C. Martin', '978-0132350884', 1, 'A handbook of agile software craftsmanship', 3, 3),
('Calculus: Early Transcendentals', 'James Stewart', '978-1285740621', 2, 'Comprehensive calculus textbook', 4, 4),
('Linear Algebra Done Right', 'Sheldon Axler', '978-3319110790', 2, 'Linear algebra textbook focusing on abstract vector spaces', 2, 2),
('University Physics', 'Hugh D. Young', '978-0133969290', 3, 'Comprehensive physics textbook for university students', 3, 3),
('To Kill a Mockingbird', 'Harper Lee', '978-0061120084', 4, 'Classic American novel about racial injustice', 5, 5),
('1984', 'George Orwell', '978-0451524935', 4, 'Dystopian social science fiction novel', 4, 4),
('The History of the Ancient World', 'Susan Wise Bauer', '978-0393059748', 5, 'Comprehensive history of ancient civilizations', 2, 2);
