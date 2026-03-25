<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ApplicationRepository;
use App\Repositories\CompanyRepository;
use App\Repositories\NotificationRepository;
use App\Support\SessionManager;
use PDO;

final class HomeController
{
    private CompanyRepository $companies;
    private ApplicationRepository $applications;
    private NotificationRepository $notifications;

    public function __construct(PDO $pdo)
    {
        $this->companies = new CompanyRepository($pdo);
        $this->applications = new ApplicationRepository($pdo);
        $this->notifications = new NotificationRepository($pdo);
    }

    public function index(): void
    {
        $title = 'Avenir Pro';
        $user = SessionManager::currentUser();
        $displayName = trim((string) (($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')));
        $message = match ($user['role'] ?? null) {
            null => 'Trouve un stage de 3e sans te perdre dans des formulaires compliques.',
            'student' => 'Cherche un secteur, regarde les offres autour de toi et candidate quand tu es pret.',
            'teacher' => 'Suivez vos eleves, reperez ceux qui n ont pas encore candidate et exportez le suivi en CSV.',
            'level_manager' => 'Suivez l ensemble du niveau, reperez les eleves sans candidature et pilotez la campagne.',
            'admin' => 'Pilotez la campagne de stage, les candidatures et les offres de l etablissement.',
            default => $displayName !== '' ? 'Bienvenue ' . $displayName . '.' : 'Bienvenue dans votre espace.',
        };
        $canManageCompanyProfile = $user !== null && in_array($user['role'], ['parent', 'company', 'admin'], true);
        $canManageInternships = $canManageCompanyProfile;
        $canAccessCollegeDashboard = $user !== null && in_array(($user['role'] ?? ''), ['admin', 'teacher', 'level_manager'], true);
        $canAccessAdminInternships = $user !== null && ($user['role'] ?? '') === 'admin';
        $newApplicationsCount = 0;
        $unreadNotificationsCount = 0;

        if ($canManageInternships) {
            $company = $this->companies->findByUserId((int) $user['id']);

            if ($company !== null) {
                $newApplicationsCount = $this->applications->countNewByCompanyId((int) $company['id']);
            }
        }

        if ($user !== null) {
            $unreadNotificationsCount = $this->notifications->countUnreadByUserId((int) $user['id']);
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
