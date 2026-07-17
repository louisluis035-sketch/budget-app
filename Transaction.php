<?php
namespace App;

require_once __DIR__ . '/Database.php';

class Transaction
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = \App\Database::getConnection();
    }

    public function getAllForUser(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM transactions WHERE user_id = :user_id ORDER BY date DESC, id DESC');
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function add(int $userId, string $type, float $amount, string $date, ?string $category = null, ?string $note = null): int|false
    {
        $stmt = $this->db->prepare('INSERT INTO transactions (user_id, type, category, note, amount, date) VALUES (:user_id, :type, :category, :note, :amount, :date)');
        if (!$stmt->execute([
            ':user_id' => $userId,
            ':type' => $type,
            ':category' => $category,
            ':note' => $note,
            ':amount' => $amount,
            ':date' => $date,
        ])) {
            return false;
        }
        return (int) $this->db->lastInsertId();
    }

    public function update(int $userId, int $transactionId, string $type, float $amount, string $date, ?string $category = null, ?string $note = null): bool
    {
        $stmt = $this->db->prepare('UPDATE transactions SET type = :type, category = :category, note = :note, amount = :amount, date = :date WHERE id = :id AND user_id = :user_id');
        return $stmt->execute([
            ':id' => $transactionId,
            ':user_id' => $userId,
            ':type' => $type,
            ':category' => $category,
            ':note' => $note,
            ':amount' => $amount,
            ':date' => $date,
        ]);
    }

    public function delete(int $userId, int $transactionId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM transactions WHERE id = :id AND user_id = :user_id');
        return $stmt->execute([':id' => $transactionId, ':user_id' => $userId]);
    }
}
