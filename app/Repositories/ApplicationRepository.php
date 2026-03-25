<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ApplicationRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(int $internshipId, int $studentId, string $message, string $classe): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO applications (internship_id, student_id, student_pseudonym, status, message, classe, anonymized_at, created_at)
             VALUES (:internship_id, :student_id, :student_pseudonym, :status, :message, :classe, NULL, NOW())'
        );
        $statement->execute([
            'internship_id' => $internshipId,
            'student_id' => $studentId,
            'student_pseudonym' => $this->buildStudentPseudonym($studentId),
            'status' => 'new',
            'message' => $message,
            'classe' => $classe,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findByInternshipIdAndStudentId(int $internshipId, int $studentId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, internship_id, student_id, created_at
             FROM applications
             WHERE internship_id = :internship_id
               AND student_id = :student_id
             LIMIT 1'
        );
        $statement->execute([
            'internship_id' => $internshipId,
            'student_id' => $studentId,
        ]);
        $application = $statement->fetch();

        return $application === false ? null : $application;
    }

    public function deleteById(int $id): void
    {
        $statement = $this->pdo->prepare(
            'DELETE FROM applications
             WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
    }

    public function findAllByStudentId(int $studentId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                applications.id,
                applications.status AS application_status,
                applications.message,
                applications.classe,
                applications.created_at,
                internships.id AS internship_id,
                internships.title AS internship_title,
                internships.status AS internship_status,
                internships.academic_year,
                companies.name AS company_name,
                companies.address AS company_address
             FROM applications
             INNER JOIN internships ON internships.id = applications.internship_id
             INNER JOIN companies ON companies.id = internships.company_id
             WHERE applications.student_id = :student_id
             ORDER BY applications.created_at DESC'
        );
        $statement->execute(['student_id' => $studentId]);

        return $statement->fetchAll();
    }

    public function findAllByCompanyId(int $companyId, ?int $internshipId = null, ?string $status = null): array
    {
        $conditions = ['internships.company_id = :company_id'];
        $params = ['company_id' => $companyId];

        if ($internshipId !== null) {
            $conditions[] = 'applications.internship_id = :internship_id';
            $params['internship_id'] = $internshipId;
        }

        if ($status !== null && $status !== '') {
            $conditions[] = 'applications.status = :status';
            $params['status'] = $status;
        }

        $statement = $this->pdo->prepare(
            'SELECT
                applications.id,
                applications.internship_id,
                applications.status,
                applications.message,
                applications.classe,
                COALESCE(applications.student_pseudonym, CONCAT(\'eleve-\', LPAD(applications.student_id, 6, \'0\'))) AS student_pseudonym,
                applications.anonymized_at,
                applications.created_at,
                internships.title AS internship_title,
                internships.academic_year,
                companies.name AS company_name
             FROM applications
             INNER JOIN internships ON internships.id = applications.internship_id
             INNER JOIN companies ON companies.id = internships.company_id
             LEFT JOIN users ON users.id = applications.student_id
             WHERE ' . implode(' AND ', $conditions) . '
             ORDER BY
                CASE applications.status
                    WHEN \'new\' THEN 1
                    WHEN \'contacted\' THEN 2
                    WHEN \'accepted\' THEN 3
                    WHEN \'rejected\' THEN 4
                    ELSE 5
                END,
                applications.created_at DESC'
        );
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function findByIdAndCompanyId(int $applicationId, int $companyId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                applications.id,
                applications.internship_id,
                applications.student_id,
                applications.status
             FROM applications
             INNER JOIN internships ON internships.id = applications.internship_id
             WHERE applications.id = :application_id
               AND internships.company_id = :company_id
             LIMIT 1'
        );
        $statement->execute([
            'application_id' => $applicationId,
            'company_id' => $companyId,
        ]);
        $application = $statement->fetch();

        return $application === false ? null : $application;
    }

    public function updateStatusById(int $applicationId, string $status): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE applications
             SET status = :status
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $applicationId,
            'status' => $status,
        ]);
    }

    public function countAcceptedByInternshipId(int $internshipId): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) AS total
             FROM applications
             WHERE internship_id = :internship_id
               AND status = :status'
        );
        $statement->execute([
            'internship_id' => $internshipId,
            'status' => 'accepted',
        ]);

        return (int) $statement->fetchColumn();
    }

    public function countNewByCompanyId(int $companyId): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) AS total
             FROM applications
             INNER JOIN internships ON internships.id = applications.internship_id
             WHERE internships.company_id = :company_id
               AND applications.status = :status'
        );
        $statement->execute([
            'company_id' => $companyId,
            'status' => 'new',
        ]);

        return (int) $statement->fetchColumn();
    }

    public function findDistinctClasses(): array
    {
        $statement = $this->pdo->prepare(
            'SELECT DISTINCT classe
             FROM applications
             WHERE anonymized_at IS NULL
               AND classe <> \'\'
             ORDER BY classe ASC'
        );
        $statement->execute();

        return array_values(array_filter(
            array_map(static fn (mixed $value): string => trim((string) $value), $statement->fetchAll(PDO::FETCH_COLUMN)),
            static fn (string $value): bool => $value !== ''
        ));
    }

    public function findDistinctCompaniesForAdmin(): array
    {
        $statement = $this->pdo->prepare(
            'SELECT DISTINCT
                companies.id,
                COALESCE(NULLIF(companies.name, \'\'), companies.siret) AS company_label
             FROM applications
             INNER JOIN internships ON internships.id = applications.internship_id
             INNER JOIN companies ON companies.id = internships.company_id
             ORDER BY company_label ASC'
        );
        $statement->execute();

        return $statement->fetchAll();
    }

    public function findDistinctInternshipsForAdmin(?int $companyId = null): array
    {
        $conditions = [];
        $params = [];

        if ($companyId !== null) {
            $conditions[] = 'companies.id = :company_id';
            $params['company_id'] = $companyId;
        }

        $statement = $this->pdo->prepare(
            'SELECT DISTINCT
                internships.id,
                internships.title,
                COALESCE(NULLIF(companies.name, \'\'), companies.siret) AS company_label
             FROM applications
             INNER JOIN internships ON internships.id = applications.internship_id
             INNER JOIN companies ON companies.id = internships.company_id
             ' . ($conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions)) . '
             ORDER BY internships.title ASC'
        );
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function findThreadContextForStudent(int $applicationId, int $studentId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                applications.id,
                applications.internship_id,
                applications.student_id,
                COALESCE(applications.student_pseudonym, CONCAT(\'eleve-\', LPAD(applications.student_id, 6, \'0\'))) AS student_pseudonym,
                applications.status,
                applications.message,
                applications.classe,
                applications.anonymized_at,
                applications.created_at,
                internships.title AS internship_title,
                internships.status AS internship_status,
                internships.academic_year,
                companies.id AS company_id,
                COALESCE(NULLIF(companies.name, \'\'), companies.siret) AS company_label,
                owner.id AS company_owner_user_id,
                owner.email AS company_owner_email,
                students.email AS student_email
             FROM applications
             INNER JOIN internships ON internships.id = applications.internship_id
             INNER JOIN companies ON companies.id = internships.company_id
             INNER JOIN users AS owner ON owner.id = companies.user_id
             LEFT JOIN users AS students ON students.id = applications.student_id
             WHERE applications.id = :application_id
               AND applications.student_id = :student_id
             LIMIT 1'
        );
        $statement->execute([
            'application_id' => $applicationId,
            'student_id' => $studentId,
        ]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function findThreadContextForCompany(int $applicationId, int $companyId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                applications.id,
                applications.internship_id,
                applications.student_id,
                COALESCE(applications.student_pseudonym, CONCAT(\'eleve-\', LPAD(applications.student_id, 6, \'0\'))) AS student_pseudonym,
                applications.status,
                applications.message,
                applications.classe,
                applications.anonymized_at,
                applications.created_at,
                internships.title AS internship_title,
                internships.status AS internship_status,
                internships.academic_year,
                companies.id AS company_id,
                COALESCE(NULLIF(companies.name, \'\'), companies.siret) AS company_label,
                owner.id AS company_owner_user_id,
                owner.email AS company_owner_email,
                students.email AS student_email
             FROM applications
             INNER JOIN internships ON internships.id = applications.internship_id
             INNER JOIN companies ON companies.id = internships.company_id
             INNER JOIN users AS owner ON owner.id = companies.user_id
             LEFT JOIN users AS students ON students.id = applications.student_id
             WHERE applications.id = :application_id
               AND companies.id = :company_id
             LIMIT 1'
        );
        $statement->execute([
            'application_id' => $applicationId,
            'company_id' => $companyId,
        ]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function findThreadContextForStaff(int $applicationId, ?string $schoolClass = null): ?array
    {
        $conditions = ['applications.id = :application_id'];
        $params = ['application_id' => $applicationId];

        if ($schoolClass !== null && $schoolClass !== '') {
            $conditions[] = 'COALESCE(users.school_class, applications.classe) = :school_class';
            $params['school_class'] = $schoolClass;
        }

        $statement = $this->pdo->prepare(
            'SELECT
                applications.id,
                applications.internship_id,
                applications.student_id,
                COALESCE(applications.student_pseudonym, CONCAT(\'eleve-\', LPAD(applications.student_id, 6, \'0\'))) AS student_pseudonym,
                applications.status,
                applications.message,
                applications.classe,
                applications.anonymized_at,
                applications.created_at,
                internships.title AS internship_title,
                internships.status AS internship_status,
                internships.academic_year,
                companies.id AS company_id,
                COALESCE(NULLIF(companies.name, \'\'), companies.siret) AS company_label,
                owner.id AS company_owner_user_id,
                owner.email AS company_owner_email,
                users.first_name AS student_first_name,
                users.last_name AS student_last_name,
                COALESCE(users.school_class, applications.classe) AS student_school_class
             FROM applications
             INNER JOIN internships ON internships.id = applications.internship_id
             INNER JOIN companies ON companies.id = internships.company_id
             INNER JOIN users AS owner ON owner.id = companies.user_id
             LEFT JOIN users ON users.id = applications.student_id
             WHERE ' . implode(' AND ', $conditions) . '
             LIMIT 1'
        );
        $statement->execute($params);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function findAllForAdmin(
        ?string $class = null,
        ?string $status = null,
        ?int $companyId = null,
        ?int $internshipId = null,
        ?string $studentSearch = null
    ): array {
        $conditions = ['1 = 1'];
        $params = [];

        if ($class !== null && $class !== '') {
            $conditions[] = 'COALESCE(users.school_class, applications.classe) = :classe';
            $params['classe'] = $class;
        }

        if ($status !== null && $status !== '') {
            $conditions[] = 'applications.status = :status';
            $params['status'] = $status;
        }

        if ($companyId !== null) {
            $conditions[] = 'companies.id = :company_id';
            $params['company_id'] = $companyId;
        }

        if ($internshipId !== null) {
            $conditions[] = 'internships.id = :internship_id';
            $params['internship_id'] = $internshipId;
        }

        $normalizedStudentSearch = $this->normalizeSearchTerm($studentSearch);

        if ($normalizedStudentSearch !== null) {
            $conditions[] = 'CONCAT_WS(\' \',
                COALESCE(users.first_name, \'\'),
                COALESCE(users.last_name, \'\'),
                COALESCE(users.last_name, \'\'),
                COALESCE(users.first_name, \'\'),
                COALESCE(applications.student_pseudonym, \'\')
            ) LIKE :student_search';
            $params['student_search'] = $normalizedStudentSearch;
        }

        $statement = $this->pdo->prepare(
            'SELECT
                applications.id,
                applications.status,
                applications.message,
                applications.classe,
                applications.student_pseudonym,
                applications.anonymized_at,
                applications.created_at,
                internships.id AS internship_id,
                internships.title AS internship_title,
                internships.status AS internship_status,
                internships.places_count,
                internships.academic_year,
                companies.id AS company_id,
                COALESCE(NULLIF(companies.name, \'\'), companies.siret) AS company_label,
                users.first_name AS student_first_name,
                users.last_name AS student_last_name,
                COALESCE(users.school_class, applications.classe) AS student_school_class
             FROM applications
             INNER JOIN internships ON internships.id = applications.internship_id
             INNER JOIN companies ON companies.id = internships.company_id
             LEFT JOIN users ON users.id = applications.student_id
             WHERE ' . implode(' AND ', $conditions) . '
             ORDER BY
                CASE applications.status
                    WHEN \'new\' THEN 1
                    WHEN \'contacted\' THEN 2
                    WHEN \'accepted\' THEN 3
                    WHEN \'rejected\' THEN 4
                    ELSE 5
                END,
                applications.created_at DESC'
        );
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function anonymizeAllStudentData(): int
    {
        /*
        |-------------------------------------------------------------------
        | RGPD - minimisation et limitation de conservation
        |-------------------------------------------------------------------
        | A partir du nettoyage annuel, on casse le lien direct avec l'eleve :
        | - `student_id` est mis a NULL
        | - `student_pseudonym` conserve une trace technique non nominative
        | - `message` est neutralise car il peut contenir des noms/prenoms
        | - `classe` est remplacee par une valeur anonymisee
        | - `anonymized_at` permet de prouver la date du traitement
        |
        | Cette approche reduit fortement les donnees personnelles conservees
        | dans les journaux de candidature. Elle ne pretend pas couvrir a elle
        | seule l'ensemble des obligations RGPD de l'application.
        */
        $statement = $this->pdo->prepare(
            'UPDATE applications
             SET student_pseudonym = COALESCE(student_pseudonym, CONCAT(\'eleve-\', LPAD(id, 6, \'0\'))),
                 student_id = NULL,
                 message = :message,
                 classe = :classe,
                 anonymized_at = NOW()
             WHERE anonymized_at IS NULL'
        );
        $statement->execute([
            'message' => '[message anonymise automatiquement apres la campagne]',
            'classe' => '[classe anonymisee]',
        ]);

        return $statement->rowCount();
    }

    private function buildStudentPseudonym(int $studentId): string
    {
        return sprintf('eleve-%06d', $studentId);
    }

    private function normalizeSearchTerm(?string $searchTerm): ?string
    {
        $value = trim((string) $searchTerm);

        return $value === '' ? null : '%' . $value . '%';
    }
}
