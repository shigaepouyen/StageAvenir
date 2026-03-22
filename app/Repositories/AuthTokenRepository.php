<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AuthTokenRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(string $selector, string $hashedValidator, int $userId, string $expiresAt): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO auth_tokens (selector, hashed_validator, user_id, expires_at)
             VALUES (:selector, :hashed_validator, :user_id, :expires_at)'
        );
        $statement->execute([
            'selector' => $selector,
            'hashed_validator' => $hashedValidator,
            'user_id' => $userId,
            'expires_at' => $expiresAt,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findActiveBySelector(string $selector): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                auth_tokens.id,
                auth_tokens.selector,
                auth_tokens.hashed_validator,
                auth_tokens.user_id,
                auth_tokens.expires_at,
                users.email,
                users.role
             FROM auth_tokens
             INNER JOIN users ON users.id = auth_tokens.user_id
             WHERE auth_tokens.selector = :selector
             LIMIT 1'
        );
        $statement->execute(['selector' => $selector]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function deleteById(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM auth_tokens WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function deleteByUserId(int $userId): void
    {
        $statement = $this->pdo->prepare('DELETE FROM auth_tokens WHERE user_id = :user_id');
        $statement->execute(['user_id' => $userId]);
    }

    public function deleteExpired(): void
    {
        $statement = $this->pdo->prepare('DELETE FROM auth_tokens WHERE expires_at < NOW()');
        $statement->execute();
    }
}
