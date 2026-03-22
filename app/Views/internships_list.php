<?php

declare(strict_types=1);
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
        <h1><?= htmlspecialchars($title ?? 'Mes offres de stage', ENT_QUOTES, 'UTF-8'); ?></h1>

        <?php if (!empty($error)): ?>
            <p style="color: #b00020;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <p style="color: #0a5f2b;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if (!empty($company)): ?>
            <p><a href="/internships/create">Ajouter une offre</a></p>

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
                        <p>Annee scolaire : <?= htmlspecialchars((string) $item['academic_year'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php if ((string) $item['status'] === 'active'): ?>
                            <form method="post" action="/internships/<?= htmlspecialchars((string) $item['id'], ENT_QUOTES, 'UTF-8'); ?>/sleep">
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
            <p><a href="/company-profile">Completer le profil entreprise</a></p>
        <?php endif; ?>

        <p><a href="/">Retour a l'accueil</a></p>
    </main>
</body>
</html>
