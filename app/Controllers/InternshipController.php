<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\CompanyRepository;
use App\Repositories\ApplicationRepository;
use App\Repositories\InternshipRepository;
use App\Repositories\TagMappingRepository;
use App\Repositories\UserRepository;
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
    private const APPLICATION_STATUSES = ['new', 'contacted', 'accepted', 'rejected'];
    private const APPLICATION_STATUS_LABELS = [
        'new' => 'Nouvelle',
        'contacted' => 'Contactee',
        'accepted' => 'Acceptee',
        'rejected' => 'Refusee',
    ];

    private PDO $pdo;
    private CompanyRepository $companies;
    private ApplicationRepository $applications;
    private InternshipRepository $internships;
    private TagMappingRepository $tagMappings;
    private UserRepository $users;
    private ApplicationMailer $applicationMailer;

    public function __construct(PDO $pdo, array $mailConfig)
    {
        $this->pdo = $pdo;
        $this->companies = new CompanyRepository($pdo);
        $this->applications = new ApplicationRepository($pdo);
        $this->internships = new InternshipRepository($pdo);
        $this->tagMappings = new TagMappingRepository($pdo);
        $this->users = new UserRepository($pdo);
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
        $newApplicationsCount = $this->applications->countNewByCompanyId((int) $company['id']);

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

        app_redirect('/internships?status=created');
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

        app_redirect('/internships?status=sleeping');
    }

    public function studentList(): void
    {
        $title = 'Offres de stage disponibles';
        $currentUser = SessionManager::currentUser();
        $items = get_active_internships();
        $origin = null;
        $markers = $this->buildMapMarkers($items, null);

        require __DIR__ . '/../Views/internships_public.php';
    }

    public function searchPage(): void
    {
        $title = 'Recherche de stages';
        $currentUser = SessionManager::currentUser();
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
            app_redirect('/login?' . http_build_query([
                'return_to' => '/offers/' . (int) $internship['id'] . '?' . http_build_query([
                    'origin_lat' => $originLat,
                    'origin_lng' => $originLng,
                ]) . '#apply',
            ]));
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

        $existingApplication = $this->applications->findByInternshipIdAndStudentId(
            (int) $internship['id'],
            (int) $currentUser['id']
        );

        if ($existingApplication !== null) {
            http_response_code(409);
            $applicationError = "Vous avez deja candidate a cette offre.";
            require __DIR__ . '/../Views/internship_detail.php';
            return;
        }

        $applicationId = $this->applications->create(
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
            $this->applications->deleteById($applicationId);
            http_response_code(500);
            $applicationError = "L'email n'a pas pu etre envoye. Reessayez plus tard.";
            require __DIR__ . '/../Views/internship_detail.php';
            return;
        }

        $queryString = http_build_query([
            'application' => 'sent',
            'origin_lat' => $originLat,
            'origin_lng' => $originLng,
        ]);

        app_redirect('/offers/' . (int) $internship['id'] . '?' . $queryString);
    }

    public function studentApplications(): void
    {
        $currentUser = SessionManager::currentUser();

        if ($currentUser === null) {
            app_redirect('/login?' . http_build_query([
                'return_to' => '/my-applications',
            ]));
        }

        if (($currentUser['role'] ?? '') !== 'student') {
            http_response_code(403);
            $title = 'Mes candidatures';
            $items = [];
            $error = "Cette page est reservee aux eleves.";
            require __DIR__ . '/../Views/student_applications.php';
            return;
        }

        $title = 'Mes candidatures';
        $items = $this->applications->findAllByStudentId((int) $currentUser['id']);
        $error = null;

        require __DIR__ . '/../Views/student_applications.php';
    }

    public function companyApplications(): void
    {
        [$title, $company, $error, $success, $accessDenied] = $this->guardWithCompany();

        $availableStatuses = self::APPLICATION_STATUS_LABELS;
        $availableInternships = [];
        $items = [];
        $selectedStatus = trim((string) ($_GET['status_filter'] ?? ''));
        $selectedInternshipId = trim((string) ($_GET['internship_id'] ?? ''));
        $newApplicationsCount = 0;

        if ($accessDenied) {
            require __DIR__ . '/../Views/company_applications.php';
            return;
        }

        $title = 'Candidatures recues';
        $availableInternships = $this->internships->findAllByCompanyId((int) $company['id']);
        $newApplicationsCount = $this->applications->countNewByCompanyId((int) $company['id']);

        if ($selectedStatus !== '' && !isset(self::APPLICATION_STATUS_LABELS[$selectedStatus])) {
            $selectedStatus = '';
        }

        $internshipId = null;

        if ($selectedInternshipId !== '' && ctype_digit($selectedInternshipId)) {
            foreach ($availableInternships as $internshipRow) {
                if ((int) $internshipRow['id'] === (int) $selectedInternshipId) {
                    $internshipId = (int) $selectedInternshipId;
                    break;
                }
            }
        }

        $items = $this->applications->findAllByCompanyId(
            (int) $company['id'],
            $internshipId,
            $selectedStatus === '' ? null : $selectedStatus
        );

        if (($_GET['status'] ?? null) === 'updated' && ($_GET['offer_status'] ?? null) === 'full') {
            $success = "Le statut de la candidature a ete mis a jour. L'offre est maintenant invisible car toutes les places sont prises.";
        } elseif (($_GET['status'] ?? null) === 'updated' && ($_GET['offer_status'] ?? null) === 'reopened') {
            $success = "Le statut de la candidature a ete mis a jour. L'offre redevient visible car une place s'est liberee.";
        } elseif (($_GET['status'] ?? null) === 'updated') {
            $success = 'Le statut de la candidature a ete mis a jour.';
        }

        require __DIR__ . '/../Views/company_applications.php';
    }

    public function updateApplicationStatus(string $id): void
    {
        [$title, $company, $error, $success, $accessDenied] = $this->guardWithCompany();

        $availableStatuses = self::APPLICATION_STATUS_LABELS;
        $availableInternships = [];
        $items = [];
        $selectedStatus = trim((string) ($_POST['status_filter'] ?? ''));
        $selectedInternshipId = trim((string) ($_POST['internship_id'] ?? ''));
        $newApplicationsCount = 0;

        if ($accessDenied) {
            require __DIR__ . '/../Views/company_applications.php';
            return;
        }

        $title = 'Candidatures recues';
        $availableInternships = $this->internships->findAllByCompanyId((int) $company['id']);
        $newApplicationsCount = $this->applications->countNewByCompanyId((int) $company['id']);

        $applicationId = (int) $id;
        $newStatus = trim((string) ($_POST['new_status'] ?? ''));

        if (!in_array($newStatus, self::APPLICATION_STATUSES, true)) {
            http_response_code(422);
            $error = 'Le statut de candidature demande est invalide.';
            $items = $this->applications->findAllByCompanyId((int) $company['id']);
            require __DIR__ . '/../Views/company_applications.php';
            return;
        }

        $application = $this->applications->findByIdAndCompanyId($applicationId, (int) $company['id']);

        if ($application === null) {
            http_response_code(404);
            $error = 'Candidature introuvable.';
            $items = $this->applications->findAllByCompanyId((int) $company['id']);
            require __DIR__ . '/../Views/company_applications.php';
            return;
        }

        $offerClosedAutomatically = false;
        $offerReopenedAutomatically = false;

        try {
            $this->pdo->beginTransaction();
            $previousStatus = (string) ($application['status'] ?? '');
            $this->applications->updateStatusById($applicationId, $newStatus);

            $internship = $this->internships->findByIdAndCompanyId((int) $application['internship_id'], (int) $company['id']);

            if (
                $internship !== null
                && (int) ($internship['places_count'] ?? 0) > 0
            ) {
                $acceptedCount = $this->applications->countAcceptedByInternshipId((int) $internship['id']);
                $placesCount = (int) $internship['places_count'];
                $internshipStatus = (string) $internship['status'];

                if ($internshipStatus === 'active' && $acceptedCount >= $placesCount) {
                    $this->internships->updateStatusById((int) $internship['id'], 'sleeping');
                    $offerClosedAutomatically = true;
                }

                if (
                    $internshipStatus === 'sleeping'
                    && $previousStatus === 'accepted'
                    && $newStatus !== 'accepted'
                    && ($acceptedCount + 1) >= $placesCount
                    && $acceptedCount < $placesCount
                ) {
                    $this->internships->updateStatusById((int) $internship['id'], 'active');
                    $offerReopenedAutomatically = true;
                }
            }

            $this->pdo->commit();
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            http_response_code(500);
            $error = "Impossible de mettre a jour la candidature pour le moment.";
            $items = $this->applications->findAllByCompanyId((int) $company['id']);
            require __DIR__ . '/../Views/company_applications.php';
            return;
        }

        $query = [
            'status' => 'updated',
        ];

        if ($offerClosedAutomatically) {
            $query['offer_status'] = 'full';
        } elseif ($offerReopenedAutomatically) {
            $query['offer_status'] = 'reopened';
        }

        if ($selectedInternshipId !== '' && ctype_digit($selectedInternshipId)) {
            $query['internship_id'] = $selectedInternshipId;
        }

        if ($selectedStatus !== '' && isset(self::APPLICATION_STATUS_LABELS[$selectedStatus])) {
            $query['status_filter'] = $selectedStatus;
        }

        app_redirect('/company-applications?' . http_build_query($query));
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

    public function adminDashboard(): void
    {
        [$title, $error, $success, $accessDenied] = $this->guardAdmin();

        $title = 'Tableau de bord college';
        $availableStatuses = self::APPLICATION_STATUS_LABELS;
        $availableClasses = $this->applications->findDistinctClasses();
        $availableCompanies = $this->applications->findDistinctCompaniesForAdmin();
        $selectedClass = trim((string) ($_GET['class_filter'] ?? ''));
        $selectedStatus = trim((string) ($_GET['status_filter'] ?? ''));
        $selectedCompanyId = trim((string) ($_GET['company_id'] ?? ''));
        $selectedInternshipId = trim((string) ($_GET['internship_id'] ?? ''));
        $availableInternships = [];
        $items = [];
        $summary = [
            'total' => 0,
            'new' => 0,
            'contacted' => 0,
            'accepted' => 0,
            'rejected' => 0,
            'students_without_application' => 0,
        ];
        $studentsWithoutApplications = [];
        $openOffers = [];
        $fullOffers = [];
        $overloadedOffers = [];

        if ($accessDenied) {
            require __DIR__ . '/../Views/admin_dashboard.php';
            return;
        }

        if (!in_array($selectedClass, $availableClasses, true)) {
            $selectedClass = '';
        }

        if (!isset(self::APPLICATION_STATUS_LABELS[$selectedStatus])) {
            $selectedStatus = '';
        }

        $companyId = $this->validateEntityFilter($selectedCompanyId, $availableCompanies);
        $availableInternships = $this->applications->findDistinctInternshipsForAdmin($companyId);
        $internshipId = $this->validateEntityFilter($selectedInternshipId, $availableInternships);

        $items = $this->applications->findAllForAdmin(
            $selectedClass === '' ? null : $selectedClass,
            $selectedStatus === '' ? null : $selectedStatus,
            $companyId,
            $internshipId
        );
        $summary = $this->buildApplicationSummary($items);
        $summary['students_without_application'] = $this->users->countStudentsWithoutApplications();

        $studentsWithoutApplications = $this->users->findStudentsWithoutApplications(25);
        $openOffers = $this->internships->findOpenOffersOverview($companyId, $internshipId);

        foreach ($openOffers as &$offer) {
            $placesCount = max(0, (int) ($offer['places_count'] ?? 0));
            $acceptedCount = (int) ($offer['accepted_applications'] ?? 0);
            $totalApplications = (int) ($offer['total_applications'] ?? 0);
            $offer['remaining_places'] = max(0, $placesCount - $acceptedCount);
            $offer['is_full'] = $placesCount > 0 && $acceptedCount >= $placesCount;
            $offer['is_overloaded'] = $placesCount > 0 && $totalApplications > $placesCount;
        }
        unset($offer);

        $fullOffers = array_values(array_filter(
            $openOffers,
            static fn (array $offer): bool => !empty($offer['is_full'])
        ));
        $overloadedOffers = array_values(array_filter(
            $openOffers,
            static fn (array $offer): bool => !empty($offer['is_overloaded'])
        ));

        require __DIR__ . '/../Views/admin_dashboard.php';
    }

    public function exportAdminDashboardCsv(): void
    {
        [$title, $error, $success, $accessDenied] = $this->guardAdmin();

        if ($accessDenied) {
            require __DIR__ . '/../Views/internships_admin.php';
            return;
        }

        $availableClasses = $this->applications->findDistinctClasses();
        $availableCompanies = $this->applications->findDistinctCompaniesForAdmin();
        $selectedClass = trim((string) ($_GET['class_filter'] ?? ''));
        $selectedStatus = trim((string) ($_GET['status_filter'] ?? ''));
        $selectedCompanyId = trim((string) ($_GET['company_id'] ?? ''));
        $selectedInternshipId = trim((string) ($_GET['internship_id'] ?? ''));

        if (!in_array($selectedClass, $availableClasses, true)) {
            $selectedClass = '';
        }

        if (!isset(self::APPLICATION_STATUS_LABELS[$selectedStatus])) {
            $selectedStatus = '';
        }

        $companyId = $this->validateEntityFilter($selectedCompanyId, $availableCompanies);
        $availableInternships = $this->applications->findDistinctInternshipsForAdmin($companyId);
        $internshipId = $this->validateEntityFilter($selectedInternshipId, $availableInternships);

        $items = $this->applications->findAllForAdmin(
            $selectedClass === '' ? null : $selectedClass,
            $selectedStatus === '' ? null : $selectedStatus,
            $companyId,
            $internshipId
        );

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="suivi-college-' . date('Ymd-His') . '.csv"');

        $output = fopen('php://output', 'wb');

        if ($output === false) {
            http_response_code(500);
            echo 'Impossible de generer le CSV.';
            exit;
        }

        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, [
            'Date',
            'Eleve',
            'Classe',
            'Statut candidature',
            'Offre',
            'Entreprise',
            'Statut offre',
            'Annee scolaire',
            'Message',
            'Anonymisee',
        ], ';');

        foreach ($items as $item) {
            $studentLabel = (string) ($item['student_email'] ?? $item['student_pseudonym'] ?? 'eleve non renseigne');
            $statusLabel = self::APPLICATION_STATUS_LABELS[(string) ($item['status'] ?? '')] ?? (string) ($item['status'] ?? '');
            $isAnonymized = (string) ($item['anonymized_at'] ?? '') !== '';

            fputcsv($output, [
                date('d/m/Y H:i', strtotime((string) $item['created_at'])),
                $studentLabel,
                (string) ($item['classe'] ?? ''),
                $statusLabel,
                (string) ($item['internship_title'] ?? ''),
                (string) ($item['company_label'] ?? ''),
                (string) ($item['internship_status'] ?? ''),
                (string) ($item['academic_year'] ?? ''),
                (string) ($item['message'] ?? ''),
                $isAnonymized ? 'oui' : 'non',
            ], ';');
        }

        fclose($output);
        exit;
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

        app_redirect('/admin/internships?status=archived');
    }

    private function guardWithCompany(): array
    {
        $user = SessionManager::currentUser();
        $title = 'Mes offres de stage';

        if ($user === null) {
            app_redirect('/login');
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
            app_redirect('/login');
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

    public static function applicationStatusLabels(): array
    {
        return self::APPLICATION_STATUS_LABELS;
    }

    private function validateEntityFilter(string $selectedId, array $rows): ?int
    {
        if ($selectedId === '' || !ctype_digit($selectedId)) {
            return null;
        }

        foreach ($rows as $row) {
            if ((int) ($row['id'] ?? 0) === (int) $selectedId) {
                return (int) $selectedId;
            }
        }

        return null;
    }

    private function buildApplicationSummary(array $items): array
    {
        $summary = [
            'total' => count($items),
            'new' => 0,
            'contacted' => 0,
            'accepted' => 0,
            'rejected' => 0,
        ];

        foreach ($items as $item) {
            $status = (string) ($item['status'] ?? '');

            if (isset($summary[$status])) {
                $summary[$status]++;
            }
        }

        return $summary;
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
