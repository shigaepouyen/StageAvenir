<?php

declare(strict_types=1);

$currentSiret = (string) ($company['siret'] ?? '');
$currentName = (string) ($company['name'] ?? '');
$currentAddress = (string) ($company['address'] ?? '');
$currentNafCode = (string) ($company['naf_code'] ?? '');
$currentLat = (string) ($company['lat'] ?? '');
$currentLng = (string) ($company['lng'] ?? '');
$searchQuery = (string) ($searchQuery ?? '');
$searchResults = is_array($searchResults ?? null) ? $searchResults : [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Profil entreprise', ENT_QUOTES, 'UTF-8'); ?></title>
</head>
<body>
    <main>
        <h1><?= htmlspecialchars($title ?? 'Profil entreprise', ENT_QUOTES, 'UTF-8'); ?></h1>

        <?php if (!empty($error)): ?>
            <p style="color: #b00020;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <p style="color: #0a5f2b;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if (empty($accessDenied)): ?>
            <section>
                <h2>Recherche Sirene</h2>
                <form method="post" action="<?= htmlspecialchars(app_path('/company-profile/search'), ENT_QUOTES, 'UTF-8'); ?>">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>"
                    >
                    <label for="search_query">Nom de l'entreprise ou SIRET</label>
                    <input
                        id="search_query"
                        name="search_query"
                        type="text"
                        required
                        value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?>"
                    >
                    <button type="submit">Rechercher</button>
                </form>
            </section>

            <?php if ($searchResults !== []): ?>
                <section>
                    <h2>Resultats</h2>
                    <?php foreach ($searchResults as $result): ?>
                        <article style="margin-bottom: 1rem; padding: 1rem; border: 1px solid #ccc;">
                            <p><strong><?= htmlspecialchars((string) ($result['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></p>
                            <p>SIRET : <?= htmlspecialchars((string) ($result['siret'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            <p>Adresse : <?= htmlspecialchars((string) ($result['address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            <p>Code NAF : <?= htmlspecialchars((string) ($result['naf_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            <form method="post" action="<?= htmlspecialchars(app_path('/company-profile/select'), ENT_QUOTES, 'UTF-8'); ?>">
                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>"
                                >
                                <input type="hidden" name="search_query" value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="selected_siret" value="<?= htmlspecialchars((string) ($result['siret'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit">Selectionner</button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>

            <section>
                <h2>Profil entreprise</h2>
            <form method="post" action="<?= htmlspecialchars(app_path('/company-profile'), ENT_QUOTES, 'UTF-8'); ?>">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>"
                >
                <label for="siret">SIRET</label>
                <input
                    id="siret"
                    name="siret"
                    type="text"
                    inputmode="numeric"
                    pattern="\d{14}"
                    maxlength="14"
                    required
                    value="<?= htmlspecialchars($currentSiret, ENT_QUOTES, 'UTF-8'); ?>"
                >

                <label for="name">Raison sociale</label>
                <input
                    id="name"
                    name="name"
                    type="text"
                    value="<?= htmlspecialchars($currentName, ENT_QUOTES, 'UTF-8'); ?>"
                >

                <label for="address">Adresse</label>
                <input
                    id="address"
                    name="address"
                    type="text"
                    value="<?= htmlspecialchars($currentAddress, ENT_QUOTES, 'UTF-8'); ?>"
                >

                <label for="naf_code">Code NAF</label>
                <input
                    id="naf_code"
                    name="naf_code"
                    type="text"
                    value="<?= htmlspecialchars($currentNafCode, ENT_QUOTES, 'UTF-8'); ?>"
                >

                <label for="lat">Latitude</label>
                <input
                    id="lat"
                    name="lat"
                    type="text"
                    value="<?= htmlspecialchars($currentLat, ENT_QUOTES, 'UTF-8'); ?>"
                >

                <label for="lng">Longitude</label>
                <input
                    id="lng"
                    name="lng"
                    type="text"
                    value="<?= htmlspecialchars($currentLng, ENT_QUOTES, 'UTF-8'); ?>"
                >

                <input type="hidden" name="search_query" value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit">Enregistrer</button>
            </form>
            <p>Format attendu : 14 chiffres, sans espaces.</p>
            </section>
        <?php endif; ?>

        <p><a href="<?= htmlspecialchars(app_path('/'), ENT_QUOTES, 'UTF-8'); ?>">Retour a l'accueil</a></p>
    </main>
</body>
</html>
