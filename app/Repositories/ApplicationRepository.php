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
            'INSERT INTO applications (internship_id, student_id, student_pseudonym, message, classe, anonymized_at, created_at)
             VALUES (:internship_id, :student_id, NULL, :message, :classe, NULL, NOW())'
        );
        $statement->execute([
            'internship_id' => $internshipId,
            'student_id' => $studentId,
            'message' => $message,
            'classe' => $classe,
        ]);

        return (int) $this->pdo->lastInsertId();
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
}
