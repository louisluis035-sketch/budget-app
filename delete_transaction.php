<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Haujaingia']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
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
$data = json_decode(file_get_contents('php://input'), true);
$transactionId = $data['id'] ?? null;

if (!$transactionId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID si sahihi']);
    exit;
}

$transaction = new Transaction();

if ($transaction->delete($userId, $transactionId)) {
    echo json_encode(['success' => true, 'message' => 'Miamala iliyofuta']);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Haiwezekani kufuta']);
}
