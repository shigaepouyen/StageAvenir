<?php

declare(strict_types=1);

$availableStatuses = is_array($availableStatuses ?? null) ? $availableStatuses : [];
$availableInternships = is_array($availableInternships ?? null) ? $availableInternships : [];
$selectedStatus = (string) ($selectedStatus ?? '');
$selectedInternshipId = (string) ($selectedInternshipId ?? '');
$newApplicationsCount = (int) ($newApplicationsCount ?? 0);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Candidatures recues', ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_path('app.css'), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="page-company">
    <main class="page-shell">
        <nav class="top-nav surface">
            <div class="nav-links">
                <a class="nav-link" href="<?= htmlspecialchars(app_path('/'), ENT_QUOTES, 'UTF-8'); ?>">Accueil</a>
                <a class="nav-link" href="<?= htmlspecialchars(app_path('/company-profile'), ENT_QUOTES, 'UTF-8'); ?>">Profil entreprise</a>
                <a class="nav-link" href="<?= htmlspecialchars(app_path('/internships'), ENT_QUOTES, 'UTF-8'); ?>">Mes offres</a>
                <a class="nav-link nav-link-current" href="<?= htmlspecialchars(app_path('/company-applications'), ENT_QUOTES, 'UTF-8'); ?>">
                    Candidatures
                    <?php if ($newApplicationsCount > 0): ?>
                        <span class="count-badge"><?= htmlspecialchars((string) $newApplicationsCount, ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                </a>
            </div>
            <form class="inline-form" method="post" action="<?= htmlspecialchars(app_path('/logout'), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="button-secondary">Me deconnecter</button>
            </form>
        </nav>

        <section class="hero hero-split" style="margin-top: 1rem;">
            <div class="hero-copy">
                <p class="eyebrow">Suivi entreprise</p>
                <h1 class="hero-title"><?= htmlspecialchars($title ?? 'Candidatures recues', ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="hero-text">Retrouve ici toutes les candidatures envoyees sur tes offres, filtre-les et mets a jour leur statut au fil des echanges.</p>
            </div>
            <aside class="hero-panel">
                <p class="eyebrow">Nouvelles candidatures</p>
                <p class="hero-title" style="font-size: clamp(2rem, 4vw, 3.4rem);"><?= htmlspecialchars((string) $newApplicationsCount, ENT_QUOTES, 'UTF-8'); ?></p>
                <p class="section-copy">Les candidatures en statut <strong>Nouvelle</strong> meritent en principe une premiere lecture ou un premier contact.</p>
            </aside>
        </section>

        <?php if (!empty($error)): ?>
            <p class="message message-error" style="margin-top: 1rem;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <p class="message message-success" style="margin-top: 1rem;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if (empty($accessDenied ?? false)): ?>
            <section class="surface" style="margin-top: 1.4rem;">
                <form method="get" action="<?= htmlspecialchars(app_path('/company-applications'), ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="field-grid">
                        <div class="field-group">
                            <label for="internship_id">Filtrer par offre</label>
                            <select id="internship_id" name="internship_id">
                                <option value="">Toutes mes offres</option>
                                <?php foreach ($availableInternships as $internship): ?>
                                    <option
                                        value="<?= htmlspecialchars((string) $internship['id'], ENT_QUOTES, 'UTF-8'); ?>"
                                        <?= $selectedInternshipId === (string) $internship['id'] ? 'selected' : ''; ?>
                                    >
                                        <?= htmlspecialchars((string) $internship['title'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field-group">
                            <label for="status_filter">Filtrer par statut</label>
                            <select id="status_filter" name="status_filter">
                                <option value="">Tous les statuts</option>
                                <?php foreach ($availableStatuses as $statusValue => $statusLabel): ?>
                                    <option
                                        value="<?= htmlspecialchars($statusValue, ENT_QUOTES, 'UTF-8'); ?>"
                                        <?= $selectedStatus === $statusValue ? 'selected' : ''; ?>
                                    >
                                        <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="inline-actions">
                        <button type="submit">Appliquer les filtres</button>
                        <a class="button-secondary" href="<?= htmlspecialchars(app_path('/company-applications'), ENT_QUOTES, 'UTF-8'); ?>">Reinitialiser</a>
                    </div>
                </form>
            </section>

            <?php if ($items === []): ?>
                <div class="empty-state" style="margin-top: 1.5rem;">
                    Aucune candidature ne correspond a ces filtres pour le moment.
                </div>
            <?php else: ?>
                <section class="results-grid" style="margin-top: 1.5rem;">
                    <?php foreach ($items as $item): ?>
                        <?php
                        $statusValue = (string) ($item['status'] ?? 'new');
                        $statusLabel = $availableStatuses[$statusValue] ?? $statusValue;
                        $studentLabel = (string) ($item['student_email'] ?? $item['student_pseudonym'] ?? 'eleve non renseigne');
                        $isAnonymized = (string) ($item['anonymized_at'] ?? '') !== '';
                        ?>
                        <article class="offer-card">
                            <div class="offer-card-top">
                                <span class="stat-badge status-badge status-badge-<?= htmlspecialchars($statusValue, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                                <span class="stat-badge stat-badge-soft">
                                    <?= htmlspecialchars(date('d/m/Y', strtotime((string) $item['created_at'])), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </div>
                            <h3><?= htmlspecialchars((string) $item['internship_title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <ul class="offer-meta">
                                <li><strong>Eleve :</strong> <?= htmlspecialchars($studentLabel, ENT_QUOTES, 'UTF-8'); ?></li>
                                <li><strong>Classe :</strong> <?= htmlspecialchars((string) $item['classe'], ENT_QUOTES, 'UTF-8'); ?></li>
                                <li><strong>Annee scolaire :</strong> <?= htmlspecialchars((string) $item['academic_year'], ENT_QUOTES, 'UTF-8'); ?></li>
                                <?php if ($isAnonymized): ?>
                                    <li><strong>RGPD :</strong> candidature anonymisee apres la campagne</li>
                                <?php endif; ?>
                            </ul>
                            <p class="offer-description"><?= nl2br(htmlspecialchars((string) $item['message'], ENT_QUOTES, 'UTF-8')); ?></p>

                            <form method="post" action="<?= htmlspecialchars(app_path('/company-applications/' . (string) $item['id'] . '/status'), ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="internship_id" value="<?= htmlspecialchars($selectedInternshipId, ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="status_filter" value="<?= htmlspecialchars($selectedStatus, ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="field-group">
                                    <label for="status-<?= htmlspecialchars((string) $item['id'], ENT_QUOTES, 'UTF-8'); ?>">Mettre a jour le statut</label>
                                    <select id="status-<?= htmlspecialchars((string) $item['id'], ENT_QUOTES, 'UTF-8'); ?>" name="new_status">
                                        <?php foreach ($availableStatuses as $optionValue => $optionLabel): ?>
                                            <option
                                                value="<?= htmlspecialchars($optionValue, ENT_QUOTES, 'UTF-8'); ?>"
                                                <?= $statusValue === $optionValue ? 'selected' : ''; ?>
                                            >
                                                <?= htmlspecialchars($optionLabel, ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="inline-actions">
                                    <button type="submit">Enregistrer</button>
                                    <a class="button-secondary" href="<?= htmlspecialchars(app_path('/offers/' . (string) $item['internship_id']), ENT_QUOTES, 'UTF-8'); ?>">Voir l'offre</a>
                                </div>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</body>
</html>
