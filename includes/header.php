<?php
require_once dirname(__DIR__) . '/config/auth.php';
?>
<header>
    <div class="header-content">
        <div class="header-left">
            <?php
            $current_page = basename($_SERVER['PHP_SELF']);
            if ($current_page !== 'index.php'):
            ?>
                <a href="javascript:history.back()" class="back-button" title="Go Back">&larr; Back</a>
            <?php endif; ?>
            <div class="logo">
                <span>Book Reservation System</span>
            </div>
        </div>
        <nav>
            <ul>
                <li><a href="<?php echo url('index.php'); ?>">Home</a></li>
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <li><a href="<?php echo url('admin/dashboard.php'); ?>">Admin Dashboard</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo url('reservations.php'); ?>">Reservations</a></li>
                        <li><a href="<?php echo url('my_reservations.php'); ?>">My Reservations</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo url('auth/logout.php'); ?>">Logout (<?php echo htmlspecialchars(getCurrentUserName() ?? '', ENT_QUOTES, 'UTF-8'); ?>)</a></li>
                <?php else: ?>
                    <li><a href="<?php echo url('auth/login.php'); ?>">Login</a></li>
                    <li><a href="<?php echo url('auth/register.php'); ?>">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>
