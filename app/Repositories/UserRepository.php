<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, email, role, created_at FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, email, role, created_at FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    public function create(string $email, string $role): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO users (email, role, created_at) VALUES (:email, :role, NOW())'
        );
        $statement->execute([
            'email' => $email,
            'role' => $role,
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
