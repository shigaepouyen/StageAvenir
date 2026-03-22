<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\InternshipRepository;
use App\Repositories\InternshipRevivalRepository;
use PDO;

final class RevivalController
{
    private InternshipRepository $internships;
    private InternshipRevivalRepository $revivals;

    public function __construct(PDO $pdo)
    {
        $this->internships = new InternshipRepository($pdo);
        $this->revivals = new InternshipRevivalRepository($pdo);
    }

    public function confirm(): void
    {
        $title = 'Reactivation de l\'offre';
        $message = null;
        $success = false;

        $selector = trim((string) ($_GET['selector'] ?? ''));
        $validator = trim((string) ($_GET['token'] ?? ''));

        if ($selector === '' || $validator === '') {
            http_response_code(400);
            $message = 'Lien de reactivation invalide.';
            require __DIR__ . '/../Views/revival_result.php';
            return;
        }

        $revival = $this->revivals->findPendingBySelector($selector);

        if ($revival === null) {
            http_response_code(404);
            $message = 'Lien de reactivation introuvable.';
            require __DIR__ . '/../Views/revival_result.php';
            return;
        }

        if ($revival['confirmed_at'] !== null) {
            $success = true;
            $message = 'Cette offre a deja ete reactivee.';
            require __DIR__ . '/../Views/revival_result.php';
            return;
        }

        if ($revival['archived_at'] !== null) {
            http_response_code(410);
            $message = 'Cette campagne de reactivation est terminee.';
            require __DIR__ . '/../Views/revival_result.php';
            return;
        }

        $isValid = hash_equals((string) $revival['hashed_validator'], hash('sha256', $validator));

        if (!$isValid) {
            http_response_code(400);
            $message = 'Lien de reactivation invalide.';
            require __DIR__ . '/../Views/revival_result.php';
            return;
        }

        $this->internships->updateStatusAndAcademicYear(
            (int) $revival['internship_id'],
            'active',
            (string) $revival['target_academic_year']
        );
        $this->revivals->markConfirmed((int) $revival['id']);

        $success = true;
        $message = 'L\'offre a ete reactivee pour l\'annee scolaire ' . $revival['target_academic_year'] . '.';
        require __DIR__ . '/../Views/revival_result.php';
    }
}
