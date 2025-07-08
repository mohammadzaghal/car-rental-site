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

$carStorage = new CarStorage($pdo);
$bookingStorage = new BookingStorage($pdo);
$userStorage = new UserStorage($pdo);
$auth = new Auth($userStorage);

$logged_in_user = $auth->authenticated_user();

$cars = $carStorage->findAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filters = [
        'transmission' => isset($_POST['transmission']) ? htmlspecialchars(trim($_POST['transmission'])) : null,
        'fuel_type' => isset($_POST['fuel_type']) ? htmlspecialchars(trim($_POST['fuel_type'])) : null,
        'passengers' => isset($_POST['passengers']) ? (int)$_POST['passengers'] : null,
        'daily_price_min' => isset($_POST['daily_price_min']) ? (int)$_POST['daily_price_min'] : null,
        'daily_price_max' => isset($_POST['daily_price_max']) ? (int)$_POST['daily_price_max'] : null,
        'start_date' => isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : null,
        'end_date' => isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : null,
    ];

    $filteredCars = [];
    foreach ($cars as $car) {
        $isAvailable = true;
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $allBookings = $bookingStorage->findByCarId($car['id']);
            foreach ($allBookings as $booking) {
                $existingStart = strtotime($booking['start_date']);
                $existingEnd = strtotime($booking['end_date']);
                $newStart = strtotime($filters['start_date']);
                $newEnd = strtotime($filters['end_date']);

                if (
                    ($newStart >= $existingStart && $newStart <= $existingEnd) ||
                    ($newEnd >= $existingStart && $newEnd <= $existingEnd) ||
                    ($newStart <= $existingStart && $newEnd >= $existingEnd)
                ) {
                    $isAvailable = false;
                    break;
                }
            }
        }

        if (
            $isAvailable &&
            (!$filters['transmission'] || strcasecmp($car['transmission'], $filters['transmission']) === 0) &&
            (!$filters['fuel_type'] || strcasecmp($car['fuel_type'], $filters['fuel_type']) === 0) &&
            (!$filters['passengers'] || $car['passengers'] >= $filters['passengers']) &&
            (!$filters['daily_price_min'] || $car['daily_price_huf'] >= $filters['daily_price_min']) &&
            (!$filters['daily_price_max'] || $car['daily_price_huf'] <= $filters['daily_price_max'])
        ) {
            $filteredCars[] = $car;
        }
    }

    $cars = $filteredCars;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title>Car Rental</title>
    <script>
        function toggleFilters() {
            const filters = document.getElementById('filters');
            if (filters.style.display === 'none' || filters.style.display === '') {
                filters.style.display = 'block';
            } else {
                filters.style.display = 'none';
            }
        }
    </script>
</head>
<body>
    <?php include 'menu.php'; ?>

    <button class="toggle-button" onclick="toggleFilters()">Filters</button>

    <div id="filters" style="display: none;">
        <form method="POST" class="form-controls">
            <label>Transmission:
                <select name="transmission">
                    <option value="" <?= empty($_POST['transmission']) ? 'selected' : '' ?>>Any</option>
                    <option value="Automatic" <?= (isset($_POST['transmission']) && $_POST['transmission'] === 'Automatic') ? 'selected' : '' ?>>Automatic</option>
                    <option value="Manual" <?= (isset($_POST['transmission']) && $_POST['transmission'] === 'Manual') ? 'selected' : '' ?>>Manual</option>
                </select>
            </label>
            <label>Fuel Type:
                <select name="fuel_type">
                    <option value="" <?= empty($_POST['fuel_type']) ? 'selected' : '' ?>>Any</option>
                    <option value="Petrol" <?= (isset($_POST['fuel_type']) && $_POST['fuel_type'] === 'Petrol') ? 'selected' : '' ?>>Petrol</option>
                    <option value="Diesel" <?= (isset($_POST['fuel_type']) && $_POST['fuel_type'] === 'Diesel') ? 'selected' : '' ?>>Diesel</option>
                    <option value="Electric" <?= (isset($_POST['fuel_type']) && $_POST['fuel_type'] === 'Electric') ? 'selected' : '' ?>>Electric</option>
                </select>
            </label>
            <label>Passengers:
                <input type="number" name="passengers" placeholder="Min Seats" value="<?= htmlspecialchars($_POST['passengers'] ?? '') ?>">
            </label>
            <label>Price Range:
                <input type="number" name="daily_price_min" placeholder="Min Price" value="<?= htmlspecialchars($_POST['daily_price_min'] ?? '') ?>">
                <input type="number" name="daily_price_max" placeholder="Max Price" value="<?= htmlspecialchars($_POST['daily_price_max'] ?? '') ?>">
            </label>
            <label>Start Date:
                <input type="date" name="start_date" value="<?= htmlspecialchars($_POST['start_date'] ?? '') ?>">
            </label>
            <label>End Date:
                <input type="date" name="end_date" value="<?= htmlspecialchars($_POST['end_date'] ?? '') ?>">
            </label>
            <div>
                <button type="submit">Filter</button>
                <button type="button" onclick="window.location.href='index.php'">Clear Filter</button>
            </div>
        </form>
    </div>

    <div class="car-list">
        <?php if (empty($cars)): ?>
            <p style="color: red; text-align: center; font-size: 18px;">No cars available with the specified filters.</p>
        <?php else: ?>
            <?php foreach ($cars as $car): ?>
                <div class="car-card">
                    <img src="<?= htmlspecialchars($car['image']) ?>" alt="<?= htmlspecialchars($car['brand']) ?>">
                    <h3><?= htmlspecialchars($car['brand'] . ' ' . $car['model']) ?></h3>
                    <p>Year: <?= htmlspecialchars($car['year']) ?></p>
                    <p>Transmission: <?= htmlspecialchars($car['transmission']) ?></p>
                    <p>Price: <?= htmlspecialchars($car['daily_price_huf']) ?> HUF/day</p>
                    <a href="car-details.php?id=<?= htmlspecialchars($car['id']) ?>">View Details</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
