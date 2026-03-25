<?php

declare(strict_types=1);

if (!function_exists('set_internship_status')) {
    function set_internship_status(int $id, string $new_status): bool
    {
        $allowedStatuses = ['active', 'archived', 'sleeping'];

        if (!in_array($new_status, $allowedStatuses, true)) {
            return false;
        }

        /** @var \PDO $pdo */
        $pdo = \Flight::get('db');
        $statement = $pdo->prepare(
            'UPDATE internships
             SET status = :status
             WHERE id = :id'
        );

        return $statement->execute([
            'id' => $id,
            'status' => $new_status,
        ]);
    }
}

if (!function_exists('get_active_internships')) {
    function get_active_internships(): array
    {
        /** @var \PDO $pdo */
        $pdo = \Flight::get('db');
        $statement = $pdo->prepare(
            'SELECT
                internships.id,
                internships.company_id,
                internships.title,
                internships.description,
                internships.sector_tag,
                internships.places_count,
                internships.status,
                internships.academic_year,
                internships.validation_status,
                companies.name AS company_name,
                companies.address AS company_address,
                companies.lat AS company_lat,
                companies.lng AS company_lng
             FROM internships
             INNER JOIN companies ON companies.id = internships.company_id
             WHERE internships.status = :status
               AND internships.validation_status = :internship_validation_status
               AND companies.validation_status = :company_validation_status
             ORDER BY internships.id DESC'
        );
        $statement->execute([
            'status' => 'active',
            'internship_validation_status' => 'approved',
            'company_validation_status' => 'approved',
        ]);

        return $statement->fetchAll();
    }
}

if (!function_exists('get_archived_internships')) {
    function get_archived_internships(): array
    {
        /** @var \PDO $pdo */
        $pdo = \Flight::get('db');
        $statement = $pdo->prepare(
            'SELECT
                internships.id,
                internships.company_id,
                internships.title,
                internships.description,
                internships.sector_tag,
                internships.places_count,
                internships.status,
                internships.academic_year,
                internships.validation_status,
                companies.name AS company_name,
                companies.address AS company_address,
                companies.lat AS company_lat,
                companies.lng AS company_lng
             FROM internships
             INNER JOIN companies ON companies.id = internships.company_id
             WHERE internships.status = :status
             ORDER BY internships.id DESC'
        );
        $statement->execute(['status' => 'archived']);

        return $statement->fetchAll();
    }
}
