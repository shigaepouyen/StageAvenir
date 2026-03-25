<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ApplicationMessageRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(
        int $applicationId,
        ?int $senderUserId,
        string $senderRole,
        ?string $senderLabel,
        string $body
    ): int {
        $statement = $this->pdo->prepare(
            'INSERT INTO application_messages (application_id, sender_user_id, sender_role, sender_label, body, created_at)
             VALUES (:application_id, :sender_user_id, :sender_role, :sender_label, :body, NOW())'
        );
        $statement->execute([
            'application_id' => $applicationId,
            'sender_user_id' => $senderUserId,
            'sender_role' => $senderRole,
            'sender_label' => $senderLabel,
            'body' => $body,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findAllByApplicationId(int $applicationId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                id,
                application_id,
                sender_user_id,
                sender_role,
                sender_label,
                body,
                created_at
             FROM application_messages
             WHERE application_id = :application_id
             ORDER BY created_at ASC, id ASC'
        );
        $statement->execute(['application_id' => $applicationId]);

        return $statement->fetchAll();
    }
}
