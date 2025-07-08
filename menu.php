<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'db.php';
require_once 'userStorage.php';
require_once 'auth.php';

$auth = new Auth(new UserStorage($pdo));
$logged_in_user = $auth->authenticated_user();
?>
<header>
    <div class="header-container">
        <div class="left">
            <h1>IKarRental</h1>
            <p>Rent cars easily!</p>
        </div>
        <div class="center">
            <nav class="menu">
                <a href="index.php">Home</a>
                <?php if ($logged_in_user && isset($logged_in_user['role']) && $logged_in_user['role'] === 'admin'): ?>
                    <a href="admin.php">Admin Dashboard</a>
                <?php endif; ?>
            </nav>
        </div>
        <div class="right">
            <nav class="menu">
                <?php if ($logged_in_user): ?>
                    <a href="profile.php">Profile</a>
                    <span class="welcome">Welcome, <?= htmlspecialchars($logged_in_user['fullname']) ?>!</span>
                    <a href="logout.php" class="logout">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </div>
</header>
