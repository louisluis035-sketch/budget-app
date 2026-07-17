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

$fullname = $_POST['fullname'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

$user = new User();

$result = $user->register($fullname, $email, $password);

if ($result) {
    echo "USER AMEHIFADHIWA";
} else {
    echo "REGISTRATION FAILED";
}
