<?php

declare(strict_types=1);

$originLat = (string) ($_GET['origin_lat'] ?? '');
$originLng = (string) ($_GET['origin_lng'] ?? '');
$distanceText = isset($internship['distance_km']) && $internship['distance_km'] !== null
    ? (string) $internship['distance_km'] . ' km'
    : 'non disponible';
$currentOfferReturnTo = '/offers/' . (string) (($internship['id'] ?? $_GET['id'] ?? '') ?: '') . '?' . http_build_query([
    'origin_lat' => $originLat,
    'origin_lng' => $originLng,
]) . '#apply';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Offre de stage', ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_path('app.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
        crossorigin=""
    >
</head>
<body class="page-detail">
    <main class="page-shell">
        <nav class="top-nav surface">
            <div class="nav-links">
                <a class="nav-link" href="<?= htmlspecialchars(app_path('/'), ENT_QUOTES, 'UTF-8'); ?>">Accueil</a>
                <a class="nav-link" href="<?= htmlspecialchars(app_path('/search'), ENT_QUOTES, 'UTF-8'); ?>">Recherche</a>
                <a class="nav-link" href="<?= htmlspecialchars(app_path('/help'), ENT_QUOTES, 'UTF-8'); ?>">Aide</a>
                <?php if (($currentUser['role'] ?? '') === 'student'): ?>
                    <a class="nav-link" href="<?= htmlspecialchars(app_path('/my-applications'), ENT_QUOTES, 'UTF-8'); ?>">Mes candidatures</a>
                <?php endif; ?>
            </div>
            <?php if ($currentUser === null): ?>
                <a class="button-secondary" href="<?= htmlspecialchars(app_path('/login?' . http_build_query(['return_to' => $currentOfferReturnTo])), ENT_QUOTES, 'UTF-8'); ?>">Me connecter</a>
            <?php else: ?>
                <form class="inline-form" method="post" action="<?= htmlspecialchars(app_path('/logout'), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="button-secondary">Me deconnecter</button>
                </form>
            <?php endif; ?>
        </nav>

        <?php if (!isset($internship) || $internship === null): ?>
            <div class="empty-state">
                <h1 class="section-title">Offre introuvable</h1>
                <p>Cette offre n'est pas disponible ou n'est plus visible pour les eleves.</p>
            </div>
        <?php else: ?>
            <section class="hero">
                <div class="hero-copy">
                    <p class="eyebrow">Offre de stage</p>
                    <h1 class="hero-title"><?= htmlspecialchars((string) $internship['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
                    <p class="hero-text">Lis tranquillement la fiche, verifie le lieu et les places disponibles, puis candidate quand tu te sens pret.</p>
                    <div class="offer-card-top">
                        <span class="stat-badge"><?= htmlspecialchars((string) $internship['places_count'], ENT_QUOTES, 'UTF-8'); ?> place(s)</span>
                        <span class="stat-badge stat-badge-soft"><?= htmlspecialchars((string) ($internship['sector_tag'] ?? 'Secteur non precise'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="stat-badge stat-badge-soft"><?= htmlspecialchars($distanceText, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                </div>
            </section>

            <section class="detail-layout" style="margin-top: 1.4rem;">
                <article class="surface detail-main">
                    <h2 class="section-title">Ce que tu vas decouvrir</h2>
                    <div class="offer-description"><?= nl2br(htmlspecialchars((string) $internship['description'], ENT_QUOTES, 'UTF-8')); ?></div>

                    <div class="field-grid" style="margin-top: 1.4rem;">
                        <div class="mini-card">
                            <h3>Entreprise</h3>
                            <p class="section-copy"><?= htmlspecialchars((string) ($internship['company_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <div class="mini-card">
                            <h3>Lieu</h3>
                            <p class="section-copy"><?= htmlspecialchars((string) ($internship['company_address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                    </div>

                    <section class="map-shell">
                        <h2 class="section-title">Ou se passe le stage ?</h2>
                        <p class="section-copy">La carte t'aide a visualiser l'emplacement de l'entreprise si les coordonnees sont disponibles.</p>
                        <?php if ($markers !== []): ?>
                            <div id="detail-map"></div>
                        <?php else: ?>
                            <div class="empty-state">Coordonnees geographiques indisponibles pour cette offre.</div>
                        <?php endif; ?>
                    </section>
                </article>

                <aside class="detail-side">
                    <section class="surface sticky-panel">
                        <p class="eyebrow">Infos pratiques</p>
                        <h2 class="section-title">A retenir</h2>
                        <ul class="info-list">
                            <li><strong>Entreprise :</strong> <?= htmlspecialchars((string) ($internship['company_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></li>
                            <li><strong>Lieu :</strong> <?= htmlspecialchars((string) ($internship['company_address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></li>
                            <li><strong>Places :</strong> <?= htmlspecialchars((string) $internship['places_count'], ENT_QUOTES, 'UTF-8'); ?></li>
                            <li><strong>Annee scolaire :</strong> <?= htmlspecialchars((string) $internship['academic_year'], ENT_QUOTES, 'UTF-8'); ?></li>
                            <li><strong>Distance :</strong> <?= htmlspecialchars($distanceText, ENT_QUOTES, 'UTF-8'); ?></li>
                        </ul>
                    </section>

                    <section class="surface apply-panel" id="apply">
                        <p class="eyebrow">Candidature</p>
                        <h2 class="section-title">Postuler a cette offre</h2>
                        <p class="form-help">Ton message n'a pas besoin d'etre parfait. Quelques lignes claires suffisent.</p>

                        <?php if (!empty($applicationError)): ?>
                            <p class="message message-error"><?= htmlspecialchars($applicationError, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>

                        <?php if (!empty($applicationSuccess)): ?>
                            <p class="message message-success"><?= htmlspecialchars($applicationSuccess, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>

                        <?php if (!isset($currentUser) || $currentUser === null): ?>
                            <div class="inline-actions">
                                <a class="button" href="<?= htmlspecialchars(app_path('/login?' . http_build_query(['return_to' => $currentOfferReturnTo])), ENT_QUOTES, 'UTF-8'); ?>">Me connecter pour candidater</a>
                            </div>
                            <p class="student-note">Tu pourras revenir ici juste apres la connexion.</p>
                        <?php elseif (($currentUser['role'] ?? '') !== 'student'): ?>
                            <div class="empty-state">La candidature est reservee aux eleves.</div>
                        <?php else: ?>
                            <form id="application-form" method="post" action="<?= htmlspecialchars(app_path('/offers/' . (string) $internship['id'] . '/apply'), ENT_QUOTES, 'UTF-8'); ?>">
                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>"
                                >
                                <div class="field-group">
                                    <label for="message">Pourquoi ce stage t'interesse ?</label>
                                    <textarea
                                        id="message"
                                        name="message"
                                        rows="8"
                                        required
                                        placeholder="Exemple : J'aimerais decouvrir ce metier, voir le quotidien de l'equipe et mieux comprendre ce secteur."
                                    ><?= htmlspecialchars((string) ($applicationData['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                </div>

                                <div class="field-group" style="margin-top: 1rem;">
                                    <label for="classe">Ta classe</label>
                                    <input
                                        id="classe"
                                        name="classe"
                                        type="text"
                                        list="class-options"
                                        placeholder="ex. 3e B"
                                        required
                                        value="<?= htmlspecialchars((string) ($applicationData['classe'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    >
                                    <datalist id="class-options">
                                        <option value="3e"></option>
                                        <option value="3e A"></option>
                                        <option value="3e B"></option>
                                        <option value="3e C"></option>
                                    </datalist>
                                </div>

                                <input type="hidden" name="origin_lat" value="<?= htmlspecialchars($originLat, ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="origin_lng" value="<?= htmlspecialchars($originLng, ENT_QUOTES, 'UTF-8'); ?>">

                                <div class="inline-actions">
                                    <button type="submit">Envoyer ma candidature</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </section>
                </aside>
            </section>
        <?php endif; ?>

        <p class="top-link"><a href="<?= htmlspecialchars(app_path('/search'), ENT_QUOTES, 'UTF-8'); ?>?origin_lat=<?= htmlspecialchars($originLat, ENT_QUOTES, 'UTF-8'); ?>&origin_lng=<?= htmlspecialchars($originLng, ENT_QUOTES, 'UTF-8'); ?>">Retour a la recherche</a></p>
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
