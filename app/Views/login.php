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
        <nav class="top-nav surface">
            <div class="nav-cluster">
                <a class="nav-brand" href="<?= htmlspecialchars(app_path('/'), ENT_QUOTES, 'UTF-8'); ?>">Avenir Pro</a>
                <div class="nav-links">
                    <a class="nav-link" href="<?= htmlspecialchars(app_path('/search'), ENT_QUOTES, 'UTF-8'); ?>">Trouver un stage</a>
                    <a class="nav-link <?= $isCompanyPath ? 'nav-link-current' : ''; ?>" href="<?= htmlspecialchars($companyLoginUrl, ENT_QUOTES, 'UTF-8'); ?>">Je propose un stage</a>
                    <a class="nav-link <?= $isCompanyPath ? '' : 'nav-link-current'; ?>" href="<?= htmlspecialchars($studentLoginUrl, ENT_QUOTES, 'UTF-8'); ?>">Connexion eleve</a>
                    <a class="nav-link" href="<?= htmlspecialchars(app_path('/help'), ENT_QUOTES, 'UTF-8'); ?>">Aide</a>
                </div>
            </div>
            <div class="nav-actions">
                <a class="button-secondary" href="<?= htmlspecialchars(app_path('/'), ENT_QUOTES, 'UTF-8'); ?>">Retour a l'accueil</a>
            </div>
        </nav>

        <section class="hero hero-split">
            <div class="hero-copy">
                <p class="eyebrow"><?= htmlspecialchars($isCompanyPath ? 'Acces entreprise' : 'Acces eleve', ENT_QUOTES, 'UTF-8'); ?></p>
                <h1 class="hero-title"><?= htmlspecialchars($isCompanyPath ? 'Je saisis mon email professionnel, puis je continue dans la webapp.' : "J'entre mon email et je recois un lien de connexion.", ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="hero-text">
                    <?= htmlspecialchars($isCompanyPath
                        ? "Le Magic Link remplace le mot de passe. Si l'adresse n'existe pas encore, Avenir Pro cree votre acces entreprise, puis vous guide vers le profil et les offres."
                        : "Pas besoin de mot de passe. Le lien recu par email suffit pour revenir dans Avenir Pro et envoyer une candidature.", ENT_QUOTES, 'UTF-8'); ?>
                </p>

                <div class="journey-grid">
                    <a class="journey-card <?= $isCompanyPath ? '' : 'journey-card-current'; ?>" href="<?= htmlspecialchars($studentLoginUrl, ENT_QUOTES, 'UTF-8'); ?>">
                        <strong>Je suis un collégien</strong>
                        <span>Je regarde les offres, puis je me connecte au moment de candidater.</span>
                    </a>
                    <a class="journey-card <?= $isCompanyPath ? 'journey-card-current' : ''; ?>" href="<?= htmlspecialchars($companyLoginUrl, ENT_QUOTES, 'UTF-8'); ?>">
                        <strong>Je suis une entreprise</strong>
                        <span>Je valide mon entreprise, je publie une offre puis je suis les candidatures.</span>
                    </a>
                </div>

                <ol class="process-list">
                    <?php if ($isCompanyPath): ?>
                        <li><span class="process-index">1</span><span>Je saisis l'email de l'entreprise ou du parent referent.</span></li>
                        <li><span class="process-index">2</span><span>Je clique sur le lien recu par email.</span></li>
                        <li><span class="process-index">3</span><span>Je complete mon entreprise puis je depose mes offres.</span></li>
                    <?php else: ?>
                        <li><span class="process-index">1</span><span>Je consulte les offres librement.</span></li>
                        <li><span class="process-index">2</span><span>Je saisis mon email quand je veux candidater.</span></li>
                        <li><span class="process-index">3</span><span>Je reviens dans Avenir Pro grâce au lien recu.</span></li>
                    <?php endif; ?>
                </ol>
            </div>

            <aside class="hero-panel login-panel">
                <h2 class="section-title"><?= htmlspecialchars($isCompanyPath ? 'Recevoir mon lien entreprise' : 'Recevoir mon lien de connexion', ENT_QUOTES, 'UTF-8'); ?></h2>
                <p class="form-help">
                    <?= htmlspecialchars($isCompanyPath
                        ? "Cette adresse servira ensuite a recevoir les alertes de la plateforme. Le detail des échanges restera dans Avenir Pro."
                        : "Tu pourras ensuite retrouver tes candidatures et les reponses directement dans la plateforme.", ENT_QUOTES, 'UTF-8'); ?>
                </p>

                <?php if (!empty($error)): ?>
                    <p class="message message-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <p class="message message-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>

                <form method="post" action="<?= htmlspecialchars(app_path('/login'), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="account_type" value="<?= htmlspecialchars($selectedAccountType, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="return_to" value="<?= htmlspecialchars($returnTo, ENT_QUOTES, 'UTF-8'); ?>">
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
                        <a class="button-ghost" href="<?= htmlspecialchars($isCompanyPath ? app_path('/help') : app_path('/search'), ENT_QUOTES, 'UTF-8'); ?>">
                            <?= htmlspecialchars($isCompanyPath ? 'Voir la FAQ entreprise' : 'Voir les offres d abord', ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    </div>
                </form>
            </aside>
        </section>
    </main>
</body>
</html>
