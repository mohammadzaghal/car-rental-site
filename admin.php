<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$db_host = 'sql311.infinityfree.com';
$db_name = 'if0_39399445_db_users';
$db_user = 'if0_39399445';
$db_pass = 'Alzaghal1010';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

require_once 'userStorage.php';
require_once 'auth.php';

session_start();

$storage = new UserStorage($pdo);
$auth = new Auth($storage);

if (!$auth->is_authenticated() || !$auth->authorize(['admin'])) {
    die("Access denied. Only admins can access this page.");
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = htmlspecialchars(trim($_POST['id']));
    $brand = htmlspecialchars(trim($_POST['brand']));
    $model = htmlspecialchars(trim($_POST['model']));
    $year = filter_var($_POST['year'], FILTER_VALIDATE_INT, [
        "options" => ["min_range" => 1900, "max_range" => date("Y")],
    ]);
    $transmission = htmlspecialchars(trim($_POST['transmission']));
    $fuel_type = htmlspecialchars(trim($_POST['fuel_type']));
    $passengers = filter_var($_POST['passengers'], FILTER_VALIDATE_INT, [
        "options" => ["min_range" => 1, "max_range" => 11],
    ]);
    $daily_price_huf = filter_var($_POST['daily_price_huf'], FILTER_VALIDATE_INT, [
        "options" => ["min_range" => 1],
    ]);
    $image = filter_var($_POST['image'], FILTER_VALIDATE_URL);

    // Validate uniqueness of car ID
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM cars WHERE id = ?");
    $stmt->execute([$id]);
    if (empty($id)) {
        $errors[] = "Car ID is required.";
    } elseif ($stmt->fetchColumn() > 0) {
        $errors[] = "Car ID already exists. Please choose a unique ID.";
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM cars WHERE brand = ? AND model = ? AND year = ?");
    $stmt->execute([$brand, $model, $year]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = "A car with the same Brand, Model, and Year already exists.";
    }

    if (empty($brand)) $errors[] = "Brand is required.";
    if (empty($model)) $errors[] = "Model is required.";
    if (!$year) $errors[] = "Year must be a valid year (1900 - current year).";
    if (!in_array($transmission, ['Automatic', 'Manual'], true)) {
        $errors[] = "Transmission must be either 'Automatic' or 'Manual'.";
    }
    if (!in_array($fuel_type, ['Petrol', 'Diesel', 'Electric'], true)) {
        $errors[] = "Fuel type must be 'Petrol', 'Diesel', or 'Electric'.";
    }
    if (!$passengers) $errors[] = "Passengers must be between 1 and 11.";
    if (!$daily_price_huf) $errors[] = "Daily price must be greater than 0.";
    if (!$image || !preg_match('/\.(jpg|jpeg|png|webp)$/i', $image)) {
        $errors[] = "A valid image URL ending with .jpg, .jpeg, .png, or .webp is required.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO cars (id, brand, model, year, transmission, fuel_type, passengers, daily_price_huf, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id, $brand, $model, $year, $transmission, $fuel_type, $passengers, $daily_price_huf, $image]);
        $success = true;
    }
}

$stmt = $pdo->query("SELECT * FROM bookings ORDER BY id DESC");
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'menu.php'; ?>
    <h1>Admin Dashboard</h1>
    <div class="admin-container">
        <div class="add-car-section">
            <h2>Add New Car</h2>
            <?php if ($success): ?>
                <p style="color: green;">Car added successfully!</p>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <ul style="color: red;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <form method="POST">
                <label>ID:
                    <input type="text" name="id" value="<?= htmlspecialchars($_POST['id'] ?? '') ?>" required>
                </label><br>
                <label>Brand:
                    <input type="text" name="brand" value="<?= htmlspecialchars($_POST['brand'] ?? '') ?>" required>
                </label><br>
                <label>Model:
                    <input type="text" name="model" value="<?= htmlspecialchars($_POST['model'] ?? '') ?>" required>
                </label><br>
                <label>Year:
                    <input type="number" name="year" value="<?= htmlspecialchars($_POST['year'] ?? '') ?>" required>
                </label><br>
                <label>Transmission:
                    <select name="transmission" required>
                        <option value="Automatic" <?= ($_POST['transmission'] ?? '') === 'Automatic' ? 'selected' : '' ?>>Automatic</option>
                        <option value="Manual" <?= ($_POST['transmission'] ?? '') === 'Manual' ? 'selected' : '' ?>>Manual</option>
                    </select>
                </label><br>
                <label>Fuel Type:
                    <select name="fuel_type" required>
                        <option value="Petrol" <?= ($_POST['fuel_type'] ?? '') === 'Petrol' ? 'selected' : '' ?>>Petrol</option>
                        <option value="Diesel" <?= ($_POST['fuel_type'] ?? '') === 'Diesel' ? 'selected' : '' ?>>Diesel</option>
                        <option value="Electric" <?= ($_POST['fuel_type'] ?? '') === 'Electric' ? 'selected' : '' ?>>Electric</option>
                    </select>
                </label><br>
                <label>Seats:
                    <input type="number" name="passengers" value="<?= htmlspecialchars($_POST['passengers'] ?? '') ?>" required>
                </label><br>
                <label>Price (HUF/day):
                    <input type="number" name="daily_price_huf" value="<?= htmlspecialchars($_POST['daily_price_huf'] ?? '') ?>" required>
                </label><br>
                <label>Image URL:
                    <input type="url" name="image" value="<?= htmlspecialchars($_POST['image'] ?? '') ?>" required>
                </label><br>
                <button type="submit">Add Car</button>
            </form>
        </div>

        <div class="bookings-section">
            <h2>All Bookings</h2>
            <table>
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Car</th>
                        <th>User</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td><?= htmlspecialchars($booking['id']) ?></td>
                            <td><?= htmlspecialchars($booking['car_id']) ?></td>
                            <td><?= htmlspecialchars($booking['user_id']) ?></td>
                            <td><?= htmlspecialchars($booking['start_date']) ?></td>
                            <td><?= htmlspecialchars($booking['end_date']) ?></td>
                            <td>
                                <form method="POST" action="delete_booking.php">
                                    <input type="hidden" name="booking_id" value="<?= htmlspecialchars($booking['id']) ?>">
                                    <button type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
