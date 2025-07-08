<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once 'db.php';
require_once 'userStorage.php';
require_once 'auth.php';

$auth = new Auth(new UserStorage($pdo));
$auth->logout();

header("Location: index.php");
exit;
