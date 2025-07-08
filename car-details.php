<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'db.php';            
require_once 'userStorage.php';   
require_once 'bookingStorage.php';
require_once 'carStorage.php';    
require_once 'auth.php';

session_start();

$userStorage = new UserStorage($pdo);
$bookingStorage = new BookingStorage($pdo);
$carStorage = new CarStorage($pdo);
$auth = new Auth($userStorage);

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Invalid or missing car ID.");
}

$car = $carStorage->findById($id);

if (!$car) {
    die("Car not found for ID: " . htmlspecialchars($id));
}

$logged_in_user = $auth->authenticated_user();

$errors = [];
$success = false;
$bookingDetails = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';

    if (empty($startDate) || empty($endDate)) {
        $errors[] = "Both start and end dates are required.";
    } elseif (strtotime($startDate) > strtotime($endDate)) {
        $errors[] = "Start date must be earlier than or equal to the end date.";
    } elseif (strtotime($startDate) < time()) {
        $errors[] = "Start date cannot be in the past.";
    } else {
        // Fetch bookings for this car
        $bookings = $bookingStorage->findByCarId($id);

        foreach ($bookings as $booking) {
            $existingStart = strtotime($booking['start_date']);
            $existingEnd = strtotime($booking['end_date']);
            $newStart = strtotime($startDate);
            $newEnd = strtotime($endDate);

            if (
                ($newStart >= $existingStart && $newStart <= $existingEnd) ||
                ($newEnd >= $existingStart && $newEnd <= $existingEnd) ||
                ($newStart <= $existingStart && $newEnd >= $existingEnd)
            ) {
                $errors[] = "The car is already booked for the selected dates.";
                break;
            }
        }

        if (empty($errors)) {
            $booking = [
                'car_id' => $id,
                'user_id' => $logged_in_user['id'] ?? null,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ];

            $bookingStorage->add($booking);
            $success = true;
            $bookingDetails = $booking;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="styles.css" />
    <title>Car Details</title>
</head>
<body>
    <?php include 'menu.php'; ?>

    <div class="car-details-container">
        <div class="car-details-left">
            <img src="<?= htmlspecialchars($car['image']) ?>" alt="<?= htmlspecialchars($car['brand'] . ' ' . $car['model']) ?>">
            <h1><?= htmlspecialchars($car['brand'] . ' ' . $car['model']) ?></h1>
            <p>Year: <?= htmlspecialchars($car['year']) ?></p>
            <p>Transmission: <?= htmlspecialchars($car['transmission']) ?></p>
            <p>Fuel Type: <?= htmlspecialchars($car['fuel_type']) ?></p>
            <p>Seats: <?= htmlspecialchars($car['passengers']) ?></p>
            <p>Price: <?= htmlspecialchars($car['daily_price_huf']) ?> HUF/day</p>
        </div>
        <div class="car-details-right">
            <?php if ($success): ?>
                <p style="color: green;">Booking successful!</p>
                <h2>Booking Details:</h2>
                <p>Car: <?= htmlspecialchars($car['brand'] . ' ' . $car['model']) ?></p>
                <p>Start Date: <?= htmlspecialchars($bookingDetails['start_date']) ?></p>
                <p>End Date: <?= htmlspecialchars($bookingDetails['end_date']) ?></p>
            <?php else: ?>
                <?php if ($errors): ?>
                    <ul style="color: red;">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if ($logged_in_user): ?>
                    <h2>Book this Car:</h2>
                    <form method="POST">
                        <label>Start Date:
                            <input type="date" name="start_date" required>
                        </label>
                        <label>End Date:
                            <input type="date" name="end_date" required>
                        </label>
                        <button type="submit">Book Now</button>
                    </form>
                <?php else: ?>
                    <p>Please <a href="login.php">log in</a> to book this car.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
