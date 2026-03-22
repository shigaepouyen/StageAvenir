<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Reactivation', ENT_QUOTES, 'UTF-8'); ?></title>
</head>
<body>
    <main>
        <h1><?= htmlspecialchars($title ?? 'Reactivation', ENT_QUOTES, 'UTF-8'); ?></h1>
        <p style="color: <?= !empty($success) ? '#0a5f2b' : '#b00020'; ?>;">
            <?= htmlspecialchars($message ?? '', ENT_QUOTES, 'UTF-8'); ?>
        </p>
        <p><a href="/">Retour a l'accueil</a></p>
    </main>
</body>
</html>
