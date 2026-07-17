<?php
// bootstrap.php — Husoma config.php yako (yenye host/name/user/pass ya MySQL)
// na kufungua muunganisho wa database + kuunda majedwali kama hayapo.
// Faili hii HAIBADILISHI config.php yako — inaitumia tu.

declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

// Chochote kikienda vibaya (onyo, notice, exception, fatal error) sasa
// kitarudi kama JSON halali badala ya ukurasa wa HTML — hii inazuia
// hitilafu ya "Jibu lisilotarajiwa kutoka kwa server" upande wa frontend
// na badala yake inaonyesha ujumbe halisi wa kosa.
ini_set('display_errors', '0');
error_reporting(E_ALL);

set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    http_response_code(500);
    echo json_encode(['error' => "Hitilafu ya PHP: $message (mstari $line wa " . basename($file) . ')']);
    exit;
});

set_exception_handler(function (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Hitilafu: ' . $e->getMessage()]);
    exit;
});

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
        }
        echo json_encode(['error' => 'Hitilafu kubwa ya PHP: ' . $err['message'] . ' (mstari ' . $err['line'] . ' wa ' . basename($err['file']) . ')']);
    }
});

$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'config.php haijapatikana.']);
    exit;
}

$config = require $configPath;

$dbConf = $config['db'] ?? null;
if (!$dbConf) {
    http_response_code(500);
    echo json_encode(['error' => 'Sehemu ya "db" haipo ndani ya config.php.']);
    exit;
}

$dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=%s',
    $dbConf['host'],
    $dbConf['name'],
    $dbConf['charset'] ?? 'utf8mb4'
);

try {
    $pdo = new PDO($dsn, $dbConf['user'], $dbConf['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Imeshindikana kuunganisha database: ' . $e->getMessage()]);
    exit;
}

$pdo->exec("
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(190) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    jina VARCHAR(190) NOT NULL,
    salio_awali DECIMAL(14,2) NOT NULL DEFAULT 0,
    bajeti_mwezi DECIMAL(14,2) NOT NULL DEFAULT 0,
    bajeti_allocated DECIMAL(14,2) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS transactions (
    id VARCHAR(20) PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(20) NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    category VARCHAR(50) NULL,
    note TEXT NULL,
    `date` DATE NOT NULL,
    created_at BIGINT NOT NULL,
    updated_at BIGINT NULL,
    INDEX idx_tx_user_date (user_id, `date`),
    CONSTRAINT fk_tx_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS deleted_transactions (
    id VARCHAR(20) PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(20) NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    category VARCHAR(50) NULL,
    note TEXT NULL,
    `date` DATE NOT NULL,
    created_at BIGINT NOT NULL,
    deleted_at BIGINT NOT NULL,
    INDEX idx_del_user_date (user_id, `date`),
    CONSTRAINT fk_del_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");