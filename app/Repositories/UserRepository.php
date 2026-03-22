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

    public function countStudentsWithoutApplications(): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) AS total
             FROM users
             LEFT JOIN applications ON applications.student_id = users.id
             WHERE users.role = :role
               AND applications.id IS NULL'
        );
        $statement->execute(['role' => 'student']);

        return (int) $statement->fetchColumn();
    }

    public function findStudentsWithoutApplications(int $limit = 50): array
    {
        $statement = $this->pdo->prepare(
            'SELECT users.id, users.email, users.created_at
             FROM users
             LEFT JOIN applications ON applications.student_id = users.id
             WHERE users.role = :role
               AND applications.id IS NULL
             ORDER BY users.created_at DESC
             LIMIT :limit'
        );
        $statement->bindValue(':role', 'student', PDO::PARAM_STR);
        $statement->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }
}
