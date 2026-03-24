<?php

declare(strict_types=1);

$userRole = (string) ($user['role'] ?? 'guest');
$isGuest = !isset($user) || $user === null;
$isStudent = $userRole === 'student';
$studentSectors = ['Sante', 'Tech', 'Animaux', 'Commerce', 'Sport', 'Culture'];
$studentSearchUrl = app_path('/search');
$newApplicationsCount = (int) ($newApplicationsCount ?? 0);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_path('app.css'), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="page-student">
    <main class="page-shell">
        <nav class="top-nav surface">
            <div class="nav-links">
                <a class="nav-link nav-link-current" href="<?= htmlspecialchars(app_path('/'), ENT_QUOTES, 'UTF-8'); ?>">Accueil</a>
                <a class="nav-link" href="<?= htmlspecialchars($studentSearchUrl, ENT_QUOTES, 'UTF-8'); ?>">Recherche</a>
                <a class="nav-link" href="<?= htmlspecialchars(app_path('/help'), ENT_QUOTES, 'UTF-8'); ?>">Aide</a>
                <?php if ($isStudent): ?>
                    <a class="nav-link" href="<?= htmlspecialchars(app_path('/my-applications'), ENT_QUOTES, 'UTF-8'); ?>">Mes candidatures</a>
                <?php endif; ?>
            </div>
            <?php if ($isGuest): ?>
                <a class="button-secondary" href="<?= htmlspecialchars(app_path('/login'), ENT_QUOTES, 'UTF-8'); ?>">Me connecter</a>
            <?php else: ?>
                <form class="inline-form" method="post" action="<?= htmlspecialchars(app_path('/logout'), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="button-secondary">Me deconnecter</button>
                </form>
            <?php endif; ?>
        </nav>

        <?php if ($isGuest || $isStudent): ?>
            <section class="hero hero-split">
                <div class="hero-copy">
                    <p class="eyebrow">Stages de 3e</p>
                    <h1 class="hero-title">Trouve un stage qui te donne envie de te lever le matin.</h1>
                    <p class="hero-text"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
                    <div class="inline-actions">
                        <a class="button" href="<?= htmlspecialchars(app_path('/search'), ENT_QUOTES, 'UTF-8'); ?>">Commencer ma recherche</a>
                        <?php if ($isGuest): ?>
                            <a class="button-secondary" href="<?= htmlspecialchars(app_path('/login'), ENT_QUOTES, 'UTF-8'); ?>">Me connecter</a>
                            <a class="button-ghost" href="<?= htmlspecialchars(app_path('/login?account_type=company&return_to=' . rawurlencode('/company-profile')), ENT_QUOTES, 'UTF-8'); ?>">Je suis une entreprise</a>
                        <?php else: ?>
                            <a class="button-secondary" href="<?= htmlspecialchars(app_path('/offers'), ENT_QUOTES, 'UTF-8'); ?>">Voir toutes les offres</a>
                            <a class="button-ghost" href="<?= htmlspecialchars(app_path('/my-applications'), ENT_QUOTES, 'UTF-8'); ?>">Mes candidatures</a>
                        <?php endif; ?>
                    </div>
                    <p class="student-note">Tu peux chercher librement. La connexion est demandee seulement au moment de candidater.</p>
                </div>

                <aside class="hero-panel">
                    <p class="eyebrow">Comment ca marche</p>
                    <ol class="step-list">
                        <li><span class="step-index">1</span>Choisis un secteur ou tape un mot-cle.</li>
                        <li><span class="step-index">2</span>Regarde les offres proches de toi et lis les details.</li>
                        <li><span class="step-index">3</span>Candidate en quelques lignes, sans mot de passe complique.</li>
                    </ol>
                </aside>
            </section>

            <section class="card-grid" style="margin-top: 1.5rem;">
                <article class="mini-card">
                    <h3>Recherche simple</h3>
                    <p class="section-copy">Tu peux filtrer par domaine et repere facilement les offres qui ont encore des places.</p>
                </article>
                <article class="mini-card">
                    <h3>Offres proches de toi</h3>
                    <p class="section-copy">Si tu utilises ta position, les offres les plus proches remontent en premier.</p>
                </article>
                <article class="mini-card">
                    <h3>Candidature rapide</h3>
                    <p class="section-copy">Un petit message de motivation suffit pour te presenter a l'entreprise.</p>
                </article>
            </section>

            <section class="surface" style="margin-top: 1.5rem;">
                <p class="eyebrow">Idees de secteurs</p>
                <h2 class="section-title">Tu ne sais pas par ou commencer ?</h2>
                <p class="section-copy">Commence par un secteur qui te parle, puis ouvre les offres qui t'intriguent.</p>
                <div class="pill-row">
                    <?php foreach ($studentSectors as $sector): ?>
                        <a
                            class="choice-pill"
                            href="<?= htmlspecialchars(app_path('/search?tag=' . rawurlencode($sector)), ENT_QUOTES, 'UTF-8'); ?>"
                        >
                            <?= htmlspecialchars($sector, ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="inline-actions">
                    <a class="button-secondary" href="<?= htmlspecialchars(app_path('/help'), ENT_QUOTES, 'UTF-8'); ?>">Lire l'aide eleve</a>
                </div>
            </section>

            <?php if ($isGuest): ?>
                <section class="surface" style="margin-top: 1.5rem;">
                    <p class="eyebrow">Entreprises</p>
                    <h2 class="section-title">Vous proposez un stage ?</h2>
                    <p class="section-copy">Un parcours dedie vous permet de creer un compte entreprise, completer votre profil puis publier vos offres en quelques minutes.</p>
                    <div class="inline-actions">
                        <a class="button-secondary" href="<?= htmlspecialchars(app_path('/login?account_type=company&return_to=' . rawurlencode('/company-profile')), ENT_QUOTES, 'UTF-8'); ?>">Je suis une entreprise</a>
                    </div>
                </section>
            <?php endif; ?>
        <?php else: ?>
            <section class="hero hero-split">
                <div class="hero-copy">
                    <p class="eyebrow">Tableau de bord</p>
                    <h1 class="hero-title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>
                    <p class="hero-text"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <aside class="hero-panel">
                    <h2 class="section-title">Acces rapides</h2>
                    <p class="role-copy">Retrouve ici les actions principales pour gerer les entreprises, les offres et l'administration.</p>
                    <div class="role-actions">
                        <a class="role-link" href="<?= htmlspecialchars(app_path('/search'), ENT_QUOTES, 'UTF-8'); ?>">Voir l'espace eleve</a>
                        <?php if (!empty($canManageCompanyProfile)): ?>
                            <a class="role-link" href="<?= htmlspecialchars(app_path('/company-profile'), ENT_QUOTES, 'UTF-8'); ?>">Gerer le profil entreprise</a>
                        <?php endif; ?>
                        <?php if (!empty($canManageInternships)): ?>
                            <a class="role-link" href="<?= htmlspecialchars(app_path('/internships'), ENT_QUOTES, 'UTF-8'); ?>">Gerer les offres</a>
                            <a class="role-link" href="<?= htmlspecialchars(app_path('/company-applications'), ENT_QUOTES, 'UTF-8'); ?>">
                                Candidatures recues
                                <?php if ($newApplicationsCount > 0): ?>
                                    <span class="count-badge"><?= htmlspecialchars((string) $newApplicationsCount, ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($canAccessAdminInternships)): ?>
                            <a class="role-link" href="<?= htmlspecialchars(app_path('/admin/dashboard'), ENT_QUOTES, 'UTF-8'); ?>">Tableau college</a>
                            <a class="role-link" href="<?= htmlspecialchars(app_path('/admin/internships'), ENT_QUOTES, 'UTF-8'); ?>">Administration des offres</a>
                        <?php endif; ?>
                    </div>
                </aside>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
