<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\CompanyRepository;
use App\Repositories\ApplicationRepository;
use App\Repositories\ApplicationMessageRepository;
use App\Repositories\InternshipRepository;
use App\Repositories\NotificationRepository;
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
    private const VALIDATION_STATUS_LABELS = [
        'pending' => 'En attente',
        'approved' => 'Validee',
        'rejected' => 'Refusee',
    ];

    private PDO $pdo;
    private CompanyRepository $companies;
    private ApplicationRepository $applications;
    private ApplicationMessageRepository $applicationMessages;
    private InternshipRepository $internships;
    private NotificationRepository $notifications;
    private TagMappingRepository $tagMappings;
    private UserRepository $users;
    private ApplicationMailer $applicationMailer;

    public function __construct(PDO $pdo, array $mailConfig)
    {
        $this->pdo = $pdo;
        $this->companies = new CompanyRepository($pdo);
        $this->applications = new ApplicationRepository($pdo);
        $this->applicationMessages = new ApplicationMessageRepository($pdo);
        $this->internships = new InternshipRepository($pdo);
        $this->notifications = new NotificationRepository($pdo);
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
            $success = "Offre enregistree. Elle restera invisible tant qu'elle n'aura pas ete validee par l'administration.";
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
            'validation_status' => 'pending',
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

        if (
            $internship === null
            || (string) $internship['status'] !== 'active'
            || (string) ($internship['validation_status'] ?? '') !== 'approved'
            || (string) ($internship['company_validation_status'] ?? '') !== 'approved'
        ) {
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

        if (
            $internship === null
            || (string) $internship['status'] !== 'active'
            || (string) ($internship['validation_status'] ?? '') !== 'approved'
            || (string) ($internship['company_validation_status'] ?? '') !== 'approved'
        ) {
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

        $companyNotificationEmail = trim((string) ($internship['owner_email'] ?? ''));

        if ($companyNotificationEmail === '' || filter_var($companyNotificationEmail, FILTER_VALIDATE_EMAIL) === false) {
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

        try {
            $this->pdo->beginTransaction();
            $this->users->updateSchoolClassById((int) $currentUser['id'], $applicationData['classe']);

            $applicationId = $this->applications->create(
                (int) $internship['id'],
                (int) $currentUser['id'],
                $applicationData['message'],
                $applicationData['classe']
            );

            $this->applicationMessages->create(
                $applicationId,
                (int) $currentUser['id'],
                'student',
                'Eleve',
                $applicationData['message']
            );

            $this->pdo->commit();
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            http_response_code(500);
            $applicationError = "La candidature n'a pas pu etre enregistree. Reessayez plus tard.";
            require __DIR__ . '/../Views/internship_detail.php';
            return;
        }

        $companyOwnerUserId = (int) ($internship['owner_user_id'] ?? 0);

        if ($companyOwnerUserId > 0) {
            $this->notifications->create(
                $companyOwnerUserId,
                'new_application',
                'Nouvelle candidature recue',
                'Une nouvelle candidature est disponible pour l\'offre "' . (string) $internship['title'] . '".',
                '/applications/' . (int) $applicationId
            );
        }

        $this->applicationMailer->sendNewApplicationNotification(
            $companyNotificationEmail,
            (string) $internship['title']
        );

        app_redirect('/applications/' . (int) $applicationId . '?status=sent');
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

    public function showApplicationThread(string $id): void
    {
        $currentUser = SessionManager::currentUser();
        $applicationId = (int) $id;
        [$thread, $error, $accessDenied, $canReply] = $this->resolveApplicationThreadContext($applicationId, $currentUser);
        $title = 'Discussion de candidature';
        $messages = [];
        $messageDraft = '';
        $success = null;

        if ($thread !== null) {
            $messages = $this->composeThreadMessages(
                $thread,
                $this->applicationMessages->findAllByApplicationId($applicationId)
            );
            $title = 'Discussion - ' . (string) ($thread['internship_title'] ?? 'Candidature');
        }

        if (($_GET['status'] ?? null) === 'sent') {
            $success = 'Candidature envoyee. Les echanges passent maintenant uniquement par cette discussion.';
        }

        if (($_GET['status'] ?? null) === 'message-sent') {
            $success = 'Votre message a bien ete envoye.';
        }

        require __DIR__ . '/../Views/application_thread.php';
    }

    public function postApplicationMessage(string $id): void
    {
        $currentUser = SessionManager::currentUser();
        $applicationId = (int) $id;
        [$thread, $error, $accessDenied, $canReply] = $this->resolveApplicationThreadContext($applicationId, $currentUser);
        $title = 'Discussion de candidature';
        $messages = $thread === null
            ? []
            : $this->composeThreadMessages(
                $thread,
                $this->applicationMessages->findAllByApplicationId($applicationId)
            );
        $messageDraft = trim((string) ($_POST['body'] ?? ''));
        $success = null;

        if ($thread !== null) {
            $title = 'Discussion - ' . (string) ($thread['internship_title'] ?? 'Candidature');
        }

        if ($accessDenied || $thread === null) {
            require __DIR__ . '/../Views/application_thread.php';
            return;
        }

        if (!$canReply) {
            http_response_code(403);
            $error = 'Ce compte peut consulter cette discussion mais ne peut pas y repondre.';
            require __DIR__ . '/../Views/application_thread.php';
            return;
        }

        if ($messageDraft === '') {
            http_response_code(422);
            $error = 'Le message est obligatoire.';
            require __DIR__ . '/../Views/application_thread.php';
            return;
        }

        $senderRole = (string) ($currentUser['role'] ?? '');
        $senderLabel = $senderRole === 'student'
            ? 'Eleve'
            : (string) ($thread['company_label'] ?? 'Entreprise');

        $this->applicationMessages->create(
            $applicationId,
            (int) $currentUser['id'],
            $senderRole,
            $senderLabel,
            $messageDraft
        );

        $recipientEmail = null;
        $recipientUserId = null;
        $notificationTitle = 'Nouvelle actualite';
        $notificationBody = 'Une nouveaute vous attend dans Avenir Pro.';

        if ($senderRole === 'student') {
            $candidateEmail = trim((string) ($thread['company_owner_email'] ?? ''));
            $recipientEmail = filter_var($candidateEmail, FILTER_VALIDATE_EMAIL) ? $candidateEmail : null;
            $recipientUserId = (int) ($thread['company_owner_user_id'] ?? 0);
            $notificationTitle = 'Nouveau message eleve';
            $notificationBody = 'Un eleve a poste un nouveau message dans une discussion de candidature.';
        } elseif (in_array($senderRole, ['company', 'parent'], true)) {
            $candidateEmail = trim((string) ($thread['student_email'] ?? ''));
            $recipientEmail = filter_var($candidateEmail, FILTER_VALIDATE_EMAIL) ? $candidateEmail : null;
            $recipientUserId = (int) ($thread['student_id'] ?? 0);
            $notificationTitle = 'Nouveau message entreprise';
            $notificationBody = 'Une entreprise a poste un nouveau message dans votre discussion de candidature.';
        }

        if ($recipientUserId !== null && $recipientUserId > 0) {
            $this->notifications->create(
                $recipientUserId,
                'new_message',
                $notificationTitle,
                $notificationBody,
                '/applications/' . $applicationId
            );
        }

        if ($recipientEmail !== null) {
            $this->applicationMailer->sendNewMessageNotification(
                $recipientEmail,
                (string) ($thread['internship_title'] ?? 'Candidature')
            );
        }

        app_redirect('/applications/' . $applicationId . '?status=message-sent');
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
        $statusActuallyChanged = false;

        try {
            $this->pdo->beginTransaction();
            $previousStatus = (string) ($application['status'] ?? '');
            $statusActuallyChanged = $previousStatus !== $newStatus;
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

        $studentId = (int) ($application['student_id'] ?? 0);

        if ($statusActuallyChanged && $studentId > 0) {
            $student = $this->users->findById($studentId);

            if ($student !== null) {
                $statusLabel = self::APPLICATION_STATUS_LABELS[$newStatus] ?? $newStatus;
                $this->notifications->create(
                    $studentId,
                    'application_status',
                    'Statut de candidature mis a jour',
                    'Le statut de votre candidature pour l\'offre "' . (string) ($internship['title'] ?? 'Stage') . '" est maintenant : ' . $statusLabel . '.',
                    '/applications/' . $applicationId
                );

                $studentEmail = trim((string) ($student['email'] ?? ''));

                if ($studentEmail !== '' && filter_var($studentEmail, FILTER_VALIDATE_EMAIL) !== false) {
                    $this->applicationMailer->sendApplicationStatusNotification(
                        $studentEmail,
                        (string) ($internship['title'] ?? 'Stage'),
                        $statusLabel
                    );
                }
            }
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

        $title = 'Moderation entreprises et offres';
        $companiesItems = [];
        $moderationItems = [];
        $archivedItems = [];

        if ($accessDenied) {
            require __DIR__ . '/../Views/internships_admin.php';
            return;
        }

        $companiesItems = $this->companies->findAllForModeration();
        $moderationItems = $this->internships->findAllForModeration();
        $archivedItems = get_archived_internships();

        if (($_GET['status'] ?? null) === 'archived') {
            $success = "L'offre a ete archivee.";
        }

        if (($_GET['status'] ?? null) === 'company-approved') {
            $success = "L'entreprise a ete validee.";
        }

        if (($_GET['status'] ?? null) === 'company-rejected') {
            $success = "L'entreprise a ete refusee.";
        }

        if (($_GET['status'] ?? null) === 'internship-approved') {
            $success = "L'offre a ete validee.";
        }

        if (($_GET['status'] ?? null) === 'internship-rejected') {
            $success = "L'offre a ete refusee.";
        }

        require __DIR__ . '/../Views/internships_admin.php';
    }

    public function adminDashboard(): void
    {
        [$title, $error, $success, $accessDenied, $canManageInternshipAdministration, $scopeClass] = $this->guardCollegeDashboard();

        $title = 'Tableau de bord college';
        $availableStatuses = self::APPLICATION_STATUS_LABELS;
        $availableClasses = $this->users->findDistinctStudentClasses();
        $availableCompanies = $this->applications->findDistinctCompaniesForAdmin();
        $selectedClass = trim((string) ($_GET['class_filter'] ?? ''));
        $selectedStatus = trim((string) ($_GET['status_filter'] ?? ''));
        $selectedCompanyId = trim((string) ($_GET['company_id'] ?? ''));
        $selectedInternshipId = trim((string) ($_GET['internship_id'] ?? ''));
        $selectedStudentSearch = trim((string) ($_GET['student_search'] ?? ''));
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
        $studentDirectory = [];
        $openOffers = [];
        $fullOffers = [];
        $overloadedOffers = [];

        if ($accessDenied) {
            require __DIR__ . '/../Views/admin_dashboard.php';
            return;
        }

        if ($scopeClass !== null) {
            $availableClasses = [$scopeClass];
            $selectedClass = $scopeClass;
        } elseif (!in_array($selectedClass, $availableClasses, true)) {
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
            $internshipId,
            $selectedStudentSearch === '' ? null : $selectedStudentSearch
        );
        $items = $this->decorateStudentRows($items);
        $summary = $this->buildApplicationSummary($items);
        $summary['students_without_application'] = $this->users->countStudentsWithoutApplications(
            $selectedClass === '' ? null : $selectedClass,
            $selectedStudentSearch === '' ? null : $selectedStudentSearch
        );

        $studentsWithoutApplications = $this->decorateStudentRows(
            $this->users->findStudentsWithoutApplications(
                25,
                $selectedClass === '' ? null : $selectedClass,
                $selectedStudentSearch === '' ? null : $selectedStudentSearch
            )
        );
        $studentDirectory = $this->decorateStudentRows(
            $this->users->findStudentsDirectory(
                200,
                $selectedClass === '' ? null : $selectedClass,
                $selectedStudentSearch === '' ? null : $selectedStudentSearch
            )
        );
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
        [$title, $error, $success, $accessDenied, $canManageInternshipAdministration, $scopeClass] = $this->guardCollegeDashboard();

        if ($accessDenied) {
            $availableStatuses = self::APPLICATION_STATUS_LABELS;
            $availableClasses = [];
            $availableCompanies = [];
            $availableInternships = [];
            $selectedClass = '';
            $selectedStatus = '';
            $selectedCompanyId = '';
            $selectedInternshipId = '';
            $selectedStudentSearch = '';
            $items = [];
            $summary = [];
            $studentsWithoutApplications = [];
            $studentDirectory = [];
            $openOffers = [];
            $fullOffers = [];
            $overloadedOffers = [];
            require __DIR__ . '/../Views/admin_dashboard.php';
            return;
        }

        $availableClasses = $this->users->findDistinctStudentClasses();
        $availableCompanies = $this->applications->findDistinctCompaniesForAdmin();
        $selectedClass = trim((string) ($_GET['class_filter'] ?? ''));
        $selectedStatus = trim((string) ($_GET['status_filter'] ?? ''));
        $selectedCompanyId = trim((string) ($_GET['company_id'] ?? ''));
        $selectedInternshipId = trim((string) ($_GET['internship_id'] ?? ''));
        $selectedStudentSearch = trim((string) ($_GET['student_search'] ?? ''));

        if ($scopeClass !== null) {
            $availableClasses = [$scopeClass];
            $selectedClass = $scopeClass;
        } elseif (!in_array($selectedClass, $availableClasses, true)) {
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
            $internshipId,
            $selectedStudentSearch === '' ? null : $selectedStudentSearch
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
            $studentLabel = $this->formatStudentLabel($item);
            $statusLabel = self::APPLICATION_STATUS_LABELS[(string) ($item['status'] ?? '')] ?? (string) ($item['status'] ?? '');
            $isAnonymized = (string) ($item['anonymized_at'] ?? '') !== '';

            fputcsv($output, [
                date('d/m/Y H:i', strtotime((string) $item['created_at'])),
                $studentLabel,
                (string) ($item['student_school_class'] ?? $item['classe'] ?? ''),
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
            $companiesItems = [];
            $moderationItems = [];
            $archivedItems = [];
            require __DIR__ . '/../Views/internships_admin.php';
            return;
        }

        $internshipId = (int) $id;
        $internship = $this->internships->findById($internshipId);

        if ($internship === null) {
            http_response_code(404);
            $error = "Offre introuvable.";
            $companiesItems = $this->companies->findAllForModeration();
            $moderationItems = $this->internships->findAllForModeration();
            $archivedItems = get_archived_internships();
            require __DIR__ . '/../Views/internships_admin.php';
            return;
        }

        set_internship_status($internshipId, 'archived');

        app_redirect('/admin/internships?status=archived');
    }

    public function approveCompany(string $id): void
    {
        [$title, $error, $success, $accessDenied] = $this->guardAdmin();

        if ($accessDenied) {
            $companiesItems = [];
            $moderationItems = [];
            $archivedItems = [];
            require __DIR__ . '/../Views/internships_admin.php';
            return;
        }

        $companyId = (int) $id;
        $company = $this->companies->findById($companyId);

        if ($company === null) {
            http_response_code(404);
            $error = "Entreprise introuvable.";
            $companiesItems = $this->companies->findAllForModeration();
            $moderationItems = $this->internships->findAllForModeration();
            $archivedItems = get_archived_internships();
            require __DIR__ . '/../Views/internships_admin.php';
            return;
        }

        $this->companies->updateValidationStatusById($companyId, 'approved');

        $owner = $this->users->findById((int) $company['user_id']);

        if ($owner !== null) {
            $companyLabel = (string) ($company['name'] ?? $company['siret'] ?? 'Votre entreprise');
            $this->notifications->create(
                (int) $owner['id'],
                'company_validation',
                'Entreprise validee',
                'Votre profil entreprise "' . $companyLabel . '" a ete valide par l\'administration.',
                '/company-profile'
            );

            $ownerEmail = trim((string) ($owner['email'] ?? ''));

            if ($ownerEmail !== '' && filter_var($ownerEmail, FILTER_VALIDATE_EMAIL) !== false) {
                $this->applicationMailer->sendCompanyValidationNotification(
                    $ownerEmail,
                    $companyLabel,
                    'Validee'
                );
            }
        }

        app_redirect('/admin/internships?status=company-approved');
    }

    public function rejectCompany(string $id): void
    {
        [$title, $error, $success, $accessDenied] = $this->guardAdmin();

        if ($accessDenied) {
            $companiesItems = [];
            $moderationItems = [];
            $archivedItems = [];
            require __DIR__ . '/../Views/internships_admin.php';
            return;
        }

        $companyId = (int) $id;
        $company = $this->companies->findById($companyId);

        if ($company === null) {
            http_response_code(404);
            $error = "Entreprise introuvable.";
            $companiesItems = $this->companies->findAllForModeration();
            $moderationItems = $this->internships->findAllForModeration();
            $archivedItems = get_archived_internships();
            require __DIR__ . '/../Views/internships_admin.php';
            return;
        }

        $this->companies->updateValidationStatusById($companyId, 'rejected');

        $owner = $this->users->findById((int) $company['user_id']);

        if ($owner !== null) {
            $companyLabel = (string) ($company['name'] ?? $company['siret'] ?? 'Votre entreprise');
            $this->notifications->create(
                (int) $owner['id'],
                'company_validation',
                'Entreprise refusee',
                'Votre profil entreprise "' . $companyLabel . '" a ete refuse par l\'administration.',
                '/company-profile'
            );

            $ownerEmail = trim((string) ($owner['email'] ?? ''));

            if ($ownerEmail !== '' && filter_var($ownerEmail, FILTER_VALIDATE_EMAIL) !== false) {
                $this->applicationMailer->sendCompanyValidationNotification(
                    $ownerEmail,
                    $companyLabel,
                    'Refusee'
                );
            }
        }

        app_redirect('/admin/internships?status=company-rejected');
    }

    public function approveInternship(string $id): void
    {
        [$title, $error, $success, $accessDenied] = $this->guardAdmin();

        if ($accessDenied) {
            $companiesItems = [];
            $moderationItems = [];
            $archivedItems = [];
            require __DIR__ . '/../Views/internships_admin.php';
            return;
        }

        $internshipId = (int) $id;
        $internship = $this->internships->findById($internshipId);

        if ($internship === null) {
            http_response_code(404);
            $error = "Offre introuvable.";
            $companiesItems = $this->companies->findAllForModeration();
            $moderationItems = $this->internships->findAllForModeration();
            $archivedItems = get_archived_internships();
            require __DIR__ . '/../Views/internships_admin.php';
            return;
        }

        $this->internships->updateValidationStatusById($internshipId, 'approved');

        $ownerUserId = (int) ($internship['owner_user_id'] ?? 0);
        $ownerEmail = trim((string) ($internship['owner_email'] ?? ''));

        if ($ownerUserId > 0) {
            $this->notifications->create(
                $ownerUserId,
                'internship_validation',
                'Offre validee',
                'Votre offre "' . (string) ($internship['title'] ?? 'Offre') . '" a ete validee par l\'administration.',
                '/internships'
            );
        }

        if ($ownerEmail !== '' && filter_var($ownerEmail, FILTER_VALIDATE_EMAIL) !== false) {
            $this->applicationMailer->sendInternshipValidationNotification(
                $ownerEmail,
                (string) ($internship['title'] ?? 'Offre'),
                'Validee'
            );
        }

        app_redirect('/admin/internships?status=internship-approved');
    }

    public function rejectInternship(string $id): void
    {
        [$title, $error, $success, $accessDenied] = $this->guardAdmin();

        if ($accessDenied) {
            $companiesItems = [];
            $moderationItems = [];
            $archivedItems = [];
            require __DIR__ . '/../Views/internships_admin.php';
            return;
        }

        $internshipId = (int) $id;
        $internship = $this->internships->findById($internshipId);

        if ($internship === null) {
            http_response_code(404);
            $error = "Offre introuvable.";
            $companiesItems = $this->companies->findAllForModeration();
            $moderationItems = $this->internships->findAllForModeration();
            $archivedItems = get_archived_internships();
            require __DIR__ . '/../Views/internships_admin.php';
            return;
        }

        $this->internships->updateValidationStatusById($internshipId, 'rejected');

        $ownerUserId = (int) ($internship['owner_user_id'] ?? 0);
        $ownerEmail = trim((string) ($internship['owner_email'] ?? ''));

        if ($ownerUserId > 0) {
            $this->notifications->create(
                $ownerUserId,
                'internship_validation',
                'Offre refusee',
                'Votre offre "' . (string) ($internship['title'] ?? 'Offre') . '" a ete refusee par l\'administration.',
                '/internships'
            );
        }

        if ($ownerEmail !== '' && filter_var($ownerEmail, FILTER_VALIDATE_EMAIL) !== false) {
            $this->applicationMailer->sendInternshipValidationNotification(
                $ownerEmail,
                (string) ($internship['title'] ?? 'Offre'),
                'Refusee'
            );
        }

        app_redirect('/admin/internships?status=internship-rejected');
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

        if ((string) ($company['validation_status'] ?? 'pending') !== 'approved') {
            http_response_code(403);
            return [$title, $company, "Votre entreprise doit d'abord etre validee par l'administration avant de publier des offres ou d'echanger avec les eleves.", null, true];
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

    private function guardCollegeDashboard(): array
    {
        $user = SessionManager::currentUser();
        $title = 'Tableau de bord college';

        if ($user === null) {
            app_redirect('/login');
        }

        $role = (string) ($user['role'] ?? '');
        $canManageInternshipAdministration = $role === 'admin';

        if (!in_array($role, ['admin', 'teacher', 'level_manager'], true)) {
            http_response_code(403);
            return [$title, "Acces reserve au suivi college.", null, true, false, null];
        }

        $scopeClass = null;

        if ($role === 'teacher') {
            $scopeClass = trim((string) ($user['managed_class'] ?? ''));

            if ($scopeClass === '') {
                http_response_code(403);
                return [$title, "Aucune classe n est rattachee a ce compte professeur.", null, true, false, null];
            }
        }

        return [$title, null, null, false, $canManageInternshipAdministration, $scopeClass];
    }

    private function resolveApplicationThreadContext(int $applicationId, ?array $currentUser): array
    {
        if ($currentUser === null) {
            app_redirect('/login?' . http_build_query([
                'return_to' => '/applications/' . $applicationId,
            ]));
        }

        $role = (string) ($currentUser['role'] ?? '');
        $thread = null;
        $error = null;
        $accessDenied = false;
        $canReply = false;

        if ($role === 'student') {
            $thread = $this->applications->findThreadContextForStudent($applicationId, (int) $currentUser['id']);
            $canReply = true;
        } elseif (in_array($role, ['company', 'parent'], true)) {
            $company = $this->companies->findByUserId((int) $currentUser['id']);

            if ($company === null) {
                http_response_code(403);
                return [null, "Aucun profil entreprise n'est rattache a ce compte.", true, false];
            }

            $thread = $this->applications->findThreadContextForCompany($applicationId, (int) $company['id']);
            $canReply = true;
        } elseif ($role === 'teacher') {
            $managedClass = trim((string) ($currentUser['managed_class'] ?? ''));
            $thread = $this->applications->findThreadContextForStaff($applicationId, $managedClass === '' ? null : $managedClass);
        } elseif (in_array($role, ['level_manager', 'admin'], true)) {
            $thread = $this->applications->findThreadContextForStaff($applicationId, null);
        } else {
            http_response_code(403);
            return [null, 'Acces refuse a cette discussion.', true, false];
        }

        if ($thread === null) {
            http_response_code(404);
            $error = 'Discussion introuvable.';
            $accessDenied = true;
        }

        return [$thread, $error, $accessDenied, $canReply];
    }

    private function composeThreadMessages(array $thread, array $storedMessages): array
    {
        $initialBody = trim((string) ($thread['message'] ?? ''));

        if ($initialBody === '') {
            return $storedMessages;
        }

        if (
            $storedMessages !== []
            && (string) ($storedMessages[0]['sender_role'] ?? '') === 'student'
            && trim((string) ($storedMessages[0]['body'] ?? '')) === $initialBody
        ) {
            return $storedMessages;
        }

        array_unshift($storedMessages, [
            'sender_label' => 'Eleve',
            'sender_role' => 'student',
            'body' => $initialBody,
            'created_at' => $thread['created_at'] ?? date('Y-m-d H:i:s'),
        ]);

        return $storedMessages;
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

    public static function validationStatusLabels(): array
    {
        return self::VALIDATION_STATUS_LABELS;
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

    private function decorateStudentRows(array $rows): array
    {
        foreach ($rows as &$row) {
            $row['student_label'] = $this->formatStudentLabel($row);
        }
        unset($row);

        return $rows;
    }

    private function formatStudentLabel(array $row): string
    {
        $fullName = trim((string) (($row['student_first_name'] ?? '') . ' ' . ($row['student_last_name'] ?? '')));

        if ($fullName !== '') {
            return $fullName;
        }

        $pseudonym = trim((string) ($row['student_pseudonym'] ?? ''));

        return $pseudonym !== '' ? $pseudonym : 'eleve non renseigne';
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
