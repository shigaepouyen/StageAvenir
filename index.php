<?php

declare(strict_types=1);

set_exception_handler(static function (\Throwable $exception): void {
    error_log(sprintf(
        '[AvenirPro] %s in %s:%d | trace=%s',
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        str_replace(["\r", "\n"], ' ', $exception->getTraceAsString())
    ));

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
    }

    echo "Erreur interne Avenir Pro. Consultez les logs OVH.";
    exit;
});

use App\Controllers\AuthController;
use App\Controllers\CompanyController;
use App\Controllers\HomeController;
use App\Controllers\InternshipController;
use App\Controllers\NotificationController;
use App\Controllers\RevivalController;
use App\Support\Csrf;
use App\Support\SessionManager;

$autoloadPath = __DIR__ . '/vendor/autoload.php';

if (!is_file($autoloadPath)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Dependances manquantes. Lancez 'composer install'.";
    exit;
}

require $autoloadPath;
require __DIR__ . '/app/Support/internship_helpers.php';
require __DIR__ . '/app/Support/geo_helpers.php';
require __DIR__ . '/app/Support/url_helpers.php';

$pdo = require __DIR__ . '/config/database.php';
$authConfig = require __DIR__ . '/config/auth.php';
$sireneConfig = require __DIR__ . '/config/sirene.php';

SessionManager::start($authConfig['session']);

$basePath = parse_url($authConfig['app_url'], PHP_URL_PATH);
$basePath = is_string($basePath) ? trim($basePath, '/') : '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !Csrf::verify($_POST['csrf_token'] ?? null)) {
    http_response_code(419);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Verification de securite</title></head><body><main><h1>Verification de securite</h1><p>Le formulaire a expire ou est invalide. Rechargez la page puis recommencez.</p><p><a href="' . htmlspecialchars(app_path('/'), ENT_QUOTES, 'UTF-8') . '">Retour a l&apos;accueil</a></p></main></body></html>';
    exit;
}

Flight::set('db', $pdo);
Flight::set('auth.config', $authConfig);
Flight::set('sirene.config', $sireneConfig);
Flight::set('app.url', $authConfig['app_url']);
Flight::set('app.base_path', $basePath);

$homeController = new HomeController($pdo);
$authController = new AuthController($pdo, $authConfig);
$companyController = new CompanyController($pdo, $sireneConfig);
$internshipController = new InternshipController($pdo, $authConfig['mail']);
$notificationController = new NotificationController($pdo);
$revivalController = new RevivalController($pdo);

Flight::route('GET /', [$homeController, 'index']);
Flight::route('GET /help', [$homeController, 'studentHelp']);
Flight::route('GET /login', [$authController, 'showLoginForm']);
Flight::route('POST /login', [$authController, 'sendMagicLink']);
Flight::route('POST /logout', [$authController, 'logout']);
Flight::route('GET /magic-link', [$authController, 'consumeMagicLink']);
Flight::route('GET /news', [$notificationController, 'index']);
Flight::route('POST /news/mark-all-read', [$notificationController, 'markAllRead']);
Flight::route('GET /company-profile', [$companyController, 'showForm']);
Flight::route('POST /company-profile', [$companyController, 'save']);
Flight::route('POST /company-profile/search', [$companyController, 'search']);
Flight::route('POST /company-profile/select', [$companyController, 'selectSearchResult']);
Flight::route('GET /internships', [$internshipController, 'index']);
Flight::route('GET /internships/create', [$internshipController, 'showCreateForm']);
Flight::route('POST /internships/create', [$internshipController, 'create']);
Flight::route('POST /internships/@id/sleep', [$internshipController, 'setSleeping']);
Flight::route('GET /company-applications', [$internshipController, 'companyApplications']);
Flight::route('POST /company-applications/@id/status', [$internshipController, 'updateApplicationStatus']);
Flight::route('GET /offers', [$internshipController, 'studentList']);
Flight::route('GET /offers/@id', [$internshipController, 'showPublicDetail']);
Flight::route('POST /offers/@id/apply', [$internshipController, 'apply']);
Flight::route('GET /applications/@id', [$internshipController, 'showApplicationThread']);
Flight::route('POST /applications/@id/messages', [$internshipController, 'postApplicationMessage']);
Flight::route('GET /my-applications', [$internshipController, 'studentApplications']);
Flight::route('GET /search', [$internshipController, 'searchPage']);
Flight::route('GET /internships/revival/confirm', [$revivalController, 'confirm']);
Flight::route('GET /admin/dashboard', [$internshipController, 'adminDashboard']);
Flight::route('GET /admin/dashboard/export', [$internshipController, 'exportAdminDashboardCsv']);
Flight::route('GET /admin/internships', [$internshipController, 'adminIndex']);
Flight::route('POST /admin/companies/@id/approve', [$internshipController, 'approveCompany']);
Flight::route('POST /admin/companies/@id/reject', [$internshipController, 'rejectCompany']);
Flight::route('POST /admin/internships/@id/approve', [$internshipController, 'approveInternship']);
Flight::route('POST /admin/internships/@id/reject', [$internshipController, 'rejectInternship']);
Flight::route('POST /admin/internships/@id/archive', [$internshipController, 'archive']);

Flight::start();
