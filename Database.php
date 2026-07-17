<?php
namespace App;

class Database
{
    private static ?\PDO $pdo = null;
    private static array $config;

    public static function init(array $config): void
    {
        self::$config = $config;
    }

    public static function getConnection(): \PDO
    {
        if (self::$pdo === null) {
            $db = self::$config['db'];
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $db['host'], $db['name'], $db['charset']);
            self::$pdo = new \PDO($dsn, $db['user'], $db['pass'], [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);
        }

        return self::$pdo;
    }

    public static function encrypt(string $plaintext): string
    {
        $key = hash('sha256', self::$config['encryption_key'], true);
        $iv = random_bytes(16);
        $ciphertext = openssl_encrypt($plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $ciphertext);
    }

    public static function decrypt(string $ciphertext): string
    {
        $decoded = base64_decode($ciphertext, true);
        if ($decoded === false || strlen($decoded) < 17) {
            return '';
        }
        $key = hash('sha256', self::$config['encryption_key'], true);
        $iv = substr($decoded, 0, 16);
        $data = substr($decoded, 16);
        return openssl_decrypt($data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv) ?: '';
    }
}
