<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class NotificationRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(
        int $recipientUserId,
        string $type,
        string $title,
        string $body,
        ?string $linkPath = null
    ): int {
        $statement = $this->pdo->prepare(
            'INSERT INTO notifications (recipient_user_id, type, title, body, link_path, is_read, created_at)
             VALUES (:recipient_user_id, :type, :title, :body, :link_path, 0, NOW())'
        );
        $statement->execute([
            'recipient_user_id' => $recipientUserId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'link_path' => $linkPath,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function countUnreadByUserId(int $userId): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) AS total
             FROM notifications
             WHERE recipient_user_id = :recipient_user_id
               AND is_read = 0'
        );
        $statement->execute(['recipient_user_id' => $userId]);

        return (int) $statement->fetchColumn();
    }

    public function findAllByUserId(int $userId, int $limit = 100): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, type, title, body, link_path, is_read, created_at
             FROM notifications
             WHERE recipient_user_id = :recipient_user_id
             ORDER BY created_at DESC, id DESC
             LIMIT :limit'
        );
        $statement->bindValue(':recipient_user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function markAllAsReadByUserId(int $userId): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE notifications
             SET is_read = 1
             WHERE recipient_user_id = :recipient_user_id
               AND is_read = 0'
        );
        $statement->execute(['recipient_user_id' => $userId]);
    }
}
