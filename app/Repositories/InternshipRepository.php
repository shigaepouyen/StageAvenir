<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class InternshipRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                internships.id,
                internships.company_id,
                internships.title,
                internships.description,
                internships.sector_tag,
                internships.places_count,
                internships.status,
                internships.academic_year,
                companies.name AS company_name,
                companies.address AS company_address,
                companies.lat AS company_lat,
                companies.lng AS company_lng,
                users.email AS owner_email
             FROM internships
             INNER JOIN companies ON companies.id = internships.company_id
             INNER JOIN users ON users.id = companies.user_id
             WHERE internships.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $internship = $statement->fetch();

        return $internship === false ? null : $internship;
    }

    public function findByIdAndCompanyId(int $id, int $companyId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, company_id, title, description, sector_tag, places_count, status, academic_year
             FROM internships
             WHERE id = :id AND company_id = :company_id
             LIMIT 1'
        );
        $statement->execute([
            'id' => $id,
            'company_id' => $companyId,
        ]);
        $internship = $statement->fetch();

        return $internship === false ? null : $internship;
    }

    public function findAllByCompanyId(int $companyId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, company_id, title, description, sector_tag, places_count, status, academic_year
             FROM internships
             WHERE company_id = :company_id
             ORDER BY id DESC'
        );
        $statement->execute(['company_id' => $companyId]);

        return $statement->fetchAll();
    }

    public function create(int $companyId, array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO internships (company_id, title, description, sector_tag, places_count, status, academic_year)
             VALUES (:company_id, :title, :description, :sector_tag, :places_count, :status, :academic_year)'
        );
        $statement->execute([
            'company_id' => $companyId,
            'title' => $data['title'],
            'description' => $data['description'],
            'sector_tag' => $data['sector_tag'],
            'places_count' => $data['places_count'],
            'status' => $data['status'],
            'academic_year' => $data['academic_year'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findByStatusesWithCompany(array $statuses): array
    {
        if ($statuses === []) {
            return [];
        }

        $placeholders = [];
        $params = [];

        foreach (array_values($statuses) as $index => $status) {
            $placeholder = ':status_' . $index;
            $placeholders[] = $placeholder;
            $params['status_' . $index] = $status;
        }

        $sql = sprintf(
            'SELECT
                internships.id,
                internships.company_id,
                internships.title,
                internships.description,
                internships.sector_tag,
                internships.places_count,
                internships.status,
                internships.academic_year,
                companies.name AS company_name
             FROM internships
             INNER JOIN companies ON companies.id = internships.company_id
             WHERE internships.status IN (%s)
             ORDER BY internships.id DESC',
            implode(', ', $placeholders)
        );

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function findPreviousYearNonArchivedForRevival(string $previousAcademicYear): array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                internships.id,
                internships.company_id,
                internships.title,
                internships.status,
                internships.academic_year,
                users.email AS owner_email
             FROM internships
             INNER JOIN companies ON companies.id = internships.company_id
             INNER JOIN users ON users.id = companies.user_id
             WHERE internships.academic_year = :academic_year
               AND internships.status <> :archived_status
             ORDER BY internships.id DESC'
        );
        $statement->execute([
            'academic_year' => $previousAcademicYear,
            'archived_status' => 'archived',
        ]);

        return $statement->fetchAll();
    }

    public function searchActiveWithCompany(?string $keyword, ?string $sectorTag): array
    {
        $conditions = ['internships.status = :status'];
        $params = ['status' => 'active'];
        $joins = ['INNER JOIN companies ON companies.id = internships.company_id'];

        if ($keyword !== null && $keyword !== '') {
            $conditions[] = 'internships.title LIKE :keyword';
            $params['keyword'] = '%' . $keyword . '%';
        }

        if ($sectorTag !== null && $sectorTag !== '') {
            $joins[] = 'INNER JOIN tags_mapping ON tags_mapping.tag_name = :tag_name
                AND companies.naf_code LIKE CONCAT(tags_mapping.naf_prefix, \'%\')';
            $params['tag_name'] = $sectorTag;
        }

        $statement = $this->pdo->prepare(
            'SELECT
                internships.id,
                internships.company_id,
                internships.title,
                internships.description,
                internships.sector_tag,
                internships.places_count,
                internships.status,
                internships.academic_year,
                companies.name AS company_name,
                companies.address AS company_address,
                companies.lat AS company_lat,
                companies.lng AS company_lng
             FROM internships
             ' . implode(' ', $joins) . '
             WHERE ' . implode(' AND ', $conditions) . '
             GROUP BY
                internships.id,
                internships.company_id,
                internships.title,
                internships.description,
                internships.sector_tag,
                internships.places_count,
                internships.status,
                internships.academic_year,
                companies.name,
                companies.address,
                companies.lat,
                companies.lng
             ORDER BY internships.id DESC'
        );
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function updateStatusAndAcademicYear(int $id, string $status, string $academicYear): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE internships
             SET status = :status,
                 academic_year = :academic_year
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'status' => $status,
            'academic_year' => $academicYear,
        ]);
    }

    public function updateStatusById(int $id, string $status): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE internships
             SET status = :status
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'status' => $status,
        ]);
    }

    public function findOpenOffersOverview(?int $companyId = null, ?int $internshipId = null): array
    {
        $conditions = ['internships.status <> :archived_status'];
        $params = ['archived_status' => 'archived'];

        if ($companyId !== null) {
            $conditions[] = 'companies.id = :company_id';
            $params['company_id'] = $companyId;
        }

        if ($internshipId !== null) {
            $conditions[] = 'internships.id = :internship_id';
            $params['internship_id'] = $internshipId;
        }

        $statement = $this->pdo->prepare(
            'SELECT
                internships.id,
                internships.title,
                internships.status,
                internships.places_count,
                internships.academic_year,
                COALESCE(NULLIF(companies.name, \'\'), companies.siret) AS company_label,
                COUNT(applications.id) AS total_applications,
                SUM(CASE WHEN applications.status = \'new\' THEN 1 ELSE 0 END) AS new_applications,
                SUM(CASE WHEN applications.status = \'accepted\' THEN 1 ELSE 0 END) AS accepted_applications
             FROM internships
             INNER JOIN companies ON companies.id = internships.company_id
             LEFT JOIN applications ON applications.internship_id = internships.id
             WHERE ' . implode(' AND ', $conditions) . '
             GROUP BY
                internships.id,
                internships.title,
                internships.status,
                internships.places_count,
                internships.academic_year,
                companies.name,
                companies.siret
             ORDER BY internships.id DESC'
        );
        $statement->execute($params);

        return $statement->fetchAll();
    }
}
