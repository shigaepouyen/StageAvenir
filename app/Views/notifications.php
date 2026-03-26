<?php

declare(strict_types=1);

$currentUser = $currentUser ?? null;
$items = is_array($items ?? null) ? $items : [];
$unreadCount = (int) ($unreadCount ?? 0);
$role = (string) ($currentUser['role'] ?? 'guest');
$isCompany = in_array($role, ['company', 'parent'], true);
$isStaff = in_array($role, ['teacher', 'level_manager', 'admin'], true);
$isAdmin = $role === 'admin';
$bodyClass = $isCompany ? 'page-company' : ($isStaff ? 'page-admin' : 'page-student');
$backUrl = app_path('/');

if ($role === 'student') {
    $backUrl = app_path('/my-applications');
} elseif ($isCompany) {
    $backUrl = app_path('/company-applications');
} elseif ($isStaff) {
    $backUrl = app_path('/admin/dashboard');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Mes news', ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_path('app.css'), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="<?= htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8'); ?>">
    <main class="page-shell">
        <nav class="top-nav surface">
            <div class="nav-cluster">
                <a class="nav-brand" href="<?= htmlspecialchars(app_path('/'), ENT_QUOTES, 'UTF-8'); ?>">Avenir Pro</a>
                <div class="nav-links">
                    <a class="nav-link" href="<?= htmlspecialchars(app_path('/'), ENT_QUOTES, 'UTF-8'); ?>">Accueil</a>
                    <?php if ($isCompany): ?>
                        <a class="nav-link" href="<?= htmlspecialchars(app_path('/company-profile'), ENT_QUOTES, 'UTF-8'); ?>">Mon entreprise</a>
                        <a class="nav-link" href="<?= htmlspecialchars(app_path('/internships'), ENT_QUOTES, 'UTF-8'); ?>">Mes offres</a>
                        <a class="nav-link" href="<?= htmlspecialchars(app_path('/company-applications'), ENT_QUOTES, 'UTF-8'); ?>">Candidatures</a>
                    <?php elseif ($isStaff): ?>
                        <a class="nav-link" href="<?= htmlspecialchars(app_path('/admin/dashboard'), ENT_QUOTES, 'UTF-8'); ?>">Suivi college</a>
                        <?php if ($isAdmin): ?>
                            <a class="nav-link" href="<?= htmlspecialchars(app_path('/admin/staff'), ENT_QUOTES, 'UTF-8'); ?>">Comptes staff</a>
                            <a class="nav-link" href="<?= htmlspecialchars(app_path('/admin/internships'), ENT_QUOTES, 'UTF-8'); ?>">Moderation</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a class="nav-link" href="<?= htmlspecialchars(app_path('/search'), ENT_QUOTES, 'UTF-8'); ?>">Trouver un stage</a>
                        <?php if ($role === 'student'): ?>
                            <a class="nav-link" href="<?= htmlspecialchars(app_path('/my-applications'), ENT_QUOTES, 'UTF-8'); ?>">Mes candidatures</a>
                        <?php endif; ?>
                    <?php endif; ?>
                    <a class="nav-link nav-link-current" href="<?= htmlspecialchars(app_path('/news'), ENT_QUOTES, 'UTF-8'); ?>">Mes news</a>
                    <a class="nav-link" href="<?= htmlspecialchars(app_path('/help'), ENT_QUOTES, 'UTF-8'); ?>">Aide</a>
                </div>
            </div>
            <div class="nav-actions">
                <?php if ($currentUser !== null): ?>
                    <form class="inline-form" method="post" action="<?= htmlspecialchars(app_path('/logout'), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" class="button-secondary">Me deconnecter</button>
                    </form>
                <?php endif; ?>
            </div>
        </nav>

        <section class="hero hero-split">
            <div class="hero-copy">
                <p class="eyebrow">Notifications</p>
                <h1 class="hero-title"><?= htmlspecialchars($title ?? 'Mes news', ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="hero-text">Retrouve ici toutes les nouveautes de la plateforme. Les emails servent uniquement d'alerte pour te demander de revenir dans Avenir Pro.</p>
                <div class="step-chip-row">
                    <span class="step-chip">1. Alerte email</span>
                    <span class="step-chip">2. Lecture des news</span>
                    <span class="step-chip">3. Action dans l'espace</span>
                </div>
            </div>
            <aside class="hero-panel">
                <p class="eyebrow">Non lues</p>
                <p class="hero-title" style="font-size: clamp(2rem, 4vw, 3.4rem);"><?= htmlspecialchars((string) $unreadCount, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php if ($unreadCount > 0): ?>
                    <form method="post" action="<?= htmlspecialchars(app_path('/news/mark-all-read'), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit">Tout marquer comme lu</button>
                    </form>
                <?php endif; ?>
            </aside>
        </section>

        <?php if (!empty($error)): ?>
            <p class="message message-error" style="margin-top: 1rem;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <p class="message message-success" style="margin-top: 1rem;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if ($items === []): ?>
            <div class="empty-state" style="margin-top: 1.5rem;">
                Aucune news pour le moment.
            </div>
        <?php else: ?>
            <section class="results-grid" style="margin-top: 1.5rem;">
                <?php foreach ($items as $item): ?>
                    <?php $isRead = !empty($item['is_read']); ?>
                    <article class="offer-card">
                        <div class="offer-card-top">
                            <span class="stat-badge <?= $isRead ? 'stat-badge-soft' : 'status-badge status-badge-new'; ?>">
                                <?= htmlspecialchars($isRead ? 'Lu' : 'Nouveau', ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                            <span class="stat-badge stat-badge-soft"><?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $item['created_at'])), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <h3><?= htmlspecialchars((string) ($item['title'] ?? 'Notification'), ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p class="offer-description"><?= nl2br(htmlspecialchars((string) ($item['body'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></p>
                        <?php if (!empty($item['link_path'])): ?>
                            <div class="inline-actions">
                                <a class="button-secondary" href="<?= htmlspecialchars(app_path((string) $item['link_path']), ENT_QUOTES, 'UTF-8'); ?>">Ouvrir</a>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <section class="support-banner" style="margin-top: 1.5rem;">
            <h2>Pourquoi recevoir un email si tout se passe dans la webapp ?</h2>
            <p>Les emails n'exposent pas le contenu detaille. Ils servent seulement d'alerte pour inviter l'utilisateur a revenir dans son espace connecte et lire la nouveaute en securite.</p>
            <div class="inline-actions">
                <a class="button-secondary" href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8'); ?>">Revenir a mon espace</a>
                <a class="button-ghost" href="<?= htmlspecialchars(app_path('/help'), ENT_QUOTES, 'UTF-8'); ?>">Voir l'aide</a>
            </div>
        </section>
    </main>
</body>
</html>
