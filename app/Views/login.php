<?php

declare(strict_types=1);

$returnTo = (string) ($returnTo ?? '/');
$selectedAccountType = (string) ($selectedAccountType ?? 'student');
$isCompanyPath = $selectedAccountType === 'company';
$studentLoginUrl = app_path('/login');
$companyLoginUrl = app_path('/login?account_type=company&return_to=' . rawurlencode('/company-profile'));
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
                <p class="eyebrow"><?= htmlspecialchars($isCompanyPath ? 'Connexion entreprise' : 'Connexion eleve', ENT_QUOTES, 'UTF-8'); ?></p>
                <h1 class="hero-title">
                    <?= htmlspecialchars($isCompanyPath ? 'Entrez votre email professionnel. On vous envoie un lien de connexion.' : "Entre ton email. On t'envoie un lien. C'est tout.", ENT_QUOTES, 'UTF-8'); ?>
                </h1>
                <p class="hero-text">
                    <?= htmlspecialchars($isCompanyPath
                        ? "Aucun mot de passe a memoriser. Si l'adresse n'existe pas encore, Avenir Pro cree automatiquement votre compte entreprise puis vous envoie un Magic Link. Le profil et les offres seront ensuite valides par l'administration."
                        : "Pas besoin de mot de passe a retenir. Tu reçois un email avec un lien de connexion valable pendant quelques minutes.", ENT_QUOTES, 'UTF-8'); ?>
                </p>
                <div class="journey-switch">
                    <a class="journey-card <?= $isCompanyPath ? '' : 'journey-card-current'; ?>" href="<?= htmlspecialchars($studentLoginUrl, ENT_QUOTES, 'UTF-8'); ?>">
                        <strong>Je suis un eleve</strong>
                        <span>Je cherche une offre puis je candidate.</span>
                    </a>
                    <a class="journey-card <?= $isCompanyPath ? 'journey-card-current' : ''; ?>" href="<?= htmlspecialchars($companyLoginUrl, ENT_QUOTES, 'UTF-8'); ?>">
                        <strong>Je suis une entreprise</strong>
                        <span>Je cree mon profil puis je publie une offre.</span>
                    </a>
                </div>
                <ol class="step-list">
                    <?php if ($isCompanyPath): ?>
                        <li><span class="step-index">1</span>Vous saisissez votre adresse email professionnelle.</li>
                        <li><span class="step-index">2</span>Vous ouvrez le lien recu par email.</li>
                        <li><span class="step-index">3</span>Vous completez votre profil entreprise puis vous ajoutez vos offres.</li>
                    <?php else: ?>
                        <li><span class="step-index">1</span>Tu saisis ton adresse email.</li>
                        <li><span class="step-index">2</span>Tu ouvres le mail recu.</li>
                        <li><span class="step-index">3</span>Tu cliques sur le lien pour revenir dans Avenir Pro.</li>
                    <?php endif; ?>
                </ol>
                <p class="student-note">
                    <?= htmlspecialchars($isCompanyPath
                        ? "Le premier lien vous emmene directement vers le profil entreprise pour preparer la publication de vos stages."
                        : 'Utilise une boite mail que tu consultes facilement avec tes parents si besoin.', ENT_QUOTES, 'UTF-8'); ?>
                </p>
            </div>

            <aside class="hero-panel login-panel">
                <h2 class="section-title"><?= htmlspecialchars($isCompanyPath ? 'Recevoir mon lien entreprise' : 'Recevoir mon lien de connexion', ENT_QUOTES, 'UTF-8'); ?></h2>
                <p class="form-help">
                    <?= htmlspecialchars($isCompanyPath
                        ? "Entrez l'adresse email de l'entreprise ou du parent referent. Si le compte n'existe pas encore, il sera cree en profil entreprise. La publication restera soumise a validation."
                        : "Entre ton email scolaire ou personnel. Si ton compte n'existe pas encore, il sera cree automatiquement.", ENT_QUOTES, 'UTF-8'); ?>
                </p>

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
                        name="account_type"
                        value="<?= htmlspecialchars($selectedAccountType, ENT_QUOTES, 'UTF-8'); ?>"
                    >
                    <input
                        type="hidden"
                        name="return_to"
                        value="<?= htmlspecialchars($returnTo, ENT_QUOTES, 'UTF-8'); ?>"
                    >
                    <div class="field-group">
                        <label for="email"><?= htmlspecialchars($isCompanyPath ? 'Adresse email de l entreprise' : 'Mon adresse email', ENT_QUOTES, 'UTF-8'); ?></label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            required
                            autocomplete="email"
                            placeholder="<?= htmlspecialchars($isCompanyPath ? 'ex. contact@entreprise.fr' : 'ex. prenom.nom@email.fr', ENT_QUOTES, 'UTF-8'); ?>"
                            value="<?= htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        >
                    </div>
                    <div class="inline-actions">
                        <button type="submit"><?= htmlspecialchars($isCompanyPath ? 'Recevoir mon lien entreprise' : 'Recevoir mon lien', ENT_QUOTES, 'UTF-8'); ?></button>
                        <?php if ($isCompanyPath): ?>
                            <a class="button-ghost" href="<?= htmlspecialchars(app_path('/'), ENT_QUOTES, 'UTF-8'); ?>">Voir le parcours entreprise</a>
                        <?php else: ?>
                            <a class="button-ghost" href="<?= htmlspecialchars(app_path('/search'), ENT_QUOTES, 'UTF-8'); ?>">Voir les offres d'abord</a>
                        <?php endif; ?>
                        <a class="button-ghost" href="<?= htmlspecialchars(app_path('/help'), ENT_QUOTES, 'UTF-8'); ?>">Besoin d'aide ?</a>
                    </div>
                </form>
            </aside>
        </section>

        <p class="top-link"><a href="<?= htmlspecialchars(app_path('/'), ENT_QUOTES, 'UTF-8'); ?>">Retour a l'accueil</a></p>
    </main>
</body>
</html>
