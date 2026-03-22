<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class InternshipRevivalRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByInternshipIdAndTargetYear(int $internshipId, string $targetAcademicYear): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                id,
                internship_id,
                target_academic_year,
                selector,
                hashed_validator,
                emails_sent,
                last_sent_at,
                confirmed_at,
                archived_at,
                created_at
             FROM internship_revival_requests
             WHERE internship_id = :internship_id
               AND target_academic_year = :target_academic_year
             LIMIT 1'
        );
        $statement->execute([
            'internship_id' => $internshipId,
            'target_academic_year' => $targetAcademicYear,
        ]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function findPendingBySelector(string $selector): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                internship_revival_requests.id,
                internship_revival_requests.internship_id,
                internship_revival_requests.target_academic_year,
                internship_revival_requests.selector,
                internship_revival_requests.hashed_validator,
                internship_revival_requests.emails_sent,
                internship_revival_requests.last_sent_at,
                internship_revival_requests.confirmed_at,
                internship_revival_requests.archived_at,
                internships.title
             FROM internship_revival_requests
             INNER JOIN internships ON internships.id = internship_revival_requests.internship_id
             WHERE internship_revival_requests.selector = :selector
             LIMIT 1'
        );
        $statement->execute(['selector' => $selector]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function create(int $internshipId, string $targetAcademicYear, string $selector, string $hashedValidator): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO internship_revival_requests (
                internship_id,
                target_academic_year,
                selector,
                hashed_validator,
                emails_sent,
                last_sent_at,
                confirmed_at,
                archived_at,
                created_at
             ) VALUES (
                :internship_id,
                :target_academic_year,
                :selector,
                :hashed_validator,
                0,
                NULL,
                NULL,
                NULL,
                NOW()
             )'
        );
        $statement->execute([
            'internship_id' => $internshipId,
            'target_academic_year' => $targetAcademicYear,
            'selector' => $selector,
            'hashed_validator' => $hashedValidator,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function deleteById(int $id): void
    {
        $statement = $this->pdo->prepare(
            'DELETE FROM internship_revival_requests
             WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
    }

    public function markEmailSent(int $id): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE internship_revival_requests
             SET emails_sent = emails_sent + 1,
                 last_sent_at = NOW()
             WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
    }

    public function markConfirmed(int $id): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE internship_revival_requests
             SET confirmed_at = NOW()
             WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
    }

    public function markArchived(int $id): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE internship_revival_requests
             SET archived_at = NOW()
             WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
    }
}
