<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Haujaingia']);
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Transaction.php';

use App\Database;
use App\Transaction;

$config = require __DIR__ . '/../config.php';
Database::init($config);

$userId = $_SESSION['user_id'];
$transaction = new Transaction();
$transactions = $transaction->getAllForUser($userId);

echo json_encode([
    'success' => true,
    'transactions' => $transactions
]);
