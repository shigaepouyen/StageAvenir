<?php

declare(strict_types=1);

$currentUser = $user ?? null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Aide eleve', ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_path('app.css'), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="page-student">
    <main class="page-shell">
        <nav class="top-nav surface">
            <div class="nav-links">
                <a class="nav-link" href="<?= htmlspecialchars(app_path('/'), ENT_QUOTES, 'UTF-8'); ?>">Accueil</a>
                <a class="nav-link" href="<?= htmlspecialchars(app_path('/search'), ENT_QUOTES, 'UTF-8'); ?>">Recherche</a>
                <?php if (($currentUser['role'] ?? '') === 'student'): ?>
                    <a class="nav-link" href="<?= htmlspecialchars(app_path('/my-applications'), ENT_QUOTES, 'UTF-8'); ?>">Mes candidatures</a>
                <?php endif; ?>
                <a class="nav-link nav-link-current" href="<?= htmlspecialchars(app_path('/help'), ENT_QUOTES, 'UTF-8'); ?>">Aide</a>
            </div>
            <?php if ($currentUser !== null): ?>
                <form class="inline-form" method="post" action="<?= htmlspecialchars(app_path('/logout'), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="button-secondary">Me deconnecter</button>
                </form>
            <?php else: ?>
                <a class="button-secondary" href="<?= htmlspecialchars(app_path('/login'), ENT_QUOTES, 'UTF-8'); ?>">Me connecter</a>
            <?php endif; ?>
        </nav>

        <section class="hero" style="margin-top: 1rem;">
            <div class="hero-copy">
                <p class="eyebrow">Aide eleve</p>
                <h1 class="hero-title"><?= htmlspecialchars($title ?? 'Aide eleve', ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="hero-text">Quelques reponses simples aux questions que tu peux te poser avant ou pendant ta recherche de stage.</p>
            </div>
        </section>

        <section class="dashboard-grid" style="margin-top: 1.5rem;">
            <article class="dashboard-card">
                <h3>Je n'ai pas recu l'email</h3>
                <p class="section-copy">Regarde dans les courriers indésirables. Si besoin, attends quelques minutes puis redemande un lien de connexion.</p>
            </article>
            <article class="dashboard-card">
                <h3>Je ne sais pas quoi ecrire</h3>
                <p class="section-copy">Explique en quelques lignes pourquoi le stage t'interesse, ce que tu aimerais observer et ce que tu veux decouvrir.</p>
            </article>
            <article class="dashboard-card">
                <h3>Je veux des offres proches</h3>
                <p class="section-copy">Dans la recherche, clique sur “Utiliser ma position” pour faire remonter les offres les plus proches de toi.</p>
            </article>
        </section>

        <section class="surface" style="margin-top: 1.5rem;">
            <h2 class="section-title">Questions frequentes</h2>
            <div class="faq-list">
                <details class="faq-item" open>
                    <summary>Est-ce que je peux regarder les offres sans etre connecte ?</summary>
                    <p>Oui. Tu peux rechercher et ouvrir les fiches librement. La connexion sert surtout au moment de candidater.</p>
                </details>
                <details class="faq-item">
                    <summary>Comment savoir si ma candidature est partie ?</summary>
                    <p>Apres l'envoi, un message de confirmation s'affiche. Tu peux aussi retrouver tes candidatures dans “Mes candidatures”.</p>
                </details>
                <details class="faq-item">
                    <summary>Si je me trompe de stage, que faire ?</summary>
                    <p>Relis bien la fiche avant d'envoyer. Si tu as deja candidate, parle-en vite a un adulte ou a ton etablissement pour savoir quoi faire ensuite.</p>
                </details>
                <details class="faq-item">
                    <summary>Que mettre dans mon message ?</summary>
                    <p>Reste simple : qui tu es, ce qui t'interesse dans ce stage, et ce que tu aimerais decouvrir pendant la semaine d'observation.</p>
                </details>
            </div>

            <div class="inline-actions">
                <a class="button" href="<?= htmlspecialchars(app_path('/search'), ENT_QUOTES, 'UTF-8'); ?>">Chercher un stage</a>
                <?php if (($currentUser['role'] ?? '') === 'student'): ?>
                    <a class="button-secondary" href="<?= htmlspecialchars(app_path('/my-applications'), ENT_QUOTES, 'UTF-8'); ?>">Voir mes candidatures</a>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>
