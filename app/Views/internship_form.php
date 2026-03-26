<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soumettre une offre</title>
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
                    <a class="nav-link" href="<?= htmlspecialchars(app_path('/company-applications'), ENT_QUOTES, 'UTF-8'); ?>">Candidatures</a>
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
                <h1 class="hero-title">Soumettre une offre</h1>
                <p class="hero-text">Decrivez le stage de façon claire, choisissez un secteur et indiquez le nombre de places. L'offre sera relue avant publication.</p>
                <div class="step-chip-row">
                    <span class="step-chip">Titre simple</span>
                    <span class="step-chip">Description concrete</span>
                    <span class="step-chip">Validation avant publication</span>
                </div>
            </div>
            <aside class="hero-panel">
                <p class="eyebrow">Pour aller vite</p>
                <ol class="step-list">
                    <li><span class="step-index">1</span>Donner un titre simple et parlant.</li>
                    <li><span class="step-index">2</span>Expliquer ce que l'eleve verra pendant la semaine.</li>
                    <li><span class="step-index">3</span>Envoyer l'offre pour validation.</li>
                </ol>
            </aside>
        </section>

        <?php if (!empty($error)): ?>
            <p class="message message-error" style="margin-top: 1rem;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if (!empty($company)): ?>
            <section class="surface" style="margin-top: 1.4rem;">
                <form method="post" action="<?= htmlspecialchars(app_path('/internships/create'), ENT_QUOTES, 'UTF-8'); ?>">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>"
                    >
                    <div class="field-grid">
                        <div class="field-group field-span-2">
                            <label for="title">Titre</label>
                            <input
                                id="title"
                                name="title"
                                type="text"
                                required
                                value="<?= htmlspecialchars((string) $formData['title'], ENT_QUOTES, 'UTF-8'); ?>"
                            >
                        </div>

                        <div class="field-group field-span-2">
                            <label for="description">Description</label>
                            <textarea
                                id="description"
                                name="description"
                                rows="8"
                                required
                            ><?= htmlspecialchars((string) $formData['description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>

                        <div class="field-group">
                            <label for="sector_tag">Secteur</label>
                            <select id="sector_tag" name="sector_tag">
                                <option value="">Choisir un secteur</option>
                                <?php foreach (\App\Controllers\InternshipController::sectorTags() as $tag): ?>
                                    <option
                                        value="<?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?>"
                                        <?= (string) $formData['sector_tag'] === $tag ? 'selected' : ''; ?>
                                    >
                                        <?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="field-group">
                            <label for="places_count">Nombre de places</label>
                            <input
                                id="places_count"
                                name="places_count"
                                type="number"
                                min="1"
                                required
                                value="<?= htmlspecialchars((string) $formData['places_count'], ENT_QUOTES, 'UTF-8'); ?>"
                            >
                        </div>
                    </div>

                    <p class="section-copy" style="margin-top: 1rem;">Annee scolaire : <?= htmlspecialchars((string) $formData['academic_year'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p class="section-copy">L'offre sera relue par l'administration avant de devenir visible aux eleves.</p>

                    <label class="choice-pill" style="margin-top: 1rem;">
                        <input
                            type="checkbox"
                            name="certification"
                            value="1"
                            <?= $formData['certification'] === '1' ? 'checked' : ''; ?>
                        >
                        Je certifie que ce stage respecte la reglementation sur le travail des mineurs.
                    </label>

                    <div class="inline-actions">
                        <button type="submit">Envoyer pour validation</button>
                        <a class="button-secondary" href="<?= htmlspecialchars(app_path('/internships'), ENT_QUOTES, 'UTF-8'); ?>">Retour a mes offres</a>
                        <a class="button-ghost" href="<?= htmlspecialchars(app_path('/help'), ENT_QUOTES, 'UTF-8'); ?>">FAQ entreprise</a>
                    </div>
                </form>
            </section>
            <section class="support-banner" style="margin-top: 1.5rem;">
                <h2>Comment ecrire une offre facile a comprendre ?</h2>
                <p>Utilisez des phrases simples, dites ce que l'eleve va observer et evitez le jargon interne. Une bonne offre aide les collégiens a se projeter rapidement.</p>
                <div class="inline-actions">
                    <a class="button-secondary" href="<?= htmlspecialchars(app_path('/help'), ENT_QUOTES, 'UTF-8'); ?>">Voir les conseils entreprise</a>
                </div>
            </section>
        <?php else: ?>
            <section class="surface" style="margin-top: 1.4rem;">
                <p class="section-copy">Complete d'abord le profil entreprise pour pouvoir deposer une offre.</p>
                <div class="inline-actions">
                    <a class="button-secondary" href="<?= htmlspecialchars(app_path('/company-profile'), ENT_QUOTES, 'UTF-8'); ?>">Completer le profil entreprise</a>
                    <a class="button-ghost" href="<?= htmlspecialchars(app_path('/help'), ENT_QUOTES, 'UTF-8'); ?>">Voir l'aide</a>
                </div>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
