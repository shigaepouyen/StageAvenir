<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\SessionManager;

final class HomeController
{
    public function index(): void
    {
        $title = 'Avenir Pro';
        $user = SessionManager::currentUser();
        $message = $user === null
            ? 'Connectez-vous avec un Magic Link.'
            : 'Bienvenue ' . $user['email'] . ' (' . $user['role'] . ').';
        $canManageCompanyProfile = $user !== null && in_array($user['role'], ['parent', 'company', 'admin'], true);
        $canManageInternships = $canManageCompanyProfile;
        $canAccessAdminInternships = $user !== null && ($user['role'] ?? '') === 'admin';

        require __DIR__ . '/../Views/home.php';
    }
}
