<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Offres de stage disponibles', ENT_QUOTES, 'UTF-8'); ?></title>
</head>
<body>
    <main>
        <h1><?= htmlspecialchars($title ?? 'Offres de stage disponibles', ENT_QUOTES, 'UTF-8'); ?></h1>

        <?php if ($items === []): ?>
            <p>Aucune offre active n'est disponible actuellement.</p>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
                <article style="margin-bottom: 1rem; padding: 1rem; border: 1px solid #ccc;">
                    <h2><?= htmlspecialchars((string) $item['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p><?= nl2br(htmlspecialchars((string) $item['description'], ENT_QUOTES, 'UTF-8')); ?></p>
                    <p>Entreprise : <?= htmlspecialchars((string) ($item['company_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                    <p>Adresse : <?= htmlspecialchars((string) ($item['company_address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                    <p>Secteur : <?= htmlspecialchars((string) ($item['sector_tag'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                    <p>Places : <?= htmlspecialchars((string) $item['places_count'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p>Annee scolaire : <?= htmlspecialchars((string) $item['academic_year'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p>
                        Distance :
                        <?php if (isset($item['distance_km']) && $item['distance_km'] !== null): ?>
                            <?= htmlspecialchars((string) $item['distance_km'], ENT_QUOTES, 'UTF-8'); ?> km
                        <?php else: ?>
                            non disponible
                        <?php endif; ?>
                    </p>
                    <p><a href="/offers/<?= htmlspecialchars((string) $item['id'], ENT_QUOTES, 'UTF-8'); ?>">Voir le detail</a></p>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>

        <p><a href="/">Retour a l'accueil</a></p>
    </main>
</body>
</html>
