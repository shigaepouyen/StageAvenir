<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Reactivation', ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_path('app.css'), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="page-company">
    <main class="page-shell">
        <section class="hero" style="margin-top: 1rem;">
            <p class="eyebrow">Campagne de reveil</p>
            <h1 class="hero-title"><?= htmlspecialchars($title ?? 'Reactivation', ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="message <?= !empty($success) ? 'message-success' : 'message-error'; ?>" style="margin-top: 1rem;">
                <?= htmlspecialchars($message ?? '', ENT_QUOTES, 'UTF-8'); ?>
            </p>
            <div class="inline-actions">
                <a class="button-secondary" href="<?= htmlspecialchars(app_path('/'), ENT_QUOTES, 'UTF-8'); ?>">Retour a l'accueil</a>
            </div>
        </section>
    </main>
</body>
</html>
