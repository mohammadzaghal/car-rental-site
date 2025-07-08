

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';
require_once 'userStorage.php';
require_once 'auth.php';

session_start();

$errors = [];
$success = false;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';


    if (empty($fullname)) {
        $errors[] = "Full name is required.";
    } elseif (count(explode(' ', $fullname)) < 2) {
        $errors[] = "Full name must include both first and last names.";
    }

    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email must be a valid email address.";
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must include at least one uppercase letter.";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must include at least one number.";
    } elseif (!preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $password)) {
        $errors[] = "Password must include at least one special character.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        $userStorage = new UserStorage($pdo);
        $auth = new Auth($userStorage);

        $data = [
            'fullname' => $fullname,
            'email' => $email,
            'password' => $password,
            'role' => "user"
        ];

        if ($auth->user_exists($email)) {
            $errors[] = "An account with this email already exists.";
        } else {
            $auth->register($data);
            $success = true;
        }
    }
}

?>

<!DOCTYPE html>
<html>
<link rel="stylesheet" href="styles.css">

<head>
    <title>Registration</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'menu.php'; ?>

    <?php if ($success): ?>
        <p style="color: green;">Registration successful! You can now <a href="login.php">log in</a>.</p>
    <?php else: ?>
        <?php if (!empty($errors)): ?>
            <ul style="color: red;">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <form method="POST">
        <label>Full Name:</label>
        <input type="text" name="fullname" value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>" required>
        <br>

        <label>Email Address:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        <br>


        <label>Password:</label>
        <input type="password" name="password" required>
        <br>

        <label>Confirm Password:</label>
        <input type="password" name="confirm_password" required>
        <br>

        <button type="submit">Register</button>
    </form>

    <?php endif; ?>
</body>
</html>
