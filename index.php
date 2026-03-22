<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\CompanyController;
use App\Controllers\HomeController;
use App\Controllers\InternshipController;
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

$pdo = require __DIR__ . '/config/database.php';
$authConfig = require __DIR__ . '/config/auth.php';
$sireneConfig = require __DIR__ . '/config/sirene.php';

SessionManager::start($authConfig['session']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !Csrf::verify($_POST['csrf_token'] ?? null)) {
    http_response_code(419);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Verification de securite</title></head><body><main><h1>Verification de securite</h1><p>Le formulaire a expire ou est invalide. Rechargez la page puis recommencez.</p><p><a href="/">Retour a l&apos;accueil</a></p></main></body></html>';
    exit;
}

Flight::set('db', $pdo);
Flight::set('auth.config', $authConfig);
Flight::set('sirene.config', $sireneConfig);

$homeController = new HomeController();
$authController = new AuthController($pdo, $authConfig);
$companyController = new CompanyController($pdo, $sireneConfig);
$internshipController = new InternshipController($pdo, $authConfig['mail']);
$revivalController = new RevivalController($pdo);

Flight::route('GET /', [$homeController, 'index']);
Flight::route('GET /login', [$authController, 'showLoginForm']);
Flight::route('POST /login', [$authController, 'sendMagicLink']);
Flight::route('GET /magic-link', [$authController, 'consumeMagicLink']);
Flight::route('GET /company-profile', [$companyController, 'showForm']);
Flight::route('POST /company-profile', [$companyController, 'save']);
Flight::route('POST /company-profile/search', [$companyController, 'search']);
Flight::route('POST /company-profile/select', [$companyController, 'selectSearchResult']);
Flight::route('GET /internships', [$internshipController, 'index']);
Flight::route('GET /internships/create', [$internshipController, 'showCreateForm']);
Flight::route('POST /internships/create', [$internshipController, 'create']);
Flight::route('POST /internships/@id/sleep', [$internshipController, 'setSleeping']);
Flight::route('GET /offers', [$internshipController, 'studentList']);
Flight::route('GET /offers/@id', [$internshipController, 'showPublicDetail']);
Flight::route('POST /offers/@id/apply', [$internshipController, 'apply']);
Flight::route('GET /search', [$internshipController, 'searchPage']);
Flight::route('GET /internships/revival/confirm', [$revivalController, 'confirm']);
Flight::route('GET /admin/internships', [$internshipController, 'adminIndex']);
Flight::route('POST /admin/internships/@id/archive', [$internshipController, 'archive']);

Flight::start();
