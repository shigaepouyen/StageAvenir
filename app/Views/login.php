<?php

declare(strict_types=1);

$returnTo = (string) ($returnTo ?? '/');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Connexion', ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_path('app.css'), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="page-login">
    <main class="page-shell">
        <section class="hero hero-split">
            <div class="hero-copy">
                <p class="eyebrow">Connexion eleve</p>
                <h1 class="hero-title">Entre ton email. On t'envoie un lien. C'est tout.</h1>
                <p class="hero-text">Pas besoin de mot de passe a retenir. Tu reçois un email avec un lien de connexion valable pendant quelques minutes.</p>
                <ol class="step-list">
                    <li><span class="step-index">1</span>Tu saisis ton adresse email.</li>
                    <li><span class="step-index">2</span>Tu ouvres le mail recu.</li>
                    <li><span class="step-index">3</span>Tu cliques sur le lien pour revenir dans Avenir Pro.</li>
                </ol>
                <p class="student-note">Utilise une boite mail que tu consultes facilement avec tes parents si besoin.</p>
            </div>

            <aside class="hero-panel login-panel">
                <h2 class="section-title">Recevoir mon lien de connexion</h2>
                <p class="form-help">Entre ton email scolaire ou personnel. Si ton compte n'existe pas encore, il sera cree automatiquement.</p>

                <?php if (!empty($error)): ?>
                    <p class="message message-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <p class="message message-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>

                <form method="post" action="<?= htmlspecialchars(app_path('/login'), ENT_QUOTES, 'UTF-8'); ?>">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>"
                    >
                    <input
                        type="hidden"
                        name="return_to"
                        value="<?= htmlspecialchars($returnTo, ENT_QUOTES, 'UTF-8'); ?>"
                    >
                    <div class="field-group">
                        <label for="email">Mon adresse email</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            required
                            autocomplete="email"
                            placeholder="ex. prenom.nom@email.fr"
                            value="<?= htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        >
                    </div>
                    <div class="inline-actions">
                        <button type="submit">Recevoir mon lien</button>
                        <a class="button-ghost" href="<?= htmlspecialchars(app_path('/search'), ENT_QUOTES, 'UTF-8'); ?>">Voir les offres d'abord</a>
                        <a class="button-ghost" href="<?= htmlspecialchars(app_path('/help'), ENT_QUOTES, 'UTF-8'); ?>">Besoin d'aide ?</a>
                    </div>
                </form>
            </aside>
        </section>

        <p class="top-link"><a href="<?= htmlspecialchars(app_path('/'), ENT_QUOTES, 'UTF-8'); ?>">Retour a l'accueil</a></p>
    </main>
</body>
</html>
