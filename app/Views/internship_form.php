<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une offre</title>
</head>
<body>
    <main>
        <h1>Ajouter une offre</h1>

        <?php if (!empty($error)): ?>
            <p style="color: #b00020;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if (!empty($company)): ?>
            <form method="post" action="/internships/create">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>"
                >
                <label for="title">Titre</label>
                <input
                    id="title"
                    name="title"
                    type="text"
                    required
                    value="<?= htmlspecialchars((string) $formData['title'], ENT_QUOTES, 'UTF-8'); ?>"
                >

                <label for="description">Description</label>
                <textarea
                    id="description"
                    name="description"
                    rows="8"
                    required
                ><?= htmlspecialchars((string) $formData['description'], ENT_QUOTES, 'UTF-8'); ?></textarea>

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

                <label for="places_count">Nombre de places</label>
                <input
                    id="places_count"
                    name="places_count"
                    type="number"
                    min="1"
                    required
                    value="<?= htmlspecialchars((string) $formData['places_count'], ENT_QUOTES, 'UTF-8'); ?>"
                >

                <p>Annee scolaire : <?= htmlspecialchars((string) $formData['academic_year'], ENT_QUOTES, 'UTF-8'); ?></p>

                <label>
                    <input
                        type="checkbox"
                        name="certification"
                        value="1"
                        <?= $formData['certification'] === '1' ? 'checked' : ''; ?>
                    >
                    Je certifie que ce stage respecte la reglementation sur le travail des mineurs.
                </label>

                <button type="submit">Publier l'offre</button>
            </form>
        <?php else: ?>
            <p><a href="/company-profile">Completer le profil entreprise</a></p>
        <?php endif; ?>

        <p><a href="/internships">Retour a mes offres</a></p>
    </main>
</body>
</html>
