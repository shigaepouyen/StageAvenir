<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Administration des offres', ENT_QUOTES, 'UTF-8'); ?></title>
</head>
<body>
    <main>
        <h1><?= htmlspecialchars($title ?? 'Administration des offres', ENT_QUOTES, 'UTF-8'); ?></h1>

        <?php if (!empty($error)): ?>
            <p style="color: #b00020;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <p style="color: #0a5f2b;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if (empty($accessDenied)): ?>
            <section>
                <h2>Offres actives et invisibles</h2>
                <?php if ($activeAndSleepingItems === []): ?>
                    <p>Aucune offre a administrer.</p>
                <?php else: ?>
                    <?php foreach ($activeAndSleepingItems as $item): ?>
                        <article style="margin-bottom: 1rem; padding: 1rem; border: 1px solid #ccc;">
                            <h3><?= htmlspecialchars((string) $item['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p>Entreprise : <?= htmlspecialchars((string) ($item['company_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            <p>Statut : <?= htmlspecialchars((string) $item['status'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <form method="post" action="/admin/internships/<?= htmlspecialchars((string) $item['id'], ENT_QUOTES, 'UTF-8'); ?>/archive">
                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>"
                                >
                                <button type="submit">Archiver</button>
                            </form>
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

        <p><a href="/">Retour a l'accueil</a></p>
    </main>
</body>
</html>
