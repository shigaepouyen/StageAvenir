<?php

declare(strict_types=1);

$companiesItems = is_array($companiesItems ?? null) ? $companiesItems : [];
$moderationItems = is_array($moderationItems ?? null) ? $moderationItems : [];
$archivedItems = is_array($archivedItems ?? null) ? $archivedItems : [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Moderation entreprises et offres', ENT_QUOTES, 'UTF-8'); ?></title>
</head>
<body>
    <main>
        <p><a href="<?= htmlspecialchars(app_path('/news'), ENT_QUOTES, 'UTF-8'); ?>">Voir mes news</a></p>
        <p><a href="<?= htmlspecialchars(app_path('/admin/dashboard'), ENT_QUOTES, 'UTF-8'); ?>">Voir le tableau college</a></p>
        <h1><?= htmlspecialchars($title ?? 'Moderation entreprises et offres', ENT_QUOTES, 'UTF-8'); ?></h1>

        <?php if (!empty($error)): ?>
            <p style="color: #b00020;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <p style="color: #0a5f2b;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if (empty($accessDenied)): ?>
            <section>
                <h2>Entreprises a moderer</h2>
                <?php if ($companiesItems === []): ?>
                    <p>Aucune entreprise a moderer.</p>
                <?php else: ?>
                    <?php foreach ($companiesItems as $item): ?>
                        <article style="margin-bottom: 1rem; padding: 1rem; border: 1px solid #ccc;">
                            <h3><?= htmlspecialchars((string) ($item['name'] ?? $item['siret'] ?? 'Entreprise'), ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p>Email responsable : <?= htmlspecialchars((string) ($item['owner_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            <p>SIRET : <?= htmlspecialchars((string) ($item['siret'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            <p>Code NAF : <?= htmlspecialchars((string) ($item['naf_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            <p>Adresse : <?= htmlspecialchars((string) ($item['address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            <p>Validation : <?= htmlspecialchars(\App\Controllers\InternshipController::validationStatusLabels()[(string) ($item['validation_status'] ?? 'pending')] ?? (string) ($item['validation_status'] ?? 'pending'), ENT_QUOTES, 'UTF-8'); ?></p>
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <form method="post" action="<?= htmlspecialchars(app_path('/admin/companies/' . (string) $item['id'] . '/approve'), ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit">Valider l'entreprise</button>
                                </form>
                                <form method="post" action="<?= htmlspecialchars(app_path('/admin/companies/' . (string) $item['id'] . '/reject'), ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit">Refuser l'entreprise</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <section>
                <h2>Offres a moderer</h2>
                <?php if ($moderationItems === []): ?>
                    <p>Aucune offre a moderer.</p>
                <?php else: ?>
                    <?php foreach ($moderationItems as $item): ?>
                        <article style="margin-bottom: 1rem; padding: 1rem; border: 1px solid #ccc;">
                            <h3><?= htmlspecialchars((string) $item['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p>Entreprise : <?= htmlspecialchars((string) ($item['company_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            <p>Statut offre : <?= htmlspecialchars((string) ($item['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            <p>Validation offre : <?= htmlspecialchars(\App\Controllers\InternshipController::validationStatusLabels()[(string) ($item['validation_status'] ?? 'pending')] ?? (string) ($item['validation_status'] ?? 'pending'), ENT_QUOTES, 'UTF-8'); ?></p>
                            <p>Validation entreprise : <?= htmlspecialchars(\App\Controllers\InternshipController::validationStatusLabels()[(string) ($item['company_validation_status'] ?? 'pending')] ?? (string) ($item['company_validation_status'] ?? 'pending'), ENT_QUOTES, 'UTF-8'); ?></p>
                            <p><?= nl2br(htmlspecialchars((string) ($item['description'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></p>
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <form method="post" action="<?= htmlspecialchars(app_path('/admin/internships/' . (string) $item['id'] . '/approve'), ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit">Valider l'offre</button>
                                </form>
                                <form method="post" action="<?= htmlspecialchars(app_path('/admin/internships/' . (string) $item['id'] . '/reject'), ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit">Refuser l'offre</button>
                                </form>
                                <form method="post" action="<?= htmlspecialchars(app_path('/admin/internships/' . (string) $item['id'] . '/archive'), ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit">Archiver</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <section>
                <h2>Offres archivees</h2>
                <?php if ($archivedItems === []): ?>
                    <p>Aucune offre archivee.</p>
                <?php else: ?>
                    <?php foreach ($archivedItems as $item): ?>
                        <article style="margin-bottom: 1rem; padding: 1rem; border: 1px solid #ccc;">
                            <h3><?= htmlspecialchars((string) $item['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p>Entreprise : <?= htmlspecialchars((string) ($item['company_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            <p>Statut : <?= htmlspecialchars((string) $item['status'], ENT_QUOTES, 'UTF-8'); ?></p>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <p><a href="<?= htmlspecialchars(app_path('/'), ENT_QUOTES, 'UTF-8'); ?>">Retour a l'accueil</a></p>
    </main>
</body>
</html>
