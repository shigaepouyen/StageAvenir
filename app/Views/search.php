<?php

declare(strict_types=1);

$keyword = (string) ($keyword ?? '');
$selectedTag = (string) ($selectedTag ?? '');
$originLat = (string) ($originLat ?? '');
$originLng = (string) ($originLng ?? '');
$locationReady = $originLat !== '' && $originLng !== '';
$resultsCount = count($items);
$currentUser = $currentUser ?? null;
$searchReturnTo = '/search' . ($keyword !== '' || $selectedTag !== '' || $originLat !== '' || $originLng !== ''
    ? '?' . http_build_query([
        'q' => $keyword,
        'tag' => $selectedTag,
        'origin_lat' => $originLat,
        'origin_lng' => $originLng,
    ])
    : '');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Recherche de stages', ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_path('app.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
        crossorigin=""
    >
</head>
<body class="page-search">
    <main class="page-shell">
        <nav class="top-nav surface">
            <div class="nav-links">
                <a class="nav-link" href="<?= htmlspecialchars(app_path('/'), ENT_QUOTES, 'UTF-8'); ?>">Accueil</a>
                <a class="nav-link nav-link-current" href="<?= htmlspecialchars(app_path('/search'), ENT_QUOTES, 'UTF-8'); ?>">Recherche</a>
                <a class="nav-link" href="<?= htmlspecialchars(app_path('/help'), ENT_QUOTES, 'UTF-8'); ?>">Aide</a>
                <?php if (($currentUser['role'] ?? '') === 'student'): ?>
                    <a class="nav-link" href="<?= htmlspecialchars(app_path('/my-applications'), ENT_QUOTES, 'UTF-8'); ?>">Mes candidatures</a>
                <?php endif; ?>
            </div>
            <?php if ($currentUser === null): ?>
                <a class="button-secondary" href="<?= htmlspecialchars(app_path('/login?' . http_build_query(['return_to' => $searchReturnTo])), ENT_QUOTES, 'UTF-8'); ?>">Me connecter</a>
            <?php else: ?>
                <form class="inline-form" method="post" action="<?= htmlspecialchars(app_path('/logout'), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="button-secondary">Me deconnecter</button>
                </form>
            <?php endif; ?>
        </nav>

        <section class="hero">
            <div class="hero-copy">
                <p class="eyebrow">Recherche eleve</p>
                <h1 class="hero-title">Cherche un stage pres de toi ou dans un domaine qui t'attire.</h1>
                <p class="hero-text">Tu peux filtrer par secteur, utiliser ta position pour voir les offres proches et ouvrir chaque fiche avant de candidater.</p>
            </div>
        </section>

        <section class="surface search-panel" style="margin-top: 1.4rem;">
            <form method="get" action="<?= htmlspecialchars(app_path('/search'), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="field-grid">
                    <div class="field-group field-span-2">
                        <label for="q">Qu'est-ce qui t'interesse ?</label>
                        <input
                            id="q"
                            name="q"
                            type="text"
                            placeholder="ex. animaux, informatique, accueil, soins..."
                            value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>"
                        >
                    </div>
                </div>

                <div class="location-panel" style="margin-top: 1.2rem;">
                    <div>
                        <h2 class="section-title">Distance</h2>
                        <p class="form-help">Optionnel. Clique sur le bouton ci-dessous pour faire remonter les offres proches de toi.</p>
                    </div>
                    <div class="inline-actions" style="margin-top: 0;">
                        <button type="button" class="button-secondary" id="use-browser-location">Utiliser ma position</button>
                        <button type="button" class="button-ghost" id="clear-browser-location">Effacer</button>
                    </div>
                </div>

                <p id="location-status" class="location-status">
                    <?php if ($locationReady): ?>
                        Position enregistree. Les offres proches sont triees en premier.
                    <?php else: ?>
                        Aucune position enregistree. Tu peux quand meme chercher librement.
                    <?php endif; ?>
                </p>

                <details class="manual-panel">
                    <summary>Je saisis mes coordonnees moi-meme</summary>
                    <div class="field-grid">
                        <div class="field-group">
                            <label for="origin_lat">Latitude</label>
                            <input
                                id="origin_lat"
                                name="origin_lat"
                                type="text"
                                inputmode="decimal"
                                value="<?= htmlspecialchars($originLat, ENT_QUOTES, 'UTF-8'); ?>"
                            >
                        </div>
                        <div class="field-group">
                            <label for="origin_lng">Longitude</label>
                            <input
                                id="origin_lng"
                                name="origin_lng"
                                type="text"
                                inputmode="decimal"
                                value="<?= htmlspecialchars($originLng, ENT_QUOTES, 'UTF-8'); ?>"
                            >
                        </div>
                    </div>
                </details>

                <fieldset class="choice-group" style="margin-top: 1.3rem;">
                    <legend>Secteurs</legend>
                    <div class="pill-row">
                        <label class="choice-pill">
                            <input type="radio" name="tag" value="" <?= $selectedTag === '' ? 'checked' : ''; ?>>
                            Tous
                        </label>
                        <?php foreach ($availableTags as $tag): ?>
                            <label class="choice-pill">
                                <input
                                    type="radio"
                                    name="tag"
                                    value="<?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?>"
                                    <?= $selectedTag === $tag ? 'checked' : ''; ?>
                                >
                                <?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <div class="inline-actions">
                    <button type="submit">Afficher les offres</button>
                    <a class="button-secondary" href="<?= htmlspecialchars(app_path('/offers'), ENT_QUOTES, 'UTF-8'); ?>">Voir toutes les offres actives</a>
                </div>
            </form>
        </section>

        <section class="results-header">
            <div>
                <p class="eyebrow">Resultats</p>
                <h2 class="section-title"><?= htmlspecialchars((string) $resultsCount, ENT_QUOTES, 'UTF-8'); ?> offre(s) a explorer</h2>
            </div>
            <?php if ($locationReady): ?>
                <span class="status-pill">Tri par distance active</span>
            <?php endif; ?>
        </section>

        <?php if ($items === []): ?>
            <div class="empty-state">
                Aucune offre active ne correspond a cette recherche pour le moment. Essaie un autre mot-cle ou un autre secteur.
            </div>
        <?php else: ?>
            <section class="results-grid">
                <?php foreach ($items as $item): ?>
                    <article class="offer-card">
                        <div class="offer-card-top">
                            <span class="stat-badge"><?= htmlspecialchars((string) $item['places_count'], ENT_QUOTES, 'UTF-8'); ?> place(s)</span>
                            <?php if (isset($item['distance_km']) && $item['distance_km'] !== null): ?>
                                <span class="stat-badge stat-badge-soft"><?= htmlspecialchars((string) $item['distance_km'], ENT_QUOTES, 'UTF-8'); ?> km</span>
                            <?php endif; ?>
                        </div>
                        <h3><?= htmlspecialchars((string) $item['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p class="offer-excerpt"><?= htmlspecialchars((string) ($item['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        <ul class="offer-meta">
                            <li><strong>Lieu :</strong> <?= htmlspecialchars((string) ($item['company_address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></li>
                            <li><strong>Secteur :</strong> <?= htmlspecialchars((string) ($item['sector_tag'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></li>
                            <li>
                                <strong>Distance :</strong>
                                <?php if (isset($item['distance_km']) && $item['distance_km'] !== null): ?>
                                    <?= htmlspecialchars((string) $item['distance_km'], ENT_QUOTES, 'UTF-8'); ?> km
                                <?php else: ?>
                                    non disponible
                                <?php endif; ?>
                            </li>
                        </ul>
                        <div class="inline-actions">
                            <a class="button" href="<?= htmlspecialchars(app_path('/offers/' . (string) $item['id']), ENT_QUOTES, 'UTF-8'); ?>?origin_lat=<?= htmlspecialchars($originLat, ENT_QUOTES, 'UTF-8'); ?>&origin_lng=<?= htmlspecialchars($originLng, ENT_QUOTES, 'UTF-8'); ?>">Voir l'offre</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <section class="surface map-shell">
            <h2 class="section-title">Carte des offres</h2>
            <p class="section-copy">La carte t'aide a visualiser les offres geolocalisees. Si une offre n'a pas encore de coordonnees, elle reste visible dans la liste.</p>
            <?php if ($markers !== []): ?>
                <div id="search-map"></div>
            <?php else: ?>
                <div class="empty-state">La carte s'affichera lorsque des offres geolocalisees seront disponibles.</div>
            <?php endif; ?>
        </section>

        <p class="top-link"><a href="<?= htmlspecialchars(app_path('/'), ENT_QUOTES, 'UTF-8'); ?>">Retour a l'accueil</a></p>
    </main>
    <script>
        const locationButton = document.getElementById('use-browser-location');
        const clearLocationButton = document.getElementById('clear-browser-location');
        const originLatInput = document.getElementById('origin_lat');
        const originLngInput = document.getElementById('origin_lng');
        const locationStatus = document.getElementById('location-status');

        if (locationButton && originLatInput && originLngInput && locationStatus) {
            locationButton.addEventListener('click', () => {
                if (!navigator.geolocation) {
                    locationStatus.textContent = "La geolocalisation n'est pas disponible sur cet appareil.";
                    return;
                }

                locationStatus.textContent = 'Recherche de ta position...';

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        originLatInput.value = position.coords.latitude.toFixed(6);
                        originLngInput.value = position.coords.longitude.toFixed(6);
                        locationStatus.textContent = 'Position enregistree. Clique sur "Afficher les offres" pour mettre a jour le tri.';
                    },
                    () => {
                        locationStatus.textContent = "Impossible de recuperer ta position. Tu peux continuer sans ce filtre.";
                    },
                    {
                        enableHighAccuracy: false,
                        timeout: 7000,
                        maximumAge: 300000
                    }
                );
            });
        }

        if (clearLocationButton && originLatInput && originLngInput && locationStatus) {
            clearLocationButton.addEventListener('click', () => {
                originLatInput.value = '';
                originLngInput.value = '';
                locationStatus.textContent = "Aucune position enregistree. Tu peux quand meme chercher librement.";
            });
        }
    </script>
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
