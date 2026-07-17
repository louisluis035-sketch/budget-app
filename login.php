<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';

use App\Database;
use App\User;

$config = require __DIR__ . '/../config.php';
Database::init($config);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

$user = new User();
$userData = $user->verifyCredentials($email, $password);

if ($userData !== null) {
    // Login successful
    $_SESSION['user_id'] = $userData['id'];
    $_SESSION['fullname'] = $userData['fullname'];
    $_SESSION['email'] = $userData['email'];
    
    header('Location: dashboard.php');
    exit;
} else {
    // Login failed
    header('Location: index.php?message=' . urlencode('Barua pepe au nenosiri si sahihi.') . '&type=error');
    exit;
}
