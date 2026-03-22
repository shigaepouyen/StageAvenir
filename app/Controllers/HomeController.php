<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ApplicationRepository;
use App\Repositories\CompanyRepository;
use App\Support\SessionManager;
use PDO;

final class HomeController
{
    private CompanyRepository $companies;
    private ApplicationRepository $applications;

    public function __construct(PDO $pdo)
    {
        $this->companies = new CompanyRepository($pdo);
        $this->applications = new ApplicationRepository($pdo);
    }

    public function index(): void
    {
        $title = 'Avenir Pro';
        $user = SessionManager::currentUser();
        $message = match ($user['role'] ?? null) {
            null => 'Trouve un stage de 3e sans te perdre dans des formulaires compliques.',
            'student' => 'Cherche un secteur, regarde les offres autour de toi et candidate quand tu es pret.',
            default => 'Bienvenue ' . $user['email'] . '.',
        };
        $canManageCompanyProfile = $user !== null && in_array($user['role'], ['parent', 'company', 'admin'], true);
        $canManageInternships = $canManageCompanyProfile;
        $canAccessAdminInternships = $user !== null && ($user['role'] ?? '') === 'admin';
        $newApplicationsCount = 0;

        if ($canManageInternships) {
            $company = $this->companies->findByUserId((int) $user['id']);

            if ($company !== null) {
                $newApplicationsCount = $this->applications->countNewByCompanyId((int) $company['id']);
            }
        }

        require __DIR__ . '/../Views/home.php';
    }

    public function studentHelp(): void
    {
        $title = 'Aide eleve';
        $user = SessionManager::currentUser();

        require __DIR__ . '/../Views/student_help.php';
    }
}
