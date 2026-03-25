<?php

declare(strict_types=1);

$currentUser = $currentUser ?? null;
$thread = $thread ?? null;
$messages = is_array($messages ?? null) ? $messages : [];
$messageDraft = (string) ($messageDraft ?? '');
$canReply = !empty($canReply);
$backUrl = app_path('/');

if (($currentUser['role'] ?? '') === 'student') {
    $backUrl = app_path('/my-applications');
} elseif (in_array(($currentUser['role'] ?? ''), ['company', 'parent'], true)) {
    $backUrl = app_path('/company-applications');
} elseif (in_array(($currentUser['role'] ?? ''), ['teacher', 'level_manager', 'admin'], true)) {
    $backUrl = app_path('/admin/dashboard');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Discussion de candidature', ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_path('app.css'), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="page-student">
    <main class="page-shell">
        <nav class="top-nav surface">
            <div class="nav-links">
                <a class="nav-link" href="<?= htmlspecialchars(app_path('/'), ENT_QUOTES, 'UTF-8'); ?>">Accueil</a>
                <a class="nav-link" href="<?= htmlspecialchars(app_path('/news'), ENT_QUOTES, 'UTF-8'); ?>">Mes news</a>
                <a class="nav-link nav-link-current" href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8'); ?>">Discussion</a>
            </div>
            <?php if ($currentUser !== null): ?>
                <form class="inline-form" method="post" action="<?= htmlspecialchars(app_path('/logout'), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="button-secondary">Me deconnecter</button>
                </form>
            <?php endif; ?>
        </nav>

        <?php if (!empty($error)): ?>
            <p class="message message-error" style="margin-top: 1rem;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <p class="message message-success" style="margin-top: 1rem;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if ($thread === null): ?>
            <div class="empty-state" style="margin-top: 1.5rem;">
                Cette discussion n'est pas disponible.
            </div>
        <?php else: ?>
            <?php
            $statusLabel = \App\Controllers\InternshipController::applicationStatusLabels()[(string) ($thread['status'] ?? 'new')] ?? (string) ($thread['status'] ?? 'new');
            $offerStatus = (string) ($thread['internship_status'] ?? '');
            ?>
            <section class="hero hero-split" style="margin-top: 1rem;">
                <div class="hero-copy">
                    <p class="eyebrow">Discussion securisee</p>
                    <h1 class="hero-title"><?= htmlspecialchars((string) ($thread['internship_title'] ?? 'Candidature'), ENT_QUOTES, 'UTF-8'); ?></h1>
                    <p class="hero-text">Tous les echanges passent ici. Les adresses email des eleves ne sont jamais partagees avec l'entreprise.</p>
                </div>
                <aside class="hero-panel">
                    <ul class="info-list">
                        <li><strong>Entreprise :</strong> <?= htmlspecialchars((string) ($thread['company_label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></li>
                        <li><strong>Classe :</strong> <?= htmlspecialchars((string) ($thread['classe'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></li>
                        <li><strong>Statut candidature :</strong> <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?></li>
                        <li><strong>Statut offre :</strong> <?= htmlspecialchars($offerStatus, ENT_QUOTES, 'UTF-8'); ?></li>
                    </ul>
                </aside>
            </section>

            <section class="surface" style="margin-top: 1.5rem;">
                <h2 class="section-title">Fil de discussion</h2>
                <?php if ($messages === []): ?>
                    <div class="empty-state">Aucun message dans cette discussion pour le moment.</div>
                <?php else: ?>
                    <div class="results-grid">
                        <?php foreach ($messages as $messageRow): ?>
                            <article class="offer-card">
                                <p class="eyebrow">
                                    <?= htmlspecialchars((string) ($messageRow['sender_label'] ?? 'Message'), ENT_QUOTES, 'UTF-8'); ?>
                                    - <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $messageRow['created_at'])), ENT_QUOTES, 'UTF-8'); ?>
                                </p>
                                <p class="offer-description"><?= nl2br(htmlspecialchars((string) $messageRow['body'], ENT_QUOTES, 'UTF-8')); ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <?php if ($canReply): ?>
                <section class="surface" style="margin-top: 1.5rem;">
                    <h2 class="section-title">Repondre dans la webapp</h2>
                    <p class="section-copy">Ecris ici ton message. Il sera visible uniquement dans Avenir Pro.</p>
                    <form method="post" action="<?= htmlspecialchars(app_path('/applications/' . (string) $thread['id'] . '/messages'), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="field-group">
                            <label for="body">Message</label>
                            <textarea id="body" name="body" rows="8" required><?= htmlspecialchars($messageDraft, ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                        <div class="inline-actions">
                            <button type="submit">Envoyer le message</button>
                            <a class="button-secondary" href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8'); ?>">Retour</a>
                        </div>
                    </form>
                </section>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</body>
</html>
