<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\CompanyRepository;
use App\Repositories\ApplicationRepository;
use App\Repositories\InternshipRepository;
use App\Repositories\TagMappingRepository;
use App\Support\ApplicationMailer;
use App\Support\SessionManager;
use PDO;

final class InternshipController
{
    private const SECTOR_TAGS = [
        'Sante',
        'Tech',
        'Animaux',
        'Commerce',
        'Education',
        'Sport',
        'Culture',
        'Administration',
    ];

    private CompanyRepository $companies;
    private ApplicationRepository $applications;
    private InternshipRepository $internships;
    private TagMappingRepository $tagMappings;
    private ApplicationMailer $applicationMailer;

    public function __construct(PDO $pdo, array $mailConfig)
    {
        $this->companies = new CompanyRepository($pdo);
        $this->applications = new ApplicationRepository($pdo);
        $this->internships = new InternshipRepository($pdo);
        $this->tagMappings = new TagMappingRepository($pdo);
        $this->applicationMailer = new ApplicationMailer($mailConfig);
    }

    public function index(): void
    {
        [$title, $company, $error, $success, $accessDenied] = $this->guardWithCompany();

        if ($accessDenied) {
            $items = [];
            require __DIR__ . '/../Views/internships_list.php';
            return;
        }

        $items = $this->internships->findAllByCompanyId((int) $company['id']);

        if (($_GET['status'] ?? null) === 'created') {
            $success = 'Offre de stage ajoutee.';
        }

        if (($_GET['status'] ?? null) === 'sleeping') {
            $success = "L'offre est maintenant invisible pour les eleves.";
        }

        require __DIR__ . '/../Views/internships_list.php';
    }

    public function showCreateForm(): void
    {
        [$title, $company, $error, $success, $accessDenied] = $this->guardWithCompany();

        if ($accessDenied) {
            $formData = $this->defaultFormData();
            require __DIR__ . '/../Views/internship_form.php';
            return;
        }

        $formData = $this->defaultFormData();
        require __DIR__ . '/../Views/internship_form.php';
    }

    public function create(): void
    {
        [$title, $company, $error, $success, $accessDenied] = $this->guardWithCompany();

        if ($accessDenied) {
            $formData = $this->defaultFormData();
            require __DIR__ . '/../Views/internship_form.php';
            return;
        }

        $formData = [
            'title' => trim((string) ($_POST['title'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'sector_tag' => trim((string) ($_POST['sector_tag'] ?? '')),
            'places_count' => trim((string) ($_POST['places_count'] ?? '')),
            'status' => 'active',
            'academic_year' => $this->currentAcademicYear(),
            'certification' => isset($_POST['certification']) ? '1' : '0',
        ];

        $validationError = $this->validateForm($formData);

        if ($validationError !== null) {
            http_response_code(422);
            $error = $validationError;
            require __DIR__ . '/../Views/internship_form.php';
            return;
        }

        $this->internships->create((int) $company['id'], [
            'title' => $formData['title'],
            'description' => $formData['description'],
            'sector_tag' => $formData['sector_tag'] === '' ? null : $formData['sector_tag'],
            'places_count' => (int) $formData['places_count'],
            'status' => 'active',
            'academic_year' => $formData['academic_year'],
        ]);

        header('Location: /internships?status=created', true, 302);
        exit;
    }

    public function setSleeping(string $id): void
    {
        [$title, $company, $error, $success, $accessDenied] = $this->guardWithCompany();

        if ($accessDenied) {
            $items = [];
            require __DIR__ . '/../Views/internships_list.php';
            return;
        }

        $internshipId = (int) $id;
        $internship = $this->internships->findByIdAndCompanyId($internshipId, (int) $company['id']);

        if ($internship === null) {
            http_response_code(404);
            $error = "Offre introuvable.";
            $items = $this->internships->findAllByCompanyId((int) $company['id']);
            require __DIR__ . '/../Views/internships_list.php';
            return;
        }

        if ((string) $internship['status'] === 'archived') {
            http_response_code(422);
            $error = "Une offre archivee ne peut pas etre repassee en invisible.";
            $items = $this->internships->findAllByCompanyId((int) $company['id']);
            require __DIR__ . '/../Views/internships_list.php';
            return;
        }

        set_internship_status($internshipId, 'sleeping');

        header('Location: /internships?status=sleeping', true, 302);
        exit;
    }

    public function studentList(): void
    {
        $title = 'Offres de stage disponibles';
        $items = get_active_internships();
        $origin = null;
        $markers = $this->buildMapMarkers($items, null);

        require __DIR__ . '/../Views/internships_public.php';
    }

    public function searchPage(): void
    {
        $title = 'Recherche de stages';
        $availableTags = $this->tagMappings->findDistinctTagNames();
        $keyword = trim((string) ($_GET['q'] ?? ''));
        $selectedTag = trim((string) ($_GET['tag'] ?? ''));
        $originLat = trim((string) ($_GET['origin_lat'] ?? ''));
        $originLng = trim((string) ($_GET['origin_lng'] ?? ''));

        if ($selectedTag !== '' && !in_array($selectedTag, $availableTags, true)) {
            $selectedTag = '';
        }

        $items = $this->internships->searchActiveWithCompany(
            $keyword === '' ? null : $keyword,
            $selectedTag === '' ? null : $selectedTag
        );
        $origin = $this->normalizeOrigin($originLat, $originLng);
        $items = $this->enrichWithDistance($items, $origin);
        $markers = $this->buildMapMarkers($items, $origin);

        require __DIR__ . '/../Views/search.php';
    }

    public function showPublicDetail(string $id): void
    {
        $originLat = trim((string) ($_GET['origin_lat'] ?? ''));
        $originLng = trim((string) ($_GET['origin_lng'] ?? ''));
        $origin = $this->normalizeOrigin($originLat, $originLng);
        $currentUser = SessionManager::currentUser();
        $applicationError = null;
        $applicationSuccess = null;
        $applicationData = [
            'message' => '',
            'classe' => '',
        ];
        $internship = $this->internships->findById((int) $id);

        if ($internship === null || (string) $internship['status'] !== 'active') {
            http_response_code(404);
            $title = 'Offre introuvable';
            $markers = [];
            require __DIR__ . '/../Views/internship_detail.php';
            return;
        }

        $title = (string) $internship['title'];
        $items = $this->enrichWithDistance([$internship], $origin);
        $internship = $items[0];
        $markers = $this->buildMapMarkers([$internship], $origin);

        if (($_GET['application'] ?? null) === 'sent') {
            $applicationSuccess = 'Votre candidature a bien ete envoyee.';
        }

        require __DIR__ . '/../Views/internship_detail.php';
    }

    public function apply(string $id): void
    {
        $originLat = trim((string) ($_POST['origin_lat'] ?? ''));
        $originLng = trim((string) ($_POST['origin_lng'] ?? ''));
        $origin = $this->normalizeOrigin($originLat, $originLng);
        $currentUser = SessionManager::currentUser();
        $applicationError = null;
        $applicationSuccess = null;
        $applicationData = [
            'message' => trim((string) ($_POST['message'] ?? '')),
            'classe' => trim((string) ($_POST['classe'] ?? '')),
        ];
        $internship = $this->internships->findById((int) $id);

        if ($internship === null || (string) $internship['status'] !== 'active') {
            http_response_code(404);
            $title = 'Offre introuvable';
            $markers = [];
            require __DIR__ . '/../Views/internship_detail.php';
            return;
        }

        $title = (string) $internship['title'];
        $items = $this->enrichWithDistance([$internship], $origin);
        $internship = $items[0];
        $markers = $this->buildMapMarkers([$internship], $origin);

        if ($currentUser === null) {
            header('Location: /login', true, 302);
            exit;
        }

        if (($currentUser['role'] ?? '') !== 'student') {
            http_response_code(403);
            $applicationError = "Seuls les eleves peuvent candidater.";
            require __DIR__ . '/../Views/internship_detail.php';
            return;
        }

        if ($applicationData['message'] === '') {
            http_response_code(422);
            $applicationError = 'Le message de motivation est obligatoire.';
            require __DIR__ . '/../Views/internship_detail.php';
            return;
        }

        if ($applicationData['classe'] === '') {
            http_response_code(422);
            $applicationError = 'La classe est obligatoire.';
            require __DIR__ . '/../Views/internship_detail.php';
            return;
        }

        $parentEmail = trim((string) ($internship['owner_email'] ?? ''));

        if ($parentEmail === '' || filter_var($parentEmail, FILTER_VALIDATE_EMAIL) === false) {
            http_response_code(500);
            $applicationError = "Impossible de transmettre la candidature a cette offre.";
            require __DIR__ . '/../Views/internship_detail.php';
            return;
        }

        $this->applications->create(
            (int) $internship['id'],
            (int) $currentUser['id'],
            $applicationData['message'],
            $applicationData['classe']
        );

        $sent = $this->applicationMailer->sendToParent(
            $parentEmail,
            (string) $currentUser['email'],
            (string) $internship['title'],
            $applicationData['message'],
            $applicationData['classe']
        );

        if (!$sent) {
            http_response_code(500);
            $applicationError = "La candidature a ete enregistree mais l'email n'a pas pu etre envoye.";
            require __DIR__ . '/../Views/internship_detail.php';
            return;
        }

        $queryString = http_build_query([
            'application' => 'sent',
            'origin_lat' => $originLat,
            'origin_lng' => $originLng,
        ]);

        header('Location: /offers/' . (int) $internship['id'] . '?' . $queryString, true, 302);
        exit;
    }

    public function adminIndex(): void
    {
        [$title, $error, $success, $accessDenied] = $this->guardAdmin();

        $activeAndSleepingItems = [];
        $archivedItems = [];

        if ($accessDenied) {
            require __DIR__ . '/../Views/internships_admin.php';
            return;
        }

        $activeAndSleepingItems = $this->internships->findByStatusesWithCompany(['active', 'sleeping']);
        $archivedItems = get_archived_internships();

        if (($_GET['status'] ?? null) === 'archived') {
            $success = "L'offre a ete archivee.";
        }

        require __DIR__ . '/../Views/internships_admin.php';
    }

    public function archive(string $id): void
    {
        [$title, $error, $success, $accessDenied] = $this->guardAdmin();

        if ($accessDenied) {
            $activeAndSleepingItems = [];
            $archivedItems = [];
            require __DIR__ . '/../Views/internships_admin.php';
            return;
        }

        $internshipId = (int) $id;
        $internship = $this->internships->findById($internshipId);

        if ($internship === null) {
            http_response_code(404);
            $error = "Offre introuvable.";
            $activeAndSleepingItems = $this->internships->findByStatusesWithCompany(['active', 'sleeping']);
            $archivedItems = get_archived_internships();
            require __DIR__ . '/../Views/internships_admin.php';
            return;
        }

        set_internship_status($internshipId, 'archived');

        header('Location: /admin/internships?status=archived', true, 302);
        exit;
    }

    private function guardWithCompany(): array
    {
        $user = SessionManager::currentUser();
        $title = 'Mes offres de stage';

        if ($user === null) {
            header('Location: /login', true, 302);
            exit;
        }

        if (!in_array($user['role'], ['parent', 'company', 'admin'], true)) {
            http_response_code(403);
            return [$title, null, "Acces refuse a la gestion des offres.", null, true];
        }

        $company = $this->companies->findByUserId((int) $user['id']);

        if ($company === null) {
            http_response_code(422);
            return [$title, null, "Completez d'abord votre profil entreprise.", null, true];
        }

        return [$title, $company, null, null, false];
    }

    private function defaultFormData(): array
    {
        return [
            'title' => '',
            'description' => '',
            'sector_tag' => '',
            'places_count' => '',
            'status' => 'active',
            'academic_year' => $this->currentAcademicYear(),
            'certification' => '0',
        ];
    }

    private function guardAdmin(): array
    {
        $user = SessionManager::currentUser();
        $title = 'Administration des offres';

        if ($user === null) {
            header('Location: /login', true, 302);
            exit;
        }

        if (($user['role'] ?? '') !== 'admin') {
            http_response_code(403);
            return [$title, "Acces reserve a l'administration.", null, true];
        }

        return [$title, null, null, false];
    }

    private function validateForm(array $formData): ?string
    {
        if ($formData['title'] === '') {
            return "Le titre est obligatoire.";
        }

        if ($formData['description'] === '') {
            return "La description est obligatoire.";
        }

        if ($formData['sector_tag'] !== '' && !in_array($formData['sector_tag'], self::SECTOR_TAGS, true)) {
            return "Le secteur selectionne est invalide.";
        }

        if ($formData['places_count'] === '' || !ctype_digit($formData['places_count']) || (int) $formData['places_count'] < 1) {
            return "Le nombre de places doit etre un entier positif.";
        }

        if ($formData['certification'] !== '1') {
            return "Vous devez certifier que ce stage respecte la reglementation sur le travail des mineurs.";
        }

        return null;
    }

    public static function sectorTags(): array
    {
        return self::SECTOR_TAGS;
    }

    private function normalizeOrigin(string $lat, string $lng): ?array
    {
        if ($lat === '' || $lng === '' || !is_numeric($lat) || !is_numeric($lng)) {
            return null;
        }

        return [
            'lat' => (float) $lat,
            'lng' => (float) $lng,
        ];
    }

    private function enrichWithDistance(array $items, ?array $origin): array
    {
        foreach ($items as &$item) {
            $lat = $item['company_lat'] ?? null;
            $lng = $item['company_lng'] ?? null;
            $item['distance_km'] = null;

            if ($origin === null || $lat === null || $lng === null || !is_numeric((string) $lat) || !is_numeric((string) $lng)) {
                continue;
            }

            $item['distance_km'] = round(
                haversine_distance_km(
                    $origin['lat'],
                    $origin['lng'],
                    (float) $lat,
                    (float) $lng
                ),
                1
            );
        }
        unset($item);

        usort($items, static function (array $left, array $right): int {
            $leftDistance = $left['distance_km'];
            $rightDistance = $right['distance_km'];

            if ($leftDistance === null && $rightDistance === null) {
                return strcmp((string) $left['title'], (string) $right['title']);
            }

            if ($leftDistance === null) {
                return 1;
            }

            if ($rightDistance === null) {
                return -1;
            }

            return $leftDistance <=> $rightDistance;
        });

        return $items;
    }

    private function buildMapMarkers(array $items, ?array $origin): array
    {
        $markers = [];

        foreach ($items as $item) {
            $lat = $item['company_lat'] ?? null;
            $lng = $item['company_lng'] ?? null;

            if ($lat === null || $lng === null || !is_numeric((string) $lat) || !is_numeric((string) $lng)) {
                continue;
            }

            $markers[] = [
                'lat' => (float) $lat,
                'lng' => (float) $lng,
                'title' => (string) $item['title'],
                'address' => (string) ($item['company_address'] ?? ''),
                'distance' => $item['distance_km'] ?? null,
            ];
        }

        if ($origin !== null) {
            array_unshift($markers, [
                'lat' => $origin['lat'],
                'lng' => $origin['lng'],
                'title' => 'Votre position',
                'address' => '',
                'distance' => 0.0,
                'is_origin' => true,
            ]);
        }

        return $markers;
    }

    private function currentAcademicYear(): string
    {
        $now = new \DateTimeImmutable();
        $year = (int) $now->format('Y');
        $month = (int) $now->format('n');

        if ($month >= 9) {
            return $year . '-' . ($year + 1);
        }

        return ($year - 1) . '-' . $year;
    }
}
