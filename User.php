<?php

namespace App;

require_once __DIR__ . '/Database.php';

class User
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([strtolower(trim($email))]);

        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function register(string $fullname, string $email, string $password): bool
    {
        try {
            $fullname = trim($fullname);
            $email = strtolower(trim($email));

            if (empty($fullname) || empty($email) || empty($password)) {
                die("Error: Tafadhali jaza taarifa zote.");
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                die("Error: Email si sahihi.");
            }

            if ($this->findByEmail($email)) {
                die("Error: Email tayari imesajiliwa.");
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $this->db->prepare("
                INSERT INTO users (fullname, email, password_hash)
                VALUES (?, ?, ?)
            ");

            if (!$stmt->execute([$fullname, $email, $passwordHash])) {
                print_r($stmt->errorInfo());
                exit;
            }

            return true;

        } catch (\PDOException $e) {
            die("PDO ERROR: " . $e->getMessage());
        }
    }

    public function verifyCredentials(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);

        if (!$user) {
            return null;
        }

        if (password_verify($password, $user['password_hash'])) {
            return $user;
        }

        return null;
    }
}