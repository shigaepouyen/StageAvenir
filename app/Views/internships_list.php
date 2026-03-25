<?php

declare(strict_types=1);

$newApplicationsCount = (int) ($newApplicationsCount ?? 0);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Mes offres de stage', ENT_QUOTES, 'UTF-8'); ?></title>
</head>
<body>
    <main>
        <p><a href="<?= htmlspecialchars(app_path('/news'), ENT_QUOTES, 'UTF-8'); ?>">Voir mes news</a></p>
        <h1><?= htmlspecialchars($title ?? 'Mes offres de stage', ENT_QUOTES, 'UTF-8'); ?></h1>

        <?php if (!empty($error)): ?>
            <p style="color: #b00020;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <p style="color: #0a5f2b;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if (!empty($company) && empty($accessDenied)): ?>
            <p><strong>Validation entreprise :</strong> <?= htmlspecialchars(\App\Controllers\InternshipController::validationStatusLabels()[(string) ($company['validation_status'] ?? 'pending')] ?? (string) ($company['validation_status'] ?? 'pending'), ENT_QUOTES, 'UTF-8'); ?></p>
            <p><a href="<?= htmlspecialchars(app_path('/internships/create'), ENT_QUOTES, 'UTF-8'); ?>">Ajouter une offre</a></p>
            <p>
                <a href="<?= htmlspecialchars(app_path('/company-applications'), ENT_QUOTES, 'UTF-8'); ?>">
                    Voir les candidatures recues
                    <?php if ($newApplicationsCount > 0): ?>
                        (<?= htmlspecialchars((string) $newApplicationsCount, ENT_QUOTES, 'UTF-8'); ?> nouvelle(s))
                    <?php endif; ?>
                </a>
            </p>

            <?php if ($items === []): ?>
                <p>Aucune offre pour le moment.</p>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <article style="margin-bottom: 1rem; padding: 1rem; border: 1px solid #ccc;">
                        <h2><?= htmlspecialchars((string) $item['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                        <p><?= nl2br(htmlspecialchars((string) $item['description'], ENT_QUOTES, 'UTF-8')); ?></p>
                        <p>Secteur : <?= htmlspecialchars((string) ($item['sector_tag'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        <p>Places : <?= htmlspecialchars((string) $item['places_count'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p>Statut : <?= htmlspecialchars((string) $item['status'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p>Validation : <?= htmlspecialchars(\App\Controllers\InternshipController::validationStatusLabels()[(string) ($item['validation_status'] ?? 'pending')] ?? (string) ($item['validation_status'] ?? 'pending'), ENT_QUOTES, 'UTF-8'); ?></p>
                        <p>Annee scolaire : <?= htmlspecialchars((string) $item['academic_year'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php if ((string) $item['status'] === 'active'): ?>
                            <form method="post" action="<?= htmlspecialchars(app_path('/internships/' . (string) $item['id'] . '/sleep'), ENT_QUOTES, 'UTF-8'); ?>">
                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>"
                                >
                                <button type="submit">Passer en invisible</button>
                            </form>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php else: ?>
            <p><a href="<?= htmlspecialchars(app_path('/company-profile'), ENT_QUOTES, 'UTF-8'); ?>">Completer le profil entreprise</a></p>
        <?php endif; ?>

        <p><a href="<?= htmlspecialchars(app_path('/'), ENT_QUOTES, 'UTF-8'); ?>">Retour a l'accueil</a></p>
    </main>
</body>
</html>
