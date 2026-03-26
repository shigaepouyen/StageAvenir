<?php

declare(strict_types=1);

$userRole = (string) ($user['role'] ?? 'guest');
$isGuest = !isset($user) || $user === null;
$isStudent = $userRole === 'student';
$isCompany = in_array($userRole, ['company', 'parent'], true);
$isStaff = in_array($userRole, ['teacher', 'level_manager', 'admin'], true);
$isAdmin = $userRole === 'admin';
$studentSectors = ['Sante', 'Tech', 'Animaux', 'Commerce', 'Sport', 'Culture'];
$studentSearchUrl = app_path('/search');
$newApplicationsCount = (int) ($newApplicationsCount ?? 0);
$unreadNotificationsCount = (int) ($unreadNotificationsCount ?? 0);
$bodyClass = $isCompany ? 'page-company' : ($isStaff ? 'page-admin' : 'page-student');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_path('app.css'), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="<?= htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8'); ?>">
    <main class="page-shell">
        <nav class="top-nav surface">
            <div class="nav-cluster">
                <a class="nav-brand" href="<?= htmlspecialchars(app_path('/'), ENT_QUOTES, 'UTF-8'); ?>">Avenir Pro</a>
                <div class="nav-links">
                    <?php if ($isGuest || $isStudent): ?>
                        <a class="nav-link nav-link-current" href="<?= htmlspecialchars(app_path('/'), ENT_QUOTES, 'UTF-8'); ?>">Accueil</a>
                        <a class="nav-link" href="<?= htmlspecialchars($studentSearchUrl, ENT_QUOTES, 'UTF-8'); ?>">Trouver un stage</a>
                        <?php if ($isStudent): ?>
                            <a class="nav-link" href="<?= htmlspecialchars(app_path('/my-applications'), ENT_QUOTES, 'UTF-8'); ?>">Mes candidatures</a>
                            <a class="nav-link" href="<?= htmlspecialchars(app_path('/news'), ENT_QUOTES, 'UTF-8'); ?>">
                                Mes news
                                <?php if ($unreadNotificationsCount > 0): ?>
                                    <span class="count-badge"><?= htmlspecialchars((string) $unreadNotificationsCount, ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </a>
                        <?php else: ?>
                            <a class="nav-link" href="<?= htmlspecialchars(app_path('/login?account_type=company&return_to=' . rawurlencode('/company-profile')), ENT_QUOTES, 'UTF-8'); ?>">Je propose un stage</a>
                        <?php endif; ?>
                        <a class="nav-link" href="<?= htmlspecialchars(app_path('/help'), ENT_QUOTES, 'UTF-8'); ?>">Aide</a>
                    <?php elseif ($isCompany): ?>
                        <a class="nav-link nav-link-current" href="<?= htmlspecialchars(app_path('/'), ENT_QUOTES, 'UTF-8'); ?>">Tableau de bord</a>
                        <a class="nav-link" href="<?= htmlspecialchars(app_path('/company-profile'), ENT_QUOTES, 'UTF-8'); ?>">Mon entreprise</a>
                        <a class="nav-link" href="<?= htmlspecialchars(app_path('/internships'), ENT_QUOTES, 'UTF-8'); ?>">Mes offres</a>
                        <a class="nav-link" href="<?= htmlspecialchars(app_path('/company-applications'), ENT_QUOTES, 'UTF-8'); ?>">
                            Candidatures
                            <?php if ($newApplicationsCount > 0): ?>
                                <span class="count-badge"><?= htmlspecialchars((string) $newApplicationsCount, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </a>
                        <a class="nav-link" href="<?= htmlspecialchars(app_path('/help'), ENT_QUOTES, 'UTF-8'); ?>">Aide</a>
                        <a class="nav-link" href="<?= htmlspecialchars(app_path('/news'), ENT_QUOTES, 'UTF-8'); ?>">
                            Mes news
                            <?php if ($unreadNotificationsCount > 0): ?>
                                <span class="count-badge"><?= htmlspecialchars((string) $unreadNotificationsCount, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </a>
                    <?php else: ?>
                        <a class="nav-link nav-link-current" href="<?= htmlspecialchars(app_path('/'), ENT_QUOTES, 'UTF-8'); ?>">Tableau de bord</a>
                        <a class="nav-link" href="<?= htmlspecialchars(app_path('/admin/dashboard'), ENT_QUOTES, 'UTF-8'); ?>">Suivi college</a>
                        <?php if (!empty($canManageStaffAccounts)): ?>
                            <a class="nav-link" href="<?= htmlspecialchars(app_path('/admin/staff'), ENT_QUOTES, 'UTF-8'); ?>">Comptes staff</a>
                        <?php endif; ?>
                        <?php if (!empty($canAccessAdminInternships)): ?>
                            <a class="nav-link" href="<?= htmlspecialchars(app_path('/admin/internships'), ENT_QUOTES, 'UTF-8'); ?>">Moderation</a>
                        <?php endif; ?>
                        <a class="nav-link" href="<?= htmlspecialchars(app_path('/help'), ENT_QUOTES, 'UTF-8'); ?>">Aide</a>
                        <a class="nav-link" href="<?= htmlspecialchars(app_path('/news'), ENT_QUOTES, 'UTF-8'); ?>">
                            Mes news
                            <?php if ($unreadNotificationsCount > 0): ?>
                                <span class="count-badge"><?= htmlspecialchars((string) $unreadNotificationsCount, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="nav-actions">
                <?php if ($isGuest): ?>
                    <a class="button-secondary" href="<?= htmlspecialchars(app_path('/login'), ENT_QUOTES, 'UTF-8'); ?>">Connexion eleve</a>
                    <a class="button" href="<?= htmlspecialchars(app_path('/login?account_type=company&return_to=' . rawurlencode('/company-profile')), ENT_QUOTES, 'UTF-8'); ?>">Connexion entreprise</a>
                <?php else: ?>
                    <form class="inline-form" method="post" action="<?= htmlspecialchars(app_path('/logout'), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" class="button-secondary">Me deconnecter</button>
                    </form>
                <?php endif; ?>
            </div>
        </nav>

        <?php if ($isGuest || $isStudent): ?>
            <section class="hero hero-split">
                <div class="hero-copy">
                    <p class="eyebrow">Stage de 3e</p>
                    <h1 class="hero-title"><?= htmlspecialchars($isStudent ? 'Tu sais deja ou cliquer pour avancer.' : 'Trouver un stage ou proposer une offre, sans parcours complique.', ENT_QUOTES, 'UTF-8'); ?></h1>
                    <p class="hero-text"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
                    <div class="inline-actions">
                        <a class="button" href="<?= htmlspecialchars(app_path('/search'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($isStudent ? 'Chercher un stage' : 'Voir les offres', ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php if ($isGuest): ?>
                            <a class="button-secondary" href="<?= htmlspecialchars(app_path('/login'), ENT_QUOTES, 'UTF-8'); ?>">Connexion eleve</a>
                            <a class="button-ghost" href="<?= htmlspecialchars(app_path('/login?account_type=company&return_to=' . rawurlencode('/company-profile')), ENT_QUOTES, 'UTF-8'); ?>">Connexion entreprise</a>
                        <?php else: ?>
                            <a class="button-secondary" href="<?= htmlspecialchars(app_path('/my-applications'), ENT_QUOTES, 'UTF-8'); ?>">Suivre mes candidatures</a>
                            <a class="button-ghost" href="<?= htmlspecialchars(app_path('/help'), ENT_QUOTES, 'UTF-8'); ?>">Questions frequentes</a>
                        <?php endif; ?>
                    </div>
                    <div class="step-chip-row">
                        <span class="step-chip">Recherche libre</span>
                        <span class="step-chip">Connexion par Magic Link</span>
                        <span class="step-chip">Discussion dans la webapp</span>
                    </div>
                </div>

                <aside class="hero-panel">
                    <p class="eyebrow">Parcours simple</p>
                    <ol class="step-list">
                        <li><span class="step-index">1</span>Je trouve une offre interessante.</li>
                        <li><span class="step-index">2</span>Je lis la fiche et je regarde le lieu.</li>
                        <li><span class="step-index">3</span>Je me connecte uniquement pour candidater.</li>
                    </ol>
                </aside>
            </section>

            <?php if ($isGuest): ?>
                <section class="journey-grid">
                    <article class="journey-panel">
                        <p class="eyebrow">Collégiens</p>
                        <h2>Je cherche un stage</h2>
                        <p>Tout commence par la recherche. Tu peux regarder les offres avant de te connecter.</p>
                        <ol class="process-list">
                            <li><span class="process-index">1</span><span>Choisir un secteur ou taper un mot-cle.</span></li>
                            <li><span class="process-index">2</span><span>Lire une offre et verifier le lieu.</span></li>
                            <li><span class="process-index">3</span><span>Se connecter par Magic Link au moment de candidater.</span></li>
                        </ol>
                        <div class="inline-actions">
                            <a class="button" href="<?= htmlspecialchars(app_path('/search'), ENT_QUOTES, 'UTF-8'); ?>">Commencer</a>
                            <a class="button-secondary" href="<?= htmlspecialchars(app_path('/help'), ENT_QUOTES, 'UTF-8'); ?>">FAQ collégien</a>
                        </div>
                    </article>

                    <article class="journey-panel">
                        <p class="eyebrow">Entreprises</p>
                        <h2>Je propose un stage</h2>
                        <p>Le parcours entreprise se fait aussi sans mot de passe, puis en trois etapes claires.</p>
                        <ol class="process-list">
                            <li><span class="process-index">1</span><span>Je cree mon acces par email professionnel.</span></li>
                            <li><span class="process-index">2</span><span>Je verifie mon entreprise avec le SIRET.</span></li>
                            <li><span class="process-index">3</span><span>Je publie une offre et je suis les candidatures.</span></li>
                        </ol>
                        <div class="inline-actions">
                            <a class="button" href="<?= htmlspecialchars(app_path('/login?account_type=company&return_to=' . rawurlencode('/company-profile')), ENT_QUOTES, 'UTF-8'); ?>">Commencer cote entreprise</a>
                            <a class="button-secondary" href="<?= htmlspecialchars(app_path('/help'), ENT_QUOTES, 'UTF-8'); ?>">FAQ entreprise</a>
                        </div>
                    </article>
                </section>
            <?php else: ?>
                <section class="journey-grid">
                    <article class="journey-panel">
                        <p class="eyebrow">Mes prochaines etapes</p>
                        <h2>Avancer sans te perdre</h2>
                        <ol class="process-list">
                            <li><span class="process-index">1</span><span>Fais une recherche avec un mot-cle ou un secteur.</span></li>
                            <li><span class="process-index">2</span><span>Ouvre quelques fiches pour comparer les lieux et les places.</span></li>
                            <li><span class="process-index">3</span><span>Retrouve ensuite tes candidatures dans ton espace.</span></li>
                        </ol>
                    </article>

                    <article class="support-banner">
                        <h2>Besoin d'un coup de pouce ?</h2>
                        <p>La page d'aide repond aux questions les plus courantes sur le Magic Link, la recherche et les candidatures.</p>
                        <div class="inline-actions">
                            <a class="button-secondary" href="<?= htmlspecialchars(app_path('/help'), ENT_QUOTES, 'UTF-8'); ?>">Ouvrir l'aide</a>
                            <a class="button" href="<?= htmlspecialchars(app_path('/news'), ENT_QUOTES, 'UTF-8'); ?>">Voir mes news</a>
                        </div>
                    </article>
                </section>
            <?php endif; ?>

            <section class="card-grid" style="margin-top: 1.5rem;">
                <article class="mini-card">
                    <h3>Recherche simple</h3>
                    <p class="section-copy">Cherche avec un mot-cle ou choisis directement un secteur qui te parle.</p>
                </article>
                <article class="mini-card">
                    <h3>Offres proches</h3>
                    <p class="section-copy">Si tu utilises ta position, les offres les plus proches remontent d'abord.</p>
                </article>
                <article class="mini-card">
                    <h3>Connexion legere</h3>
                    <p class="section-copy">Aucun mot de passe a retenir : l'email contient simplement un lien de connexion.</p>
                </article>
            </section>

            <section class="surface" style="margin-top: 1.5rem;">
                <p class="eyebrow">Idees de secteurs</p>
                <h2 class="section-title">Tu ne sais pas encore quoi chercher ?</h2>
                <p class="section-copy">Pars d'un secteur, ouvre quelques fiches, puis garde celles qui te donnent vraiment envie.</p>
                <div class="pill-row">
                    <?php foreach ($studentSectors as $sector): ?>
                        <a class="choice-pill" href="<?= htmlspecialchars(app_path('/search?tag=' . rawurlencode($sector)), ENT_QUOTES, 'UTF-8'); ?>">
                            <?= htmlspecialchars($sector, ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="inline-actions">
                    <a class="button-secondary" href="<?= htmlspecialchars(app_path('/help'), ENT_QUOTES, 'UTF-8'); ?>">Lire la FAQ collégien</a>
                </div>
            </section>
        <?php elseif ($isCompany): ?>
            <section class="hero hero-split">
                <div class="hero-copy">
                    <p class="eyebrow">Espace entreprise</p>
                    <h1 class="hero-title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>
                    <p class="hero-text"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
                    <div class="inline-actions">
                        <a class="button" href="<?= htmlspecialchars(app_path('/company-profile'), ENT_QUOTES, 'UTF-8'); ?>">Verifier mon entreprise</a>
                        <a class="button-secondary" href="<?= htmlspecialchars(app_path('/internships'), ENT_QUOTES, 'UTF-8'); ?>">Voir mes offres</a>
                        <a class="button-ghost" href="<?= htmlspecialchars(app_path('/company-applications'), ENT_QUOTES, 'UTF-8'); ?>">Suivre les candidatures</a>
                    </div>
                </div>
                <aside class="hero-panel">
                    <p class="eyebrow">En 3 etapes</p>
                    <ol class="step-list">
                        <li><span class="step-index">1</span>Je verifie mon entreprise avec le SIRET.</li>
                        <li><span class="step-index">2</span>Je publie une offre claire et simple.</li>
                        <li><span class="step-index">3</span>Je lis les candidatures et je reponds dans la webapp.</li>
                    </ol>
                </aside>
            </section>

            <section class="journey-grid">
                <article class="journey-panel">
                    <p class="eyebrow">Acces rapides</p>
                    <h2>Commencer sans hesiter</h2>
                    <div class="role-actions">
                        <a class="role-link" href="<?= htmlspecialchars(app_path('/company-profile'), ENT_QUOTES, 'UTF-8'); ?>">1. Mon entreprise</a>
                        <a class="role-link" href="<?= htmlspecialchars(app_path('/internships'), ENT_QUOTES, 'UTF-8'); ?>">2. Mes offres</a>
                        <a class="role-link" href="<?= htmlspecialchars(app_path('/company-applications'), ENT_QUOTES, 'UTF-8'); ?>">
                            3. Mes candidatures
                            <?php if ($newApplicationsCount > 0): ?>
                                <span class="count-badge"><?= htmlspecialchars((string) $newApplicationsCount, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                </article>

                <article class="support-banner">
                    <h2>Questions frequentes</h2>
                    <p>Le centre d'aide entreprise explique le SIRET, la validation des offres et le fonctionnement des discussions avec les eleves.</p>
                    <div class="inline-actions">
                        <a class="button-secondary" href="<?= htmlspecialchars(app_path('/help'), ENT_QUOTES, 'UTF-8'); ?>">Ouvrir l'aide entreprise</a>
                        <a class="button" href="<?= htmlspecialchars(app_path('/news'), ENT_QUOTES, 'UTF-8'); ?>">Voir mes news</a>
                    </div>
                </article>
            </section>
        <?php else: ?>
            <section class="hero hero-split">
                <div class="hero-copy">
                    <p class="eyebrow">Equipe educative</p>
                    <h1 class="hero-title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>
                    <p class="hero-text"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <aside class="hero-panel">
                    <h2 class="section-title">Acces rapides</h2>
                    <div class="role-actions">
                        <a class="role-link" href="<?= htmlspecialchars(app_path('/admin/dashboard'), ENT_QUOTES, 'UTF-8'); ?>">Suivi college</a>
                        <?php if (!empty($canManageStaffAccounts)): ?>
                            <a class="role-link" href="<?= htmlspecialchars(app_path('/admin/staff'), ENT_QUOTES, 'UTF-8'); ?>">Comptes staff</a>
                        <?php endif; ?>
                        <?php if (!empty($canAccessAdminInternships)): ?>
                            <a class="role-link" href="<?= htmlspecialchars(app_path('/admin/internships'), ENT_QUOTES, 'UTF-8'); ?>">Moderation entreprises et offres</a>
                        <?php endif; ?>
                        <a class="role-link" href="<?= htmlspecialchars(app_path('/help'), ENT_QUOTES, 'UTF-8'); ?>">Aide equipe educative</a>
                    </div>
                </aside>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
