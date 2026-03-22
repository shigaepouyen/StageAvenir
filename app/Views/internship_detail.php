<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Offre de stage', ENT_QUOTES, 'UTF-8'); ?></title>
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
        crossorigin=""
    >
    <style>
        #detail-map { height: 320px; margin: 1rem 0; }
    </style>
</head>
<body>
    <main>
        <?php if (!isset($internship) || $internship === null): ?>
            <h1>Offre introuvable</h1>
            <p>Cette offre n'est pas disponible.</p>
        <?php else: ?>
            <h1><?= htmlspecialchars((string) $internship['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <p><?= nl2br(htmlspecialchars((string) $internship['description'], ENT_QUOTES, 'UTF-8')); ?></p>
            <p>Entreprise : <?= htmlspecialchars((string) ($internship['company_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            <p>Lieu : <?= htmlspecialchars((string) ($internship['company_address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            <p>Secteur : <?= htmlspecialchars((string) ($internship['sector_tag'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            <p>Places : <?= htmlspecialchars((string) $internship['places_count'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p>Annee scolaire : <?= htmlspecialchars((string) $internship['academic_year'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p>
                Distance :
                <?php if (isset($internship['distance_km']) && $internship['distance_km'] !== null): ?>
                    <?= htmlspecialchars((string) $internship['distance_km'], ENT_QUOTES, 'UTF-8'); ?> km
                <?php else: ?>
                    non disponible
                <?php endif; ?>
            </p>
            <?php if ($markers !== []): ?>
                <div id="detail-map"></div>
            <?php else: ?>
                <p>Coordonnees geographiques indisponibles pour cette offre.</p>
            <?php endif; ?>

            <section id="apply">
                <h2>Candidater</h2>

                <?php if (!empty($applicationError)): ?>
                    <p style="color: #b00020;"><?= htmlspecialchars($applicationError, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>

                <?php if (!empty($applicationSuccess)): ?>
                    <p style="color: #0a5f2b;"><?= htmlspecialchars($applicationSuccess, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>

                <?php if (!isset($currentUser) || $currentUser === null): ?>
                    <p><a href="/login">Connectez-vous pour candidater</a></p>
                <?php elseif (($currentUser['role'] ?? '') !== 'student'): ?>
                    <p>La candidature est reservee aux eleves.</p>
                <?php else: ?>
                    <p><a href="#application-form">Candidater</a></p>
                    <form id="application-form" method="post" action="/offers/<?= htmlspecialchars((string) $internship['id'], ENT_QUOTES, 'UTF-8'); ?>/apply">
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>"
                        >
                        <label for="message">Message de motivation</label>
                        <textarea
                            id="message"
                            name="message"
                            rows="8"
                            required
                        ><?= htmlspecialchars((string) ($applicationData['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>

                        <label for="classe">Classe</label>
                        <input
                            id="classe"
                            name="classe"
                            type="text"
                            required
                            value="<?= htmlspecialchars((string) ($applicationData['classe'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        >

                        <input type="hidden" name="origin_lat" value="<?= htmlspecialchars((string) ($_GET['origin_lat'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="origin_lng" value="<?= htmlspecialchars((string) ($_GET['origin_lng'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

                        <button type="submit">Envoyer ma candidature</button>
                    </form>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <p><a href="/search?origin_lat=<?= htmlspecialchars((string) ($_GET['origin_lat'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>&origin_lng=<?= htmlspecialchars((string) ($_GET['origin_lng'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">Retour a la recherche</a></p>
    </main>
    <?php if (!empty($markers)): ?>
        <script
            src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""
        ></script>
        <script>
            const detailMarkers = <?= json_encode($markers, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            const detailMap = L.map('detail-map');
            const detailBounds = [];
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
            }).addTo(detailMap);

            detailMarkers.forEach((marker) => {
                const leafletMarker = L.marker([marker.lat, marker.lng]).addTo(detailMap);
                const distanceText = marker.distance === null ? 'Distance non disponible' : `Distance : ${marker.distance} km`;
                leafletMarker.bindPopup(`<strong>${escapeHtml(marker.title)}</strong><br>${escapeHtml(marker.address || '')}<br>${escapeHtml(distanceText)}`);
                detailBounds.push([marker.lat, marker.lng]);
            });

            if (detailBounds.length > 0) {
                detailMap.fitBounds(detailBounds, { padding: [30, 30] });
            } else {
                detailMap.setView([46.2276, 2.2137], 5);
            }
        </script>
    <?php endif; ?>
</body>
</html>
