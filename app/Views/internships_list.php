<?php

declare(strict_types=1);

$newApplicationsCount = (int) ($newApplicationsCount ?? 0);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Mes offres de stage', ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_path('app.css'), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="page-company">
    <main class="page-shell">
        <nav class="top-nav surface">
            <div class="nav-cluster">
                <a class="nav-brand" href="<?= htmlspecialchars(app_path('/'), ENT_QUOTES, 'UTF-8'); ?>">Avenir Pro</a>
                <div class="nav-links">
                    <a class="nav-link" href="<?= htmlspecialchars(app_path('/'), ENT_QUOTES, 'UTF-8'); ?>">Tableau de bord</a>
                    <a class="nav-link" href="<?= htmlspecialchars(app_path('/company-profile'), ENT_QUOTES, 'UTF-8'); ?>">Mon entreprise</a>
                    <a class="nav-link nav-link-current" href="<?= htmlspecialchars(app_path('/internships'), ENT_QUOTES, 'UTF-8'); ?>">Mes offres</a>
                    <a class="nav-link" href="<?= htmlspecialchars(app_path('/company-applications'), ENT_QUOTES, 'UTF-8'); ?>">
                        Candidatures
                        <?php if ($newApplicationsCount > 0): ?>
                            <span class="count-badge"><?= htmlspecialchars((string) $newApplicationsCount, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                    </a>
                    <a class="nav-link" href="<?= htmlspecialchars(app_path('/news'), ENT_QUOTES, 'UTF-8'); ?>">Mes news</a>
                    <a class="nav-link" href="<?= htmlspecialchars(app_path('/help'), ENT_QUOTES, 'UTF-8'); ?>">Aide</a>
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
                <p class="eyebrow">Etape 2</p>
                <h1 class="hero-title"><?= htmlspecialchars($title ?? 'Mes offres de stage', ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="hero-text">Retrouvez ici vos offres, leur etat de validation et ce qu'il vous reste a faire pour les rendre visibles aux eleves.</p>
                <div class="step-chip-row">
                    <span class="step-chip">Entreprise validee</span>
                    <span class="step-chip">Offre relue</span>
                    <span class="step-chip">Visible aux eleves</span>
                </div>
            </div>
            <aside class="hero-panel">
                <p class="eyebrow">Parcours</p>
                <div class="role-actions">
                    <a class="role-link" href="<?= htmlspecialchars(app_path('/company-profile'), ENT_QUOTES, 'UTF-8'); ?>">1. Mon entreprise</a>
                    <a class="role-link" href="<?= htmlspecialchars(app_path('/internships/create'), ENT_QUOTES, 'UTF-8'); ?>">2. Ajouter une offre</a>
                    <a class="role-link" href="<?= htmlspecialchars(app_path('/company-applications'), ENT_QUOTES, 'UTF-8'); ?>">
                        3. Suivre les candidatures
                        <?php if ($newApplicationsCount > 0): ?>
                            <span class="count-badge"><?= htmlspecialchars((string) $newApplicationsCount, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </aside>
        </section>

        <?php if (!empty($error)): ?>
            <p class="message message-error" style="margin-top: 1rem;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <p class="message message-success" style="margin-top: 1rem;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if (!empty($company) && empty($accessDenied)): ?>
            <section class="surface" style="margin-top: 1.4rem;">
                <p class="section-copy"><strong>Validation entreprise :</strong> <?= htmlspecialchars(\App\Controllers\InternshipController::validationStatusLabels()[(string) ($company['validation_status'] ?? 'pending')] ?? (string) ($company['validation_status'] ?? 'pending'), ENT_QUOTES, 'UTF-8'); ?></p>
            </section>

            <?php if ($items === []): ?>
                <div class="empty-state" style="margin-top: 1.5rem;">Aucune offre pour le moment.</div>
            <?php else: ?>
                <section class="results-grid" style="margin-top: 1.5rem;">
                    <?php foreach ($items as $item): ?>
                        <?php
                        $statusValue = (string) ($item['status'] ?? '');
                        $validationValue = (string) ($item['validation_status'] ?? 'pending');
                        $displayStatus = match (true) {
                            $statusValue === 'sleeping' => 'Invisible cote eleve',
                            $statusValue === 'archived' => 'Archivee',
                            $validationValue === 'pending' => 'En attente de validation',
                            $validationValue === 'rejected' => 'A revoir apres refus',
                            default => 'Visible cote eleve',
                        };
                        ?>
                        <article class="offer-card">
                            <div class="offer-card-top">
                                <span class="stat-badge"><?= htmlspecialchars($displayStatus, ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="stat-badge stat-badge-soft">
                                    Validation : <?= htmlspecialchars(\App\Controllers\InternshipController::validationStatusLabels()[(string) ($item['validation_status'] ?? 'pending')] ?? (string) ($item['validation_status'] ?? 'pending'), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </div>
                            <h2><?= htmlspecialchars((string) $item['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                            <p class="offer-description"><?= nl2br(htmlspecialchars((string) $item['description'], ENT_QUOTES, 'UTF-8')); ?></p>
                            <ul class="offer-meta">
                                <li><strong>Secteur :</strong> <?= htmlspecialchars((string) ($item['sector_tag'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></li>
                                <li><strong>Places :</strong> <?= htmlspecialchars((string) $item['places_count'], ENT_QUOTES, 'UTF-8'); ?></li>
                                <li><strong>Annee scolaire :</strong> <?= htmlspecialchars((string) $item['academic_year'], ENT_QUOTES, 'UTF-8'); ?></li>
                            </ul>
                            <div class="inline-actions">
                                <a class="button-secondary" href="<?= htmlspecialchars(app_path('/internships/create'), ENT_QUOTES, 'UTF-8'); ?>">Ajouter une offre</a>
                                <?php if ((string) $item['status'] === 'active'): ?>
                                    <form method="post" action="<?= htmlspecialchars(app_path('/internships/' . (string) $item['id'] . '/sleep'), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>"
                                        >
                                        <button type="submit">Rendre invisible</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
        <?php else: ?>
            <section class="surface" style="margin-top: 1.4rem;">
                <p class="section-copy">Votre entreprise doit d'abord etre validee par l'administration avant de publier des offres ou d'echanger avec les eleves.</p>
                <div class="inline-actions">
                    <a class="button-secondary" href="<?= htmlspecialchars(app_path('/company-profile'), ENT_QUOTES, 'UTF-8'); ?>">Completer le profil entreprise</a>
                    <a class="button-ghost" href="<?= htmlspecialchars(app_path('/help'), ENT_QUOTES, 'UTF-8'); ?>">Voir la FAQ entreprise</a>
                </div>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
