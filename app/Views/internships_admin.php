<?php

declare(strict_types=1);

$companiesItems = is_array($companiesItems ?? null) ? $companiesItems : [];
$moderationItems = is_array($moderationItems ?? null) ? $moderationItems : [];
$archivedItems = is_array($archivedItems ?? null) ? $archivedItems : [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Moderation entreprises et offres', ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_path('app.css'), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="page-admin">
    <main class="page-shell">
        <nav class="top-nav surface">
            <div class="nav-cluster">
                <a class="nav-brand" href="<?= htmlspecialchars(app_path('/'), ENT_QUOTES, 'UTF-8'); ?>">Avenir Pro</a>
                <div class="nav-links">
                    <a class="nav-link" href="<?= htmlspecialchars(app_path('/'), ENT_QUOTES, 'UTF-8'); ?>">Tableau de bord</a>
                    <a class="nav-link" href="<?= htmlspecialchars(app_path('/admin/dashboard'), ENT_QUOTES, 'UTF-8'); ?>">Suivi college</a>
                    <a class="nav-link" href="<?= htmlspecialchars(app_path('/admin/staff'), ENT_QUOTES, 'UTF-8'); ?>">Comptes staff</a>
                    <a class="nav-link nav-link-current" href="<?= htmlspecialchars(app_path('/admin/internships'), ENT_QUOTES, 'UTF-8'); ?>">Moderation</a>
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
                <p class="eyebrow">Moderation</p>
                <h1 class="hero-title"><?= htmlspecialchars($title ?? 'Moderation entreprises et offres', ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="hero-text">Valide ou refuse les profils entreprises et les offres avant publication pour maintenir un cadre securise pour les mineurs.</p>
                <div class="step-chip-row">
                    <span class="step-chip">1. Entreprise</span>
                    <span class="step-chip">2. Offre</span>
                    <span class="step-chip">3. Publication</span>
                </div>
            </div>
            <aside class="hero-panel">
                <p class="eyebrow">Flux</p>
                <p class="section-copy">L'ordre normal est : validation de l'entreprise, puis validation de l'offre. Une offre refusee ou archivee ne remonte plus cote eleve.</p>
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
                <h2>Entreprises a moderer</h2>
                <?php if ($companiesItems === []): ?>
                    <div class="empty-state">Aucune entreprise a moderer.</div>
                <?php else: ?>
                    <div class="results-grid">
                    <?php foreach ($companiesItems as $item): ?>
                        <?php
                        $companyValidationStatus = (string) ($item['validation_status'] ?? 'pending');
                        $companyApproveLabel = $companyValidationStatus === 'rejected'
                            ? 'Revalider l\'entreprise'
                            : 'Valider l\'entreprise';
                        ?>
                        <article class="offer-card">
                            <h3><?= htmlspecialchars((string) ($item['name'] ?? $item['siret'] ?? 'Entreprise'), ENT_QUOTES, 'UTF-8'); ?></h3>
                            <ul class="offer-meta">
                                <li><strong>Email responsable :</strong> <?= htmlspecialchars((string) ($item['owner_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></li>
                                <li><strong>SIRET :</strong> <?= htmlspecialchars((string) ($item['siret'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></li>
                                <li><strong>Code NAF :</strong> <?= htmlspecialchars((string) ($item['naf_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></li>
                                <li><strong>Adresse :</strong> <?= htmlspecialchars((string) ($item['address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></li>
                                <li><strong>Validation :</strong> <?= htmlspecialchars(\App\Controllers\InternshipController::validationStatusLabels()[(string) ($item['validation_status'] ?? 'pending')] ?? (string) ($item['validation_status'] ?? 'pending'), ENT_QUOTES, 'UTF-8'); ?></li>
                            </ul>
                            <div class="inline-actions">
                                <?php if ($companyValidationStatus !== 'approved'): ?>
                                    <form method="post" action="<?= htmlspecialchars(app_path('/admin/companies/' . (string) $item['id'] . '/approve'), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit"><?= htmlspecialchars($companyApproveLabel, ENT_QUOTES, 'UTF-8'); ?></button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($companyValidationStatus !== 'rejected'): ?>
                                    <form method="post" action="<?= htmlspecialchars(app_path('/admin/companies/' . (string) $item['id'] . '/reject'), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit">Refuser l'entreprise</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="surface" style="margin-top: 1.4rem;">
                <h2>Offres a moderer</h2>
                <?php if ($moderationItems === []): ?>
                    <div class="empty-state">Aucune offre a moderer.</div>
                <?php else: ?>
                    <div class="results-grid">
                    <?php foreach ($moderationItems as $item): ?>
                        <?php
                        $internshipValidationStatus = (string) ($item['validation_status'] ?? 'pending');
                        $internshipApproveLabel = $internshipValidationStatus === 'rejected'
                            ? 'Revalider l\'offre'
                            : 'Valider l\'offre';
                        ?>
                        <article class="offer-card">
                            <h3><?= htmlspecialchars((string) $item['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <ul class="offer-meta">
                                <li><strong>Entreprise :</strong> <?= htmlspecialchars((string) ($item['company_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></li>
                                <li><strong>Statut offre :</strong> <?= htmlspecialchars((string) ($item['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></li>
                                <li><strong>Validation offre :</strong> <?= htmlspecialchars(\App\Controllers\InternshipController::validationStatusLabels()[(string) ($item['validation_status'] ?? 'pending')] ?? (string) ($item['validation_status'] ?? 'pending'), ENT_QUOTES, 'UTF-8'); ?></li>
                                <li><strong>Validation entreprise :</strong> <?= htmlspecialchars(\App\Controllers\InternshipController::validationStatusLabels()[(string) ($item['company_validation_status'] ?? 'pending')] ?? (string) ($item['company_validation_status'] ?? 'pending'), ENT_QUOTES, 'UTF-8'); ?></li>
                            </ul>
                            <p class="offer-description"><?= nl2br(htmlspecialchars((string) ($item['description'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></p>
                            <div class="inline-actions">
                                <?php if ($internshipValidationStatus !== 'approved'): ?>
                                    <form method="post" action="<?= htmlspecialchars(app_path('/admin/internships/' . (string) $item['id'] . '/approve'), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit"><?= htmlspecialchars($internshipApproveLabel, ENT_QUOTES, 'UTF-8'); ?></button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($internshipValidationStatus !== 'rejected'): ?>
                                    <form method="post" action="<?= htmlspecialchars(app_path('/admin/internships/' . (string) $item['id'] . '/reject'), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit">Refuser l'offre</button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" action="<?= htmlspecialchars(app_path('/admin/internships/' . (string) $item['id'] . '/archive'), ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit">Archiver</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="surface" style="margin-top: 1.4rem;">
                <h2>Offres archivees</h2>
                <?php if ($archivedItems === []): ?>
                    <div class="empty-state">Aucune offre archivee.</div>
                <?php else: ?>
                    <div class="results-grid">
                    <?php foreach ($archivedItems as $item): ?>
                        <article class="offer-card">
                            <h3><?= htmlspecialchars((string) $item['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <ul class="offer-meta">
                                <li><strong>Entreprise :</strong> <?= htmlspecialchars((string) ($item['company_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></li>
                                <li><strong>Statut :</strong> <?= htmlspecialchars((string) $item['status'], ENT_QUOTES, 'UTF-8'); ?></li>
                            </ul>
                        </article>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
