# Book Reservation System

A university project for a second-year computer science course - a dynamic website using Native PHP, HTML, CSS, JavaScript, and MySQL for managing book reservations.

## Features

### User Features
- **Authentication**: Register and login system with email/phone and password
- **Browse Books**: Search and filter books by title, author, category, or description
- **Reserve Books**: One-click reservation of available books
- **My Reservations**: View, return, or cancel active reservations
- **Comments**: Post questions and comments in the sidebar

### Admin Features
- **Dashboard**: Overview of system statistics (books, users, reservations)
- **CRUD Operations**: Add, edit, view, and archive books
- **Book Management**: Manage book categories, copies, and availability

### Security Features
- **Password Hashing**: Uses `password_hash()` for secure password storage
- **SQL Injection Prevention**: All database queries use PDO prepared statements
- **Session Management**: Secure session handling with authentication checks
- **Role-Based Access**: Separate access levels for users and admins

## Project Structure

```
book-reservation-system/
├── admin/
│   └── dashboard.php          # Admin dashboard with CRUD operations
├── assets/
│   └── css/
│       └── style.css          # Main stylesheet (responsive design)
├── auth/
│   ├── login.php             # User login page
│   ├── logout.php            # Logout processing
│   └── register.php          # User registration page
├── config/
│   ├── auth.php              # Session management functions
│   └── database.php          # Database connection (PDO)
├── database/
│   └── schema.sql            # Database schema and sample data
├── includes/
│   ├── footer.php            # Footer component
│   ├── header.php            # Header with navigation
│   └── sidebar.php           # Sidebar with comments
├── add_comment.php           # Comment processing
├── index.php                 # Home page (public)
├── my_reservations.php       # User's reservations view
├── process_reservation.php   # Reservation processing
├── reservations.php          # Book browsing and reservation
└── README.md                 # This file
```

## Database Schema

The system uses the following tables:
- **users**: Stores user information and authentication data
- **categories**: Organizes books into categories
- **books**: Stores book details and availability
- **reservations**: Tracks user book reservations
- **comments**: Stores user comments and questions

## Installation Instructions

### Prerequisites
- EasyPHP or similar local server (XAMPP, WAMP, MAMP)
- MySQL database
- PHP 7.0 or higher
- Modern web browser

### Setup Steps

1. **Extract/Place Files**
   - Place the project folder in your web server's root directory (e.g., `C:\Program Files (x86)\EasyPHP-Devserver-17\eds-www\`)

2. **Create Database**
   - Open phpMyAdmin (usually at `http://127.0.0.1/phpmyadmin`)
   - Create a new database named `book_reservation`
   - Import the SQL file: `database/schema.sql`
   - This will create all tables and insert sample data

3. **Configure Database Connection**
   - Defaults in `config/database.php` use `root` with an empty password (typical XAMPP/WAMP).
   - On another PC, copy `config/database.local.php.example` to `config/database.local.php` and set your MySQL password there.

4. **Start the Server**
   - Launch EasyPHP (or your local server)
   - Ensure Apache and MySQL are running

5. **Access the Application**
   - Apache/XAMPP: `http://localhost/empty/` (folder name may vary)
   - PHP built-in server (from project folder): `php -S localhost:8000` then open `http://localhost:8000/`
   - Navigation URLs work in subfolders and with the built-in server automatically.

## Default Credentials

### Admin Account
- **Email**: admin@university.edu
- **Password**: admin123

### User Account
- Register a new account through the registration page
- Or use the admin account for testing

## Usage Guide

### For Students/Users

1. **Register**: Click "Register" on the home page
2. **Login**: Use your email and password
3. **Browse Books**: Navigate to "Reservations" to search and filter books
4. **Reserve**: Click "Reserve" on any available book
5. **View Reservations**: Check "My Reservations" to see your active reservations
6. **Return/Cancel**: Use the action buttons to return or cancel reservations

### For Admins

1. **Login**: Use the admin credentials
2. **Dashboard**: View system statistics and manage books
3. **Add Book**: Click "Add New Book" and fill in the details
4. **Edit Book**: Click "Edit" on any book to modify its information
5. **Archive Book**: Click "Archive" to remove a book from public view (can be restored)
6. **View All**: Toggle between active and archived books

## Technical Implementation

### Security Measures
- **Prepared Statements**: All SQL queries use PDO prepared statements to prevent SQL injection
- **Password Hashing**: Passwords are hashed using PHP's `password_hash()` with bcrypt
- **Input Sanitization**: User inputs are sanitized using `filter_input()` and `htmlspecialchars()`
- **Session Security**: Sessions are properly managed with secure session handling

### Responsive Design
- Mobile-friendly layout using CSS Grid and Flexbox
- Breakpoints for tablets (768px) and mobile devices (480px)
- Clean, modern UI with gradient header and card-based layout

### Database Relationships
- Books are categorized using foreign key relationships
- Reservations link users to books with status tracking
- Comments can be associated with specific books or general discussions

## Presentation Tips

### Key Points to Highlight
1. **Security**: Emphasize the use of prepared statements and password hashing
2. **User Experience**: Show the responsive design and intuitive navigation
3. **Database Design**: Explain the normalized database structure
4. **CRUD Operations**: Demonstrate the admin dashboard functionality
5. **Authentication**: Explain the session-based authentication system

### Demonstration Flow
1. Show the public home page
2. Demonstrate user registration
3. Show the login process
4. Browse and search for books
5. Make a reservation
6. Show "My Reservations" page
7. Login as admin
8. Demonstrate admin dashboard
9. Add/edit/archive a book
10. Show the complete user experience

## Troubleshooting

### Common Issues

**Database Connection Error**
- Verify MySQL is running
- Check database credentials in `config/database.php`
- Ensure the database `book_reservation` exists

**Page Not Found (404)**
- Verify files are in the correct directory
- Check the URL path matches your folder structure

**Session Not Working**
- Ensure cookies are enabled in your browser
- Check PHP session configuration

**Images Not Displaying**
- The system uses emoji icons for simplicity
- To add real images, update the `image_url` field in the books table

## Future Enhancements

Potential improvements for future development:
- Email notifications for reservations
- Book rating and review system
- Advanced search with filters
- Export reservations to PDF
- Integration with library barcode system
- Mobile app version

## License

This is a university project for educational purposes.

## Contact

For questions or issues, contact: support@university.edu

---

**Note**: This project was developed as a second-year computer science course assignment to demonstrate proficiency in PHP, MySQL, and web development best practices.
