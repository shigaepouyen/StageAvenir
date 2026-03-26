<?php

declare(strict_types=1);

$currentSiret = (string) ($company['siret'] ?? '');
$currentName = (string) ($company['name'] ?? '');
$currentAddress = (string) ($company['address'] ?? '');
$currentNafCode = (string) ($company['naf_code'] ?? '');
$currentLat = (string) ($company['lat'] ?? '');
$currentLng = (string) ($company['lng'] ?? '');
$currentValidationStatus = (string) ($company['validation_status'] ?? 'pending');
$searchQuery = (string) ($searchQuery ?? '');
$searchResults = is_array($searchResults ?? null) ? $searchResults : [];
$validationLabel = \App\Controllers\InternshipController::validationStatusLabels()[$currentValidationStatus] ?? $currentValidationStatus;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Profil entreprise', ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_path('app.css'), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="page-company">
    <main class="page-shell">
        <nav class="top-nav surface">
            <div class="nav-cluster">
                <a class="nav-brand" href="<?= htmlspecialchars(app_path('/'), ENT_QUOTES, 'UTF-8'); ?>">Avenir Pro</a>
                <div class="nav-links">
                    <a class="nav-link" href="<?= htmlspecialchars(app_path('/'), ENT_QUOTES, 'UTF-8'); ?>">Tableau de bord</a>
                    <a class="nav-link nav-link-current" href="<?= htmlspecialchars(app_path('/company-profile'), ENT_QUOTES, 'UTF-8'); ?>">Mon entreprise</a>
                    <a class="nav-link" href="<?= htmlspecialchars(app_path('/internships'), ENT_QUOTES, 'UTF-8'); ?>">Mes offres</a>
                    <a class="nav-link" href="<?= htmlspecialchars(app_path('/company-applications'), ENT_QUOTES, 'UTF-8'); ?>">Candidatures</a>
                    <a class="nav-link" href="<?= htmlspecialchars(app_path('/help'), ENT_QUOTES, 'UTF-8'); ?>">Aide</a>
                    <a class="nav-link" href="<?= htmlspecialchars(app_path('/news'), ENT_QUOTES, 'UTF-8'); ?>">Mes news</a>
                </div>
            </div>
            <div class="nav-actions">
                <form class="inline-form" method="post" action="<?= htmlspecialchars(app_path('/logout'), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="button-secondary">Me deconnecter</button>
                </form>
            </div>
        </nav>

        <section class="hero hero-split" style="margin-top: 1rem;">
            <div class="hero-copy">
                <p class="eyebrow">Etape 1</p>
                <h1 class="hero-title"><?= htmlspecialchars($title ?? 'Profil entreprise', ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="hero-text">Commencez ici. Renseignez le SIRET, verifiez les informations de l'entreprise, puis envoyez le profil pour validation.</p>
                <div class="step-chip-row">
                    <span class="step-chip">1. Mon entreprise</span>
                    <span class="step-chip">2. Mes offres</span>
                    <span class="step-chip">3. Mes candidatures</span>
                </div>
            </div>
            <aside class="hero-panel">
                <p class="eyebrow">Etat actuel</p>
                <p class="section-title">Validation entreprise</p>
                <p class="section-copy"><?= htmlspecialchars($validationLabel, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php if (!empty($company) && $currentValidationStatus !== 'approved'): ?>
                    <p class="section-copy">Tant que l'entreprise n'est pas validee, les offres ne peuvent pas etre visibles cote eleve.</p>
                <?php endif; ?>
            </aside>
        </section>

        <?php if (!empty($error)): ?>
            <p class="message message-error" style="margin-top: 1rem;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <p class="message message-success" style="margin-top: 1rem;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if (empty($accessDenied)): ?>
            <section class="surface" style="margin-top: 1.4rem;">
                <h2>1. Retrouver votre entreprise</h2>
                <p class="section-copy">Recherchez votre structure par nom ou par SIRET pour pre-remplir automatiquement les informations principales.</p>
                <form method="post" action="<?= htmlspecialchars(app_path('/company-profile/search'), ENT_QUOTES, 'UTF-8'); ?>">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>"
                    >
                    <div class="field-grid">
                        <div class="field-group field-span-2">
                            <label for="search_query">Nom de l'entreprise ou SIRET</label>
                            <input
                                id="search_query"
                                name="search_query"
                                type="text"
                                required
                                value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?>"
                            >
                        </div>
                    </div>
                    <div class="inline-actions">
                        <button type="submit">Rechercher</button>
                    </div>
                </form>
            </section>

            <?php if ($searchResults !== []): ?>
                <section class="results-grid" style="margin-top: 1.5rem;">
                    <h2>Resultats proposes</h2>
                    <?php foreach ($searchResults as $result): ?>
                        <article class="offer-card">
                            <p><strong><?= htmlspecialchars((string) ($result['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></p>
                            <p>SIRET : <?= htmlspecialchars((string) ($result['siret'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            <p>Adresse : <?= htmlspecialchars((string) ($result['address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            <p>Code NAF : <?= htmlspecialchars((string) ($result['naf_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            <form method="post" action="<?= htmlspecialchars(app_path('/company-profile/select'), ENT_QUOTES, 'UTF-8'); ?>">
                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>"
                                >
                                <input type="hidden" name="search_query" value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="selected_siret" value="<?= htmlspecialchars((string) ($result['siret'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="inline-actions">
                                    <button type="submit">Selectionner</button>
                                </div>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>

            <section class="surface" style="margin-top: 1.5rem;">
                <h2>2. Verifier les informations</h2>
                <?php if (!empty($company)): ?>
                    <p class="section-copy"><strong>Validation entreprise :</strong> <?= htmlspecialchars($validationLabel, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php if ($currentValidationStatus !== 'approved'): ?>
                        <p class="section-copy">Une fois ce formulaire envoye, l'administration doit valider l'entreprise avant publication des offres.</p>
                    <?php endif; ?>
                <?php endif; ?>
                <form method="post" action="<?= htmlspecialchars(app_path('/company-profile'), ENT_QUOTES, 'UTF-8'); ?>">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>"
                    >
                    <div class="field-grid">
                        <div class="field-group">
                            <label for="siret">SIRET</label>
                            <input
                                id="siret"
                                name="siret"
                                type="text"
                                inputmode="numeric"
                                pattern="\d{14}"
                                maxlength="14"
                                required
                                value="<?= htmlspecialchars($currentSiret, ENT_QUOTES, 'UTF-8'); ?>"
                            >
                        </div>

                        <div class="field-group">
                            <label for="name">Raison sociale</label>
                            <input
                                id="name"
                                name="name"
                                type="text"
                                value="<?= htmlspecialchars($currentName, ENT_QUOTES, 'UTF-8'); ?>"
                            >
                        </div>

                        <div class="field-group field-span-2">
                            <label for="address">Adresse</label>
                            <input
                                id="address"
                                name="address"
                                type="text"
                                value="<?= htmlspecialchars($currentAddress, ENT_QUOTES, 'UTF-8'); ?>"
                            >
                        </div>

                        <div class="field-group">
                            <label for="naf_code">Code NAF</label>
                            <input
                                id="naf_code"
                                name="naf_code"
                                type="text"
                                value="<?= htmlspecialchars($currentNafCode, ENT_QUOTES, 'UTF-8'); ?>"
                            >
                        </div>

                        <div class="field-group">
                            <label for="lat">Latitude</label>
                            <input
                                id="lat"
                                name="lat"
                                type="text"
                                value="<?= htmlspecialchars($currentLat, ENT_QUOTES, 'UTF-8'); ?>"
                            >
                        </div>

                        <div class="field-group">
                            <label for="lng">Longitude</label>
                            <input
                                id="lng"
                                name="lng"
                                type="text"
                                value="<?= htmlspecialchars($currentLng, ENT_QUOTES, 'UTF-8'); ?>"
                            >
                        </div>
                    </div>

                    <input type="hidden" name="search_query" value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="inline-actions">
                        <button type="submit">Enregistrer et demander la validation</button>
                        <a class="button-ghost" href="<?= htmlspecialchars(app_path('/help'), ENT_QUOTES, 'UTF-8'); ?>">Voir la FAQ entreprise</a>
                    </div>
                </form>
                <p class="form-help">Format attendu : 14 chiffres, sans espaces.</p>
            </section>

            <section class="support-banner" style="margin-top: 1.5rem;">
                <h2>Avant de passer a l'etape suivante</h2>
                <p>Quand l'entreprise est validee, vous pouvez deposer vos offres depuis “Mes offres”. Si vous avez un doute sur le SIRET ou la validation, la FAQ entreprise repond aux questions les plus courantes.</p>
                <div class="inline-actions">
                    <a class="button-secondary" href="<?= htmlspecialchars(app_path('/internships'), ENT_QUOTES, 'UTF-8'); ?>">Aller vers mes offres</a>
                    <a class="button-ghost" href="<?= htmlspecialchars(app_path('/help'), ENT_QUOTES, 'UTF-8'); ?>">Ouvrir l'aide entreprise</a>
                </div>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
