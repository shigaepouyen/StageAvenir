<?php

declare(strict_types=1);

$currentUser = $currentUser ?? null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Mes candidatures', ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_path('app.css'), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="page-student">
    <main class="page-shell">
        <nav class="top-nav surface">
            <div class="nav-links">
                <a class="nav-link" href="<?= htmlspecialchars(app_path('/'), ENT_QUOTES, 'UTF-8'); ?>">Accueil</a>
                <a class="nav-link" href="<?= htmlspecialchars(app_path('/search'), ENT_QUOTES, 'UTF-8'); ?>">Recherche</a>
                <a class="nav-link nav-link-current" href="<?= htmlspecialchars(app_path('/my-applications'), ENT_QUOTES, 'UTF-8'); ?>">Mes candidatures</a>
                <a class="nav-link" href="<?= htmlspecialchars(app_path('/help'), ENT_QUOTES, 'UTF-8'); ?>">Aide</a>
            </div>
            <?php if ($currentUser !== null): ?>
                <form class="inline-form" method="post" action="<?= htmlspecialchars(app_path('/logout'), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="button-secondary">Me deconnecter</button>
                </form>
            <?php endif; ?>
        </nav>

        <section class="hero" style="margin-top: 1rem;">
            <div class="hero-copy">
                <p class="eyebrow">Suivi eleve</p>
                <h1 class="hero-title"><?= htmlspecialchars($title ?? 'Mes candidatures', ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="hero-text">Retrouve ici les offres pour lesquelles tu as deja envoye une candidature.</p>
            </div>
        </section>

        <?php if (!empty($error)): ?>
            <p class="message message-error" style="margin-top: 1rem;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if ($items === []): ?>
            <div class="empty-state" style="margin-top: 1.5rem;">
                Tu n'as pas encore candidate. Commence par explorer les offres puis reviens ici pour suivre ce que tu as envoye.
                <div class="inline-actions">
                    <a class="button" href="<?= htmlspecialchars(app_path('/search'), ENT_QUOTES, 'UTF-8'); ?>">Chercher un stage</a>
                </div>
            </div>
        <?php else: ?>
            <section class="results-grid" style="margin-top: 1.5rem;">
                <?php foreach ($items as $item): ?>
                    <article class="offer-card">
                        <div class="offer-card-top">
                            <span class="stat-badge">Envoyee le <?= htmlspecialchars(date('d/m/Y', strtotime((string) $item['created_at'])), ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="stat-badge stat-badge-soft"><?= htmlspecialchars((string) $item['internship_status'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="stat-badge status-badge status-badge-<?= htmlspecialchars((string) ($item['application_status'] ?? 'new'), ENT_QUOTES, 'UTF-8'); ?>">
                                <?= htmlspecialchars(\App\Controllers\InternshipController::applicationStatusLabels()[(string) ($item['application_status'] ?? 'new')] ?? (string) ($item['application_status'] ?? 'new'), ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </div>
                        <h3><?= htmlspecialchars((string) $item['internship_title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <ul class="offer-meta">
                            <li><strong>Entreprise :</strong> <?= htmlspecialchars((string) ($item['company_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></li>
                            <li><strong>Adresse :</strong> <?= htmlspecialchars((string) ($item['company_address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></li>
                            <li><strong>Classe :</strong> <?= htmlspecialchars((string) $item['classe'], ENT_QUOTES, 'UTF-8'); ?></li>
                            <li><strong>Annee scolaire :</strong> <?= htmlspecialchars((string) $item['academic_year'], ENT_QUOTES, 'UTF-8'); ?></li>
                        </ul>
                        <p class="offer-excerpt"><?= htmlspecialchars((string) $item['message'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <div class="inline-actions">
                            <a class="button" href="<?= htmlspecialchars(app_path('/offers/' . (string) $item['internship_id']), ENT_QUOTES, 'UTF-8'); ?>">Revoir l'offre</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
