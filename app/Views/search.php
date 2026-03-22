<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Recherche de stages', ENT_QUOTES, 'UTF-8'); ?></title>
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
        crossorigin=""
    >
    <style>
        #search-map { height: 360px; margin: 1rem 0; }
    </style>
</head>
<body>
    <main>
        <h1><?= htmlspecialchars($title ?? 'Recherche de stages', ENT_QUOTES, 'UTF-8'); ?></h1>

        <form method="get" action="/search">
            <label for="q">Mots-cles</label>
            <input
                id="q"
                name="q"
                type="text"
                value="<?= htmlspecialchars($keyword ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            >

            <label for="origin_lat">Latitude eleve / college</label>
            <input
                id="origin_lat"
                name="origin_lat"
                type="text"
                value="<?= htmlspecialchars($originLat ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            >

            <label for="origin_lng">Longitude eleve / college</label>
            <input
                id="origin_lng"
                name="origin_lng"
                type="text"
                value="<?= htmlspecialchars($originLng ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            >

            <fieldset>
                <legend>Secteurs</legend>
                <label>
                    <input type="radio" name="tag" value="" <?= ($selectedTag ?? '') === '' ? 'checked' : ''; ?>>
                    Tous
                </label>
                <?php foreach ($availableTags as $tag): ?>
                    <label>
                        <input
                            type="radio"
                            name="tag"
                            value="<?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?>"
                            <?= ($selectedTag ?? '') === $tag ? 'checked' : ''; ?>
                        >
                        <?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?>
                    </label>
                <?php endforeach; ?>
            </fieldset>

            <button type="submit">Rechercher</button>
        </form>

        <?php if ($markers !== []): ?>
            <div id="search-map"></div>
        <?php else: ?>
            <p>La carte s'affichera lorsque des offres geolocalisees seront disponibles.</p>
        <?php endif; ?>

        <?php if ($items === []): ?>
            <p>Aucune offre active ne correspond a votre recherche.</p>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
                <article style="margin-bottom: 1rem; padding: 1rem; border: 1px solid #ccc;">
                    <h2><?= htmlspecialchars((string) $item['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p>Lieu : <?= htmlspecialchars((string) ($item['company_address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                    <p>Places : <?= htmlspecialchars((string) $item['places_count'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p>Secteur : <?= htmlspecialchars((string) ($item['sector_tag'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                    <p>
                        Distance :
                        <?php if (isset($item['distance_km']) && $item['distance_km'] !== null): ?>
                            <?= htmlspecialchars((string) $item['distance_km'], ENT_QUOTES, 'UTF-8'); ?> km
                        <?php else: ?>
                            non disponible
                        <?php endif; ?>
                    </p>
                    <p><a href="/offers/<?= htmlspecialchars((string) $item['id'], ENT_QUOTES, 'UTF-8'); ?>?origin_lat=<?= htmlspecialchars((string) ($originLat ?? ''), ENT_QUOTES, 'UTF-8'); ?>&origin_lng=<?= htmlspecialchars((string) ($originLng ?? ''), ENT_QUOTES, 'UTF-8'); ?>">Voir le detail</a></p>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>

        <p><a href="/">Retour a l'accueil</a></p>
    </main>
    <?php if ($markers !== []): ?>
        <script
            src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""
        ></script>
        <script>
            const searchMarkers = <?= json_encode($markers, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            const searchMap = L.map('search-map');
            const bounds = [];
            const escapeHtml = (value) => {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            };

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(searchMap);

            searchMarkers.forEach((marker) => {
                const leafletMarker = L.marker([marker.lat, marker.lng]).addTo(searchMap);
                const distanceText = marker.distance === null ? 'Distance non disponible' : `Distance : ${marker.distance} km`;
                leafletMarker.bindPopup(`<strong>${escapeHtml(marker.title)}</strong><br>${escapeHtml(marker.address || '')}<br>${escapeHtml(distanceText)}`);
                bounds.push([marker.lat, marker.lng]);
            });

            if (bounds.length > 0) {
                searchMap.fitBounds(bounds, { padding: [30, 30] });
            } else {
                searchMap.setView([46.2276, 2.2137], 5);
            }
        </script>
    <?php endif; ?>
</body>
</html>
