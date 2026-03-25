<?php

declare(strict_types=1);

$availableStatuses = is_array($availableStatuses ?? null) ? $availableStatuses : [];
$availableClasses = is_array($availableClasses ?? null) ? $availableClasses : [];
$availableCompanies = is_array($availableCompanies ?? null) ? $availableCompanies : [];
$availableInternships = is_array($availableInternships ?? null) ? $availableInternships : [];
$selectedClass = (string) ($selectedClass ?? '');
$selectedStatus = (string) ($selectedStatus ?? '');
$selectedCompanyId = (string) ($selectedCompanyId ?? '');
$selectedInternshipId = (string) ($selectedInternshipId ?? '');
$selectedStudentSearch = (string) ($selectedStudentSearch ?? '');
$summary = is_array($summary ?? null) ? $summary : [];
$studentsWithoutApplications = is_array($studentsWithoutApplications ?? null) ? $studentsWithoutApplications : [];
$studentDirectory = is_array($studentDirectory ?? null) ? $studentDirectory : [];
$openOffers = is_array($openOffers ?? null) ? $openOffers : [];
$fullOffers = is_array($fullOffers ?? null) ? $fullOffers : [];
$overloadedOffers = is_array($overloadedOffers ?? null) ? $overloadedOffers : [];
$canManageInternshipAdministration = !empty($canManageInternshipAdministration);
$exportUrl = app_path('/admin/dashboard/export?' . http_build_query([
    'class_filter' => $selectedClass,
    'status_filter' => $selectedStatus,
    'company_id' => $selectedCompanyId,
    'internship_id' => $selectedInternshipId,
    'student_search' => $selectedStudentSearch,
]));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Tableau de bord college', ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_path('app.css'), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="page-admin">
    <main class="page-shell">
        <nav class="top-nav surface">
            <div class="nav-links">
                <a class="nav-link" href="<?= htmlspecialchars(app_path('/'), ENT_QUOTES, 'UTF-8'); ?>">Accueil</a>
                <a class="nav-link" href="<?= htmlspecialchars(app_path('/news'), ENT_QUOTES, 'UTF-8'); ?>">Mes news</a>
                <a class="nav-link nav-link-current" href="<?= htmlspecialchars(app_path('/admin/dashboard'), ENT_QUOTES, 'UTF-8'); ?>">Tableau college</a>
                <?php if ($canManageInternshipAdministration): ?>
                    <a class="nav-link" href="<?= htmlspecialchars(app_path('/admin/internships'), ENT_QUOTES, 'UTF-8'); ?>">Moderation</a>
                <?php endif; ?>
            </div>
            <form class="inline-form" method="post" action="<?= htmlspecialchars(app_path('/logout'), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="button-secondary">Me deconnecter</button>
            </form>
        </nav>

        <section class="hero hero-split" style="margin-top: 1rem;">
            <div class="hero-copy">
                <p class="eyebrow">Suivi referent</p>
                <h1 class="hero-title"><?= htmlspecialchars($title ?? 'Tableau de bord college', ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="hero-text">Suivi global des candidatures, des offres ouvertes et des points d'attention de la campagne de stage.</p>
            </div>
            <aside class="hero-panel">
                <p class="eyebrow">Export</p>
                <h2 class="section-title">Sortie CSV</h2>
                <p class="section-copy">Exporte la liste des candidatures selon les filtres actuellement appliques.</p>
                <div class="inline-actions">
                    <a class="button" href="<?= htmlspecialchars($exportUrl, ENT_QUOTES, 'UTF-8'); ?>">Exporter en CSV</a>
                </div>
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
                <form method="get" action="<?= htmlspecialchars(app_path('/admin/dashboard'), ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="field-grid">
                        <div class="field-group">
                            <label for="class_filter">Classe</label>
                            <select id="class_filter" name="class_filter">
                                <option value="">Toutes les classes</option>
                                <?php foreach ($availableClasses as $classValue): ?>
                                    <option value="<?= htmlspecialchars($classValue, ENT_QUOTES, 'UTF-8'); ?>" <?= $selectedClass === $classValue ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($classValue, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field-group">
                            <label for="status_filter">Statut candidature</label>
                            <select id="status_filter" name="status_filter">
                                <option value="">Tous les statuts</option>
                                <?php foreach ($availableStatuses as $statusValue => $statusLabel): ?>
                                    <option value="<?= htmlspecialchars($statusValue, ENT_QUOTES, 'UTF-8'); ?>" <?= $selectedStatus === $statusValue ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field-group">
                            <label for="company_id">Entreprise</label>
                            <select id="company_id" name="company_id">
                                <option value="">Toutes les entreprises</option>
                                <?php foreach ($availableCompanies as $companyRow): ?>
                                    <option value="<?= htmlspecialchars((string) $companyRow['id'], ENT_QUOTES, 'UTF-8'); ?>" <?= $selectedCompanyId === (string) $companyRow['id'] ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars((string) $companyRow['company_label'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field-group">
                            <label for="internship_id">Offre</label>
                            <select id="internship_id" name="internship_id">
                                <option value="">Toutes les offres</option>
                                <?php foreach ($availableInternships as $internshipRow): ?>
                                    <option value="<?= htmlspecialchars((string) $internshipRow['id'], ENT_QUOTES, 'UTF-8'); ?>" <?= $selectedInternshipId === (string) $internshipRow['id'] ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars((string) $internshipRow['title'], ENT_QUOTES, 'UTF-8'); ?> - <?= htmlspecialchars((string) $internshipRow['company_label'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field-group">
                            <label for="student_search">Recherche eleve</label>
                            <input
                                id="student_search"
                                name="student_search"
                                type="text"
                                value="<?= htmlspecialchars($selectedStudentSearch, ENT_QUOTES, 'UTF-8'); ?>"
                                placeholder="Prenom ou nom"
                            >
                        </div>
                    </div>
                    <div class="inline-actions">
                        <button type="submit">Appliquer les filtres</button>
                        <a class="button-secondary" href="<?= htmlspecialchars(app_path('/admin/dashboard'), ENT_QUOTES, 'UTF-8'); ?>">Reinitialiser</a>
                    </div>
                </form>
            </section>

            <section class="dashboard-grid" style="margin-top: 1.5rem;">
                <article class="dashboard-card">
                    <h3>Candidatures</h3>
                    <p class="hero-title" style="font-size: clamp(1.8rem, 4vw, 3rem);"><?= htmlspecialchars((string) ($summary['total'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></p>
                    <p class="section-copy">Nombre total de candidatures correspondant aux filtres actuels.</p>
                </article>
                <article class="dashboard-card">
                    <h3>Nouvelles</h3>
                    <p class="hero-title" style="font-size: clamp(1.8rem, 4vw, 3rem);"><?= htmlspecialchars((string) ($summary['new'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></p>
                    <p class="section-copy">Candidatures qui n'ont pas encore ete traitees.</p>
                </article>
                <article class="dashboard-card">
                    <h3>Eleves sans candidature</h3>
                    <p class="hero-title" style="font-size: clamp(1.8rem, 4vw, 3rem);"><?= htmlspecialchars((string) ($summary['students_without_application'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></p>
                    <p class="section-copy">Comptes eleves connus dans la plateforme sans candidature envoyee.</p>
                </article>
            </section>

            <section class="card-grid" style="margin-top: 1.5rem;">
                <article class="mini-card">
                    <h3>Contactees</h3>
                    <p class="section-copy"><?= htmlspecialchars((string) ($summary['contacted'] ?? 0), ENT_QUOTES, 'UTF-8'); ?> candidature(s)</p>
                </article>
                <article class="mini-card">
                    <h3>Acceptees</h3>
                    <p class="section-copy"><?= htmlspecialchars((string) ($summary['accepted'] ?? 0), ENT_QUOTES, 'UTF-8'); ?> candidature(s)</p>
                </article>
                <article class="mini-card">
                    <h3>Refusees</h3>
                    <p class="section-copy"><?= htmlspecialchars((string) ($summary['rejected'] ?? 0), ENT_QUOTES, 'UTF-8'); ?> candidature(s)</p>
                </article>
            </section>

            <section class="card-grid" style="margin-top: 1.5rem;">
                <article class="mini-card">
                    <h3>Offres completes</h3>
                    <p class="section-copy"><?= htmlspecialchars((string) count($fullOffers), ENT_QUOTES, 'UTF-8'); ?> offre(s) ont deja autant de candidatures acceptees que de places.</p>
                </article>
                <article class="mini-card">
                    <h3>Offres sur-sollicitees</h3>
                    <p class="section-copy"><?= htmlspecialchars((string) count($overloadedOffers), ENT_QUOTES, 'UTF-8'); ?> offre(s) ont plus de candidatures que de places proposees.</p>
                </article>
                <article class="mini-card">
                    <h3>Offres ouvertes</h3>
                    <p class="section-copy"><?= htmlspecialchars((string) count($openOffers), ENT_QUOTES, 'UTF-8'); ?> offre(s) non archivees sont encore suivies dans ce tableau.</p>
                </article>
            </section>

            <section class="detail-layout" style="margin-top: 1.5rem;">
                <article class="surface">
                    <h2 class="section-title">Alertes simples</h2>
                    <?php if ($studentsWithoutApplications === [] && $fullOffers === [] && $overloadedOffers === []): ?>
                        <div class="empty-state">Aucune alerte simple a signaler pour le moment.</div>
                    <?php else: ?>
                        <div class="faq-list">
                            <details class="faq-item" open>
                                <summary>Eleves sans candidature</summary>
                                <?php if ($studentsWithoutApplications === []): ?>
                                    <p>Aucun eleve connu dans la plateforme n'est sans candidature.</p>
                                <?php else: ?>
                                    <ul class="offer-meta">
                                        <?php foreach ($studentsWithoutApplications as $student): ?>
                                            <li>
                                                <?= htmlspecialchars((string) ($student['student_label'] ?? 'eleve non renseigne'), ENT_QUOTES, 'UTF-8'); ?>
                                                <?php if (!empty($student['school_class'])): ?>
                                                    - <?= htmlspecialchars((string) $student['school_class'], ENT_QUOTES, 'UTF-8'); ?>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </details>
                            <details class="faq-item">
                                <summary>Offres completes</summary>
                                <?php if ($fullOffers === []): ?>
                                    <p>Aucune offre complete.</p>
                                <?php else: ?>
                                    <ul class="offer-meta">
                                        <?php foreach ($fullOffers as $offer): ?>
                                            <li><?= htmlspecialchars((string) $offer['title'], ENT_QUOTES, 'UTF-8'); ?> - <?= htmlspecialchars((string) $offer['company_label'], ENT_QUOTES, 'UTF-8'); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </details>
                            <details class="faq-item">
                                <summary>Offres avec trop de candidatures</summary>
                                <?php if ($overloadedOffers === []): ?>
                                    <p>Aucune offre ne depasse actuellement son nombre de places.</p>
                                <?php else: ?>
                                    <ul class="offer-meta">
                                        <?php foreach ($overloadedOffers as $offer): ?>
                                            <li><?= htmlspecialchars((string) $offer['title'], ENT_QUOTES, 'UTF-8'); ?> - <?= htmlspecialchars((string) $offer['total_applications'], ENT_QUOTES, 'UTF-8'); ?> candidature(s) pour <?= htmlspecialchars((string) $offer['places_count'], ENT_QUOTES, 'UTF-8'); ?> place(s)</li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </details>
                        </div>
                    <?php endif; ?>
                </article>

                <aside class="detail-side">
                    <section class="surface">
                        <h2 class="section-title">Mode de lecture</h2>
                        <p class="section-copy">Les filtres s'appliquent a la liste des candidatures, a l'annuaire eleves et a l'export CSV. Les emails eleves ne sont jamais affiches dans cet espace.</p>
                    </section>
                </aside>
            </section>

            <section class="surface" style="margin-top: 1.5rem;">
                <h2 class="section-title">Annuaire eleves</h2>
                <p class="section-copy">Liste interne des eleves par classe, avec recherche par prenom ou nom. Cet annuaire reste volontairement sans adresses email.</p>
                <?php if ($studentDirectory === []): ?>
                    <div class="empty-state">Aucun eleve ne correspond aux filtres actuels.</div>
                <?php else: ?>
                    <div class="results-grid">
                        <?php foreach ($studentDirectory as $student): ?>
                            <?php
                            $studentClass = (string) ($student['school_class'] ?? '');
                            $applicationsCount = (int) ($student['applications_count'] ?? 0);
                            $lastApplicationAt = (string) ($student['last_application_at'] ?? '');
                            ?>
                            <article class="offer-card">
                                <div class="offer-card-top">
                                    <span class="stat-badge stat-badge-soft">
                                        <?= htmlspecialchars($studentClass !== '' ? $studentClass : 'Classe non renseignee', ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                    <?php if ($applicationsCount === 0): ?>
                                        <span class="stat-badge status-badge status-badge-new">Sans candidature</span>
                                    <?php endif; ?>
                                </div>
                                <h3><?= htmlspecialchars((string) ($student['student_label'] ?? 'eleve non renseigne'), ENT_QUOTES, 'UTF-8'); ?></h3>
                                <ul class="offer-meta">
                                    <li><strong>Candidatures :</strong> <?= htmlspecialchars((string) $applicationsCount, ENT_QUOTES, 'UTF-8'); ?></li>
                                    <?php if ($lastApplicationAt !== ''): ?>
                                        <li><strong>Derniere candidature :</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($lastApplicationAt)), ENT_QUOTES, 'UTF-8'); ?></li>
                                    <?php endif; ?>
                                </ul>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="surface" style="margin-top: 1.5rem;">
                <h2 class="section-title">Offres ouvertes et places disponibles</h2>
                <?php if ($openOffers === []): ?>
                    <div class="empty-state">Aucune offre non archivee ne correspond a la selection actuelle.</div>
                <?php else: ?>
                    <div class="results-grid">
                        <?php foreach ($openOffers as $offer): ?>
                            <article class="offer-card">
                                <div class="offer-card-top">
                                    <span class="stat-badge stat-badge-soft"><?= htmlspecialchars((string) $offer['status'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php if (!empty($offer['is_full'])): ?>
                                        <span class="stat-badge status-badge status-badge-rejected">Complete</span>
                                    <?php endif; ?>
                                    <?php if (!empty($offer['is_overloaded'])): ?>
                                        <span class="stat-badge status-badge status-badge-new">Sur-sollicitee</span>
                                    <?php endif; ?>
                                </div>
                                <h3><?= htmlspecialchars((string) $offer['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <ul class="offer-meta">
                                    <li><strong>Entreprise :</strong> <?= htmlspecialchars((string) $offer['company_label'], ENT_QUOTES, 'UTF-8'); ?></li>
                                    <li><strong>Places :</strong> <?= htmlspecialchars((string) $offer['places_count'], ENT_QUOTES, 'UTF-8'); ?></li>
                                    <li><strong>Candidatures :</strong> <?= htmlspecialchars((string) $offer['total_applications'], ENT_QUOTES, 'UTF-8'); ?></li>
                                    <li><strong>Acceptees :</strong> <?= htmlspecialchars((string) $offer['accepted_applications'], ENT_QUOTES, 'UTF-8'); ?></li>
                                    <li><strong>Places restantes :</strong> <?= htmlspecialchars((string) $offer['remaining_places'], ENT_QUOTES, 'UTF-8'); ?></li>
                                </ul>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="surface" style="margin-top: 1.5rem;">
                <h2 class="section-title">Candidatures detaillees</h2>
                <?php if ($items === []): ?>
                    <div class="empty-state">Aucune candidature ne correspond aux filtres actuels.</div>
                <?php else: ?>
                    <div class="results-grid">
                        <?php foreach ($items as $item): ?>
                            <?php
                            $studentLabel = (string) ($item['student_label'] ?? 'eleve non renseigne');
                            $statusValue = (string) ($item['status'] ?? 'new');
                            $statusLabel = $availableStatuses[$statusValue] ?? $statusValue;
                            $isAnonymized = (string) ($item['anonymized_at'] ?? '') !== '';
                            ?>
                            <article class="offer-card">
                                <div class="offer-card-top">
                                    <span class="stat-badge status-badge status-badge-<?= htmlspecialchars($statusValue, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="stat-badge stat-badge-soft"><?= htmlspecialchars(date('d/m/Y', strtotime((string) $item['created_at'])), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php if ($isAnonymized): ?>
                                        <span class="stat-badge stat-badge-soft">RGPD</span>
                                    <?php endif; ?>
                                </div>
                                <h3><?= htmlspecialchars((string) $item['internship_title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <ul class="offer-meta">
                                    <li><strong>Eleve :</strong> <?= htmlspecialchars($studentLabel, ENT_QUOTES, 'UTF-8'); ?></li>
                                    <li><strong>Classe :</strong> <?= htmlspecialchars((string) ($item['student_school_class'] ?? $item['classe'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></li>
                                    <li><strong>Entreprise :</strong> <?= htmlspecialchars((string) $item['company_label'], ENT_QUOTES, 'UTF-8'); ?></li>
                                    <li><strong>Statut offre :</strong> <?= htmlspecialchars((string) $item['internship_status'], ENT_QUOTES, 'UTF-8'); ?></li>
                                    <?php if ($isAnonymized): ?>
                                        <li><strong>Confidentialite :</strong> candidature deja anonymisee apres la campagne</li>
                                    <?php endif; ?>
                                </ul>
                                <p class="offer-excerpt"><?= htmlspecialchars((string) $item['message'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
