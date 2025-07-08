<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require_once 'db.php';
require_once 'userStorage.php';
require_once 'carStorage.php';
require_once 'bookingStorage.php';
require_once 'auth.php';

$auth = new Auth(new UserStorage($pdo));
$carStorage = new CarStorage($pdo);
$bookingStorage = new BookingStorage($pdo);

$logged_in_user = $auth->authenticated_user();

if (!$logged_in_user) {
    header("Location: login.php");
    exit();
}

$bookings = $bookingStorage->findByUserId($logged_in_user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title>Profile</title>
</head>
<body>
    <?php include 'menu.php'; ?>
    <div class="profile-container">
        <h2>My Reservations</h2>
        <div class="car-list">
            <?php if (empty($bookings)): ?>
                <p style="color: white; text-align: center;">No reservations found.</p>
            <?php else: ?>
                <?php foreach ($bookings as $booking): ?>
                    <?php $car = $carStorage->findById($booking['car_id']); ?>
                    <?php if ($car): ?>
                        <div class="car-card">
                            <img src="<?= htmlspecialchars($car['image']) ?>" alt="<?= htmlspecialchars($car['brand']) ?>">
                            <h3><?= htmlspecialchars($car['brand'] . ' ' . $car['model']) ?></h3>
                            <p><?= htmlspecialchars($car['passengers']) ?> seats - <?= htmlspecialchars($car['transmission']) ?></p>
                            <p><?= htmlspecialchars($booking['start_date']) ?> - <?= htmlspecialchars($booking['end_date']) ?></p>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
