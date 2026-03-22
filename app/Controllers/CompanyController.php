<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\CompanyRepository;
use App\Services\SireneApiClient;
use App\Support\SessionManager;
use PDO;
use RuntimeException;

final class CompanyController
{
    private CompanyRepository $companies;
    private SireneApiClient $sirene;

    public function __construct(PDO $pdo, array $sireneConfig)
    {
        $this->companies = new CompanyRepository($pdo);
        $this->sirene = new SireneApiClient($sireneConfig);
    }

    public function showForm(): void
    {
        [$user, $title, $accessDenied, $error, $success] = $this->guard();

        if ($accessDenied) {
            $company = null;
            $searchQuery = '';
            $searchResults = [];
            require __DIR__ . '/../Views/company_profile.php';
            return;
        }

        $company = $this->companies->findByUserId((int) $user['id']);
        $searchQuery = '';
        $searchResults = [];
        if (($status = ($_GET['status'] ?? null)) === 'saved') {
            $success = 'Profil entreprise enregistre.';
        }

        require __DIR__ . '/../Views/company_profile.php';
    }

    public function save(): void
    {
        [$user, $title, $accessDenied, $error, $success] = $this->guard();

        if ($accessDenied) {
            $company = null;
            $searchQuery = '';
            $searchResults = [];
            require __DIR__ . '/../Views/company_profile.php';
            return;
        }

        $existingUserCompany = $this->companies->findByUserId((int) $user['id']);
        $profile = $this->normalizeProfilePayload($_POST);
        $validationError = $this->validateProfile($profile, (int) $user['id']);

        if ($validationError !== null) {
            http_response_code(422);
            $error = $validationError;
            $company = $existingUserCompany === null ? $profile : array_merge($existingUserCompany, $profile);
            $searchQuery = trim((string) ($_POST['search_query'] ?? ''));
            $searchResults = [];
            require __DIR__ . '/../Views/company_profile.php';
            return;
        }

        if ($existingUserCompany === null) {
            $this->companies->createProfile((int) $user['id'], $profile);
        } else {
            $this->companies->updateProfileByUserId((int) $user['id'], $profile);
        }

        app_redirect('/company-profile?status=saved');
    }

    public function search(): void
    {
        [$user, $title, $accessDenied, $error, $success] = $this->guard();

        if ($accessDenied) {
            $company = null;
            $searchQuery = '';
            $searchResults = [];
            require __DIR__ . '/../Views/company_profile.php';
            return;
        }

        $company = $this->companies->findByUserId((int) $user['id']);
        $searchQuery = trim((string) ($_POST['search_query'] ?? ''));
        $searchResults = [];

        if ($searchQuery === '') {
            http_response_code(422);
            $error = "Saisissez un nom d'entreprise ou un SIRET avant de rechercher.";
            require __DIR__ . '/../Views/company_profile.php';
            return;
        }

        try {
            $searchResults = $this->sirene->search($searchQuery);
        } catch (RuntimeException $exception) {
            http_response_code(502);
            $error = "Erreur API Sirene : " . $exception->getMessage();
            require __DIR__ . '/../Views/company_profile.php';
            return;
        }

        if ($searchResults === []) {
            $success = null;
            $error = "Aucun resultat n'a ete trouve.";
        }

        require __DIR__ . '/../Views/company_profile.php';
    }

    public function selectSearchResult(): void
    {
        [$user, $title, $accessDenied, $error, $success] = $this->guard();

        if ($accessDenied) {
            $company = null;
            $searchQuery = '';
            $searchResults = [];
            require __DIR__ . '/../Views/company_profile.php';
            return;
        }

        $searchQuery = trim((string) ($_POST['search_query'] ?? ''));
        $selectedSiret = trim((string) ($_POST['selected_siret'] ?? ''));
        $searchResults = [];

        if (!preg_match('/^\d{14}$/', $selectedSiret)) {
            http_response_code(422);
            $error = 'Le SIRET selectionne est invalide.';
            $company = $this->companies->findByUserId((int) $user['id']);
            require __DIR__ . '/../Views/company_profile.php';
            return;
        }

        try {
            $profile = $this->sirene->findBySiret($selectedSiret);
        } catch (RuntimeException $exception) {
            http_response_code(502);
            $error = "Erreur API Sirene : " . $exception->getMessage();
            $company = $this->companies->findByUserId((int) $user['id']);
            require __DIR__ . '/../Views/company_profile.php';
            return;
        }

        if ($profile === null) {
            http_response_code(404);
            $error = "Aucune fiche Sirene exploitable n'a ete trouvee pour ce SIRET.";
            $company = $this->companies->findByUserId((int) $user['id']);
            require __DIR__ . '/../Views/company_profile.php';
            return;
        }

        $validationError = $this->validateProfile($profile, (int) $user['id']);

        if ($validationError !== null) {
            http_response_code(422);
            $error = $validationError;
            $company = $this->companies->findByUserId((int) $user['id']);
            require __DIR__ . '/../Views/company_profile.php';
            return;
        }

        $existingUserCompany = $this->companies->findByUserId((int) $user['id']);

        if ($existingUserCompany === null) {
            $this->companies->createProfile((int) $user['id'], $profile);
        } else {
            $this->companies->updateProfileByUserId((int) $user['id'], $profile);
        }

        app_redirect('/company-profile?status=saved');
    }

    private function canManageCompanyProfile(string $role): bool
    {
        return in_array($role, ['parent', 'company', 'admin'], true);
    }

    private function guard(): array
    {
        $user = SessionManager::currentUser();

        if ($user === null) {
            app_redirect('/login');
        }

        $title = 'Profil entreprise';

        if (!$this->canManageCompanyProfile($user['role'])) {
            http_response_code(403);
            return [$user, $title, true, "Acces refuse a la gestion du profil entreprise.", null];
        }

        return [$user, $title, false, null, null];
    }

    private function normalizeProfilePayload(array $input): array
    {
        return [
            'siret' => trim((string) ($input['siret'] ?? '')),
            'name' => $this->nullableString($input['name'] ?? null),
            'naf_code' => $this->nullableString($input['naf_code'] ?? null),
            'address' => $this->nullableString($input['address'] ?? null),
            'lat' => $this->nullableDecimal($input['lat'] ?? null),
            'lng' => $this->nullableDecimal($input['lng'] ?? null),
        ];
    }

    private function validateProfile(array $profile, int $currentUserId): ?string
    {
        if (!preg_match('/^\d{14}$/', (string) $profile['siret'])) {
            return 'Le SIRET doit contenir exactement 14 chiffres.';
        }

        $existingCompany = $this->companies->findBySiret((string) $profile['siret']);

        if ($existingCompany !== null && (int) $existingCompany['user_id'] !== $currentUserId) {
            return 'Ce SIRET est deja utilise par une autre entreprise.';
        }

        if ($profile['lat'] !== null && !is_numeric((string) $profile['lat'])) {
            return 'La latitude est invalide.';
        }

        if ($profile['lng'] !== null && !is_numeric((string) $profile['lng'])) {
            return 'La longitude est invalide.';
        }

        return null;
    }

    private function nullableString(mixed $value): ?string
    {
        $stringValue = trim((string) $value);
        return $stringValue === '' ? null : $stringValue;
    }

    private function nullableDecimal(mixed $value): ?string
    {
        $stringValue = trim((string) $value);

        if ($stringValue === '') {
            return null;
        }

        return is_numeric($stringValue) ? $stringValue : $stringValue;
    }
}
