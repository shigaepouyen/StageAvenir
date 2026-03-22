<?php

declare(strict_types=1);

$currentUser = $currentUser ?? null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Offres de stage disponibles', ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_path('app.css'), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="page-student">
    <main class="page-shell">
        <nav class="top-nav surface">
            <div class="nav-links">
                <a class="nav-link" href="<?= htmlspecialchars(app_path('/'), ENT_QUOTES, 'UTF-8'); ?>">Accueil</a>
                <a class="nav-link" href="<?= htmlspecialchars(app_path('/search'), ENT_QUOTES, 'UTF-8'); ?>">Recherche</a>
                <a class="nav-link" href="<?= htmlspecialchars(app_path('/help'), ENT_QUOTES, 'UTF-8'); ?>">Aide</a>
                <?php if (($currentUser['role'] ?? '') === 'student'): ?>
                    <a class="nav-link" href="<?= htmlspecialchars(app_path('/my-applications'), ENT_QUOTES, 'UTF-8'); ?>">Mes candidatures</a>
                <?php endif; ?>
            </div>
            <?php if ($currentUser === null): ?>
                <a class="button-secondary" href="<?= htmlspecialchars(app_path('/login?' . http_build_query(['return_to' => '/offers'])), ENT_QUOTES, 'UTF-8'); ?>">Me connecter</a>
            <?php else: ?>
                <form class="inline-form" method="post" action="<?= htmlspecialchars(app_path('/logout'), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="button-secondary">Me deconnecter</button>
                </form>
            <?php endif; ?>
        </nav>

        <section class="hero">
            <div class="hero-copy">
                <p class="eyebrow">Offres actives</p>
                <h1 class="hero-title"><?= htmlspecialchars($title ?? 'Offres de stage disponibles', ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="hero-text">Voici toutes les offres actuellement visibles pour les eleves. Ouvre une fiche pour lire les details et candidater.</p>
                <div class="inline-actions">
                    <a class="button" href="<?= htmlspecialchars(app_path('/search'), ENT_QUOTES, 'UTF-8'); ?>">Filtrer par recherche</a>
                </div>
            </div>
        </section>

        <?php if ($items === []): ?>
            <div class="empty-state" style="margin-top: 1.5rem;">Aucune offre active n'est disponible actuellement.</div>
        <?php else: ?>
            <section class="results-grid" style="margin-top: 1.5rem;">
                <?php foreach ($items as $item): ?>
                    <article class="offer-card">
                        <div class="offer-card-top">
                            <span class="stat-badge"><?= htmlspecialchars((string) $item['places_count'], ENT_QUOTES, 'UTF-8'); ?> place(s)</span>
                            <?php if (isset($item['distance_km']) && $item['distance_km'] !== null): ?>
                                <span class="stat-badge stat-badge-soft"><?= htmlspecialchars((string) $item['distance_km'], ENT_QUOTES, 'UTF-8'); ?> km</span>
                            <?php endif; ?>
                        </div>
                        <h3><?= htmlspecialchars((string) $item['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p class="offer-excerpt"><?= htmlspecialchars((string) $item['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <ul class="offer-meta">
                            <li><strong>Entreprise :</strong> <?= htmlspecialchars((string) ($item['company_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></li>
                            <li><strong>Adresse :</strong> <?= htmlspecialchars((string) ($item['company_address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></li>
                            <li><strong>Secteur :</strong> <?= htmlspecialchars((string) ($item['sector_tag'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></li>
                        </ul>
                        <div class="inline-actions">
                            <a class="button" href="<?= htmlspecialchars(app_path('/offers/' . (string) $item['id']), ENT_QUOTES, 'UTF-8'); ?>">Voir l'offre</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <p class="top-link"><a href="<?= htmlspecialchars(app_path('/'), ENT_QUOTES, 'UTF-8'); ?>">Retour a l'accueil</a></p>
    </main>
</body>
</html>
