<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
</head>
<body>
    <main>
        <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>
        <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php if (!isset($user) || $user === null): ?>
            <p><a href="/login">Se connecter</a></p>
        <?php else: ?>
            <p><a href="/offers">Voir les offres disponibles</a></p>
            <p><a href="/search">Rechercher un stage</a></p>
            <?php if (!empty($canManageCompanyProfile)): ?>
                <p><a href="/company-profile">Gerer le profil entreprise</a></p>
            <?php endif; ?>
            <?php if (!empty($canManageInternships)): ?>
                <p><a href="/internships">Voir mes offres</a></p>
            <?php endif; ?>
            <?php if (!empty($canAccessAdminInternships)): ?>
                <p><a href="/admin/internships">Administration des offres</a></p>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</body>
</html>
