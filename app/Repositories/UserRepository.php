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
        $statement = $this->pdo->prepare(
            'SELECT id, email, role, first_name, last_name, school_class, managed_class, created_at
             FROM users
             WHERE email = :email
             LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, email, role, first_name, last_name, school_class, managed_class, created_at
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    public function create(
        string $email,
        string $role,
        ?string $schoolClass = null,
        ?string $managedClass = null,
        ?string $firstName = null,
        ?string $lastName = null
    ): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO users (email, role, first_name, last_name, school_class, managed_class, created_at)
             VALUES (:email, :role, :first_name, :last_name, :school_class, :managed_class, NOW())'
        );
        $statement->execute([
            'email' => $email,
            'role' => $role,
            'first_name' => $this->normalizeNullableString($firstName),
            'last_name' => $this->normalizeNullableString($lastName),
            'school_class' => $schoolClass,
            'managed_class' => $managedClass,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateSchoolClassById(int $userId, string $schoolClass): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE users
             SET school_class = :school_class
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $userId,
            'school_class' => trim($schoolClass),
        ]);
    }

    public function countStudentsWithoutApplications(?string $schoolClass = null, ?string $searchTerm = null): int
    {
        $conditions = [
            'users.role = :role',
            'applications.id IS NULL',
        ];
        $params = ['role' => 'student'];

        if ($schoolClass !== null && $schoolClass !== '') {
            $conditions[] = 'users.school_class = :school_class';
            $params['school_class'] = $schoolClass;
        }

        $normalizedSearchTerm = $this->normalizeSearchTerm($searchTerm);

        if ($normalizedSearchTerm !== null) {
            $conditions[] = $this->buildStudentSearchCondition('users');
            $params['search_term'] = $normalizedSearchTerm;
        }

        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) AS total
             FROM users
             LEFT JOIN applications ON applications.student_id = users.id
             WHERE ' . implode(' AND ', $conditions)
        );
        $statement->execute($params);

        return (int) $statement->fetchColumn();
    }

    public function findStudentsWithoutApplications(int $limit = 50, ?string $schoolClass = null, ?string $searchTerm = null): array
    {
        $conditions = [
            'users.role = :role',
            'applications.id IS NULL',
        ];
        $params = ['role' => 'student'];

        if ($schoolClass !== null && $schoolClass !== '') {
            $conditions[] = 'users.school_class = :school_class';
            $params['school_class'] = $schoolClass;
        }

        $normalizedSearchTerm = $this->normalizeSearchTerm($searchTerm);

        if ($normalizedSearchTerm !== null) {
            $conditions[] = $this->buildStudentSearchCondition('users');
            $params['search_term'] = $normalizedSearchTerm;
        }

        $statement = $this->pdo->prepare(
            'SELECT users.id, users.first_name, users.last_name, users.school_class, users.created_at
             FROM users
             LEFT JOIN applications ON applications.student_id = users.id
             WHERE ' . implode(' AND ', $conditions) . '
             ORDER BY users.school_class ASC, users.last_name ASC, users.first_name ASC, users.created_at DESC
             LIMIT :limit'
        );
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value, PDO::PARAM_STR);
        }
        $statement->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function findStudentsDirectory(int $limit = 200, ?string $schoolClass = null, ?string $searchTerm = null): array
    {
        $conditions = ['users.role = :role'];
        $params = ['role' => 'student'];

        if ($schoolClass !== null && $schoolClass !== '') {
            $conditions[] = 'users.school_class = :school_class';
            $params['school_class'] = $schoolClass;
        }

        $normalizedSearchTerm = $this->normalizeSearchTerm($searchTerm);

        if ($normalizedSearchTerm !== null) {
            $conditions[] = $this->buildStudentSearchCondition('users');
            $params['search_term'] = $normalizedSearchTerm;
        }

        $statement = $this->pdo->prepare(
            'SELECT
                users.id,
                users.first_name,
                users.last_name,
                users.school_class,
                users.created_at,
                COUNT(applications.id) AS applications_count,
                MAX(applications.created_at) AS last_application_at
             FROM users
             LEFT JOIN applications ON applications.student_id = users.id
             WHERE ' . implode(' AND ', $conditions) . '
             GROUP BY users.id, users.first_name, users.last_name, users.school_class, users.created_at
             ORDER BY users.school_class ASC, users.last_name ASC, users.first_name ASC, users.created_at DESC
             LIMIT :limit'
        );
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value, PDO::PARAM_STR);
        }
        $statement->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function findDistinctStudentClasses(): array
    {
        $statement = $this->pdo->prepare(
            'SELECT DISTINCT school_class
             FROM users
             WHERE role = :role
               AND school_class IS NOT NULL
               AND school_class <> \'\'
             ORDER BY school_class ASC'
        );
        $statement->execute(['role' => 'student']);

        return array_values(array_filter(
            array_map(static fn (mixed $value): string => trim((string) $value), $statement->fetchAll(PDO::FETCH_COLUMN)),
            static fn (string $value): bool => $value !== ''
        ));
    }

    private function normalizeSearchTerm(?string $searchTerm): ?string
    {
        $value = trim((string) $searchTerm);

        return $value === '' ? null : '%' . $value . '%';
    }

    private function buildStudentSearchCondition(string $tableAlias): string
    {
        return 'CONCAT_WS(\' \',
                COALESCE(' . $tableAlias . '.first_name, \'\'),
                COALESCE(' . $tableAlias . '.last_name, \'\'),
                COALESCE(' . $tableAlias . '.last_name, \'\'),
                COALESCE(' . $tableAlias . '.first_name, \'\')
            ) LIKE :search_term';
    }

    private function normalizeNullableString(?string $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
