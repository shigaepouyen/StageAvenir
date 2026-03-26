<?php

declare(strict_types=1);

$currentUser = $user ?? null;
$role = (string) ($currentUser['role'] ?? 'guest');
$isCompany = in_array($role, ['company', 'parent'], true);
$isStaff = in_array($role, ['teacher', 'level_manager', 'admin'], true);
$isStudent = $role === 'student';
$bodyClass = $isCompany ? 'page-company' : ($isStaff ? 'page-admin' : 'page-student');
$backUrl = app_path('/');

if ($isStudent) {
    $backUrl = app_path('/search');
} elseif ($isCompany) {
    $backUrl = app_path('/company-profile');
} elseif ($isStaff) {
    $backUrl = app_path('/admin/dashboard');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Aide et FAQ', ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_path('app.css'), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="<?= htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8'); ?>">
    <main class="page-shell">
        <nav class="top-nav surface">
            <div class="nav-cluster">
                <a class="nav-brand" href="<?= htmlspecialchars(app_path('/'), ENT_QUOTES, 'UTF-8'); ?>">Avenir Pro</a>
                <div class="nav-links">
                    <?php if ($isCompany): ?>
                        <a class="nav-link" href="<?= htmlspecialchars(app_path('/company-profile'), ENT_QUOTES, 'UTF-8'); ?>">Mon entreprise</a>
                        <a class="nav-link" href="<?= htmlspecialchars(app_path('/internships'), ENT_QUOTES, 'UTF-8'); ?>">Mes offres</a>
                        <a class="nav-link" href="<?= htmlspecialchars(app_path('/company-applications'), ENT_QUOTES, 'UTF-8'); ?>">Candidatures</a>
                    <?php elseif ($isStaff): ?>
                        <a class="nav-link" href="<?= htmlspecialchars(app_path('/admin/dashboard'), ENT_QUOTES, 'UTF-8'); ?>">Suivi college</a>
                        <?php if ($role === 'admin'): ?>
                            <a class="nav-link" href="<?= htmlspecialchars(app_path('/admin/staff'), ENT_QUOTES, 'UTF-8'); ?>">Comptes staff</a>
                            <a class="nav-link" href="<?= htmlspecialchars(app_path('/admin/internships'), ENT_QUOTES, 'UTF-8'); ?>">Moderation</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a class="nav-link" href="<?= htmlspecialchars(app_path('/search'), ENT_QUOTES, 'UTF-8'); ?>">Trouver un stage</a>
                        <?php if ($isStudent): ?>
                            <a class="nav-link" href="<?= htmlspecialchars(app_path('/my-applications'), ENT_QUOTES, 'UTF-8'); ?>">Mes candidatures</a>
                        <?php endif; ?>
                    <?php endif; ?>
                    <a class="nav-link nav-link-current" href="<?= htmlspecialchars(app_path('/help'), ENT_QUOTES, 'UTF-8'); ?>">Aide</a>
                </div>
            </div>
            <div class="nav-actions">
                <?php if ($currentUser !== null): ?>
                    <a class="button-ghost" href="<?= htmlspecialchars(app_path('/news'), ENT_QUOTES, 'UTF-8'); ?>">Mes news</a>
                    <form class="inline-form" method="post" action="<?= htmlspecialchars(app_path('/logout'), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" class="button-secondary">Me deconnecter</button>
                    </form>
                <?php else: ?>
                    <a class="button-secondary" href="<?= htmlspecialchars(app_path('/login'), ENT_QUOTES, 'UTF-8'); ?>">Me connecter</a>
                <?php endif; ?>
            </div>
        </nav>

        <section class="hero hero-split">
            <div class="hero-copy">
                <p class="eyebrow"><?= htmlspecialchars($isCompany ? 'Aide entreprise' : ($isStaff ? 'Aide equipe educative' : 'Aide collégien'), ENT_QUOTES, 'UTF-8'); ?></p>
                <h1 class="hero-title"><?= htmlspecialchars($title ?? 'Aide et FAQ', ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="hero-text">
                    <?= htmlspecialchars(
                        $isCompany
                            ? "Retrouvez ici les étapes simples pour vérifier l'entreprise, publier une offre et suivre les candidatures."
                            : ($isStaff
                                ? "Retrouvez ici les repères utiles pour suivre les élèves, filtrer le tableau collège et utiliser les bons accès."
                                : "Retrouve ici les réponses simples aux questions fréquentes sur la recherche, le Magic Link et les candidatures."),
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>
                </p>
                <div class="inline-actions">
                    <a class="button-secondary" href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8'); ?>">Revenir a mon espace</a>
                    <?php if (!$isCompany && !$isStaff): ?>
                        <a class="button" href="<?= htmlspecialchars(app_path('/search'), ENT_QUOTES, 'UTF-8'); ?>">Trouver un stage</a>
                    <?php elseif ($isCompany): ?>
                        <a class="button" href="<?= htmlspecialchars(app_path('/company-profile'), ENT_QUOTES, 'UTF-8'); ?>">Verifier mon entreprise</a>
                    <?php else: ?>
                        <a class="button" href="<?= htmlspecialchars(app_path('/admin/dashboard'), ENT_QUOTES, 'UTF-8'); ?>">Ouvrir le suivi college</a>
                    <?php endif; ?>
                </div>
            </div>
            <aside class="hero-panel">
                <p class="eyebrow">Rappel</p>
                <ul class="offer-meta">
                    <li><strong>Connexion :</strong> toujours par Magic Link</li>
                    <li><strong>Mineurs :</strong> les echanges restent dans la webapp</li>
                    <li><strong>Support :</strong> commencez par la question qui correspond a votre profil</li>
                </ul>
            </aside>
        </section>

        <section class="faq-columns" style="margin-top: 1.5rem;">
            <article class="faq-group">
                <p class="eyebrow">Collégiens</p>
                <h3>Questions les plus courantes</h3>
                <p>Comprendre la recherche, la connexion et la candidature.</p>
                <div class="faq-list" style="margin-top: 1rem;">
                    <details class="faq-item" open>
                        <summary>Est-ce que je peux regarder les offres sans etre connecte ?</summary>
                        <p>Oui. Tu peux chercher et lire les fiches librement. La connexion est demandee surtout au moment de candidater.</p>
                    </details>
                    <details class="faq-item">
                        <summary>Je n'ai pas recu l'email de connexion. Que faire ?</summary>
                        <p>Regarde dans les courriers indesirables, puis attends quelques minutes avant de redemander un lien. Le lien recu remplace le mot de passe.</p>
                    </details>
                    <details class="faq-item">
                        <summary>Je ne sais pas quoi ecrire dans ma candidature.</summary>
                        <p>Reste simple : dis ce qui t'interesse dans ce stage, ce que tu aimerais observer et pourquoi tu as envie de le découvrir.</p>
                    </details>
                    <details class="faq-item">
                        <summary>Comment savoir si ma candidature est bien partie ?</summary>
                        <p>Un message de confirmation s'affiche juste apres l'envoi, puis tu retrouves tout dans “Mes candidatures”.</p>
                    </details>
                    <details class="faq-item">
                        <summary>Est-ce que je dois me connecter avant de chercher ?</summary>
                        <p>Non. La recherche et la lecture des fiches restent ouvertes. Le Magic Link sert surtout au moment ou tu veux envoyer une candidature ou relire tes echanges.</p>
                    </details>
                    <details class="faq-item">
                        <summary>Est-ce que l'entreprise verra mon adresse email ?</summary>
                        <p>Non. Les echanges se font dans Avenir Pro. Les emails servent seulement a te prevenir qu'une nouveaute t'attend dans ton espace.</p>
                    </details>
                    <details class="faq-item">
                        <summary>Qui peut voir mes candidatures ?</summary>
                        <p>L'entreprise concernee, toi-meme et l'equipe educative autorisee peuvent suivre la candidature. Le detail passe dans la webapp, pas dans les emails.</p>
                    </details>
                </div>
            </article>

            <article class="faq-group">
                <p class="eyebrow">Entreprises</p>
                <h3>Questions les plus courantes</h3>
                <p>Comprendre le profil entreprise, la publication et le suivi des candidatures.</p>
                <div class="faq-list" style="margin-top: 1rem;">
                    <details class="faq-item" open>
                        <summary>Comment publier ma premiere offre ?</summary>
                        <p>Commencez par “Mon entreprise”, verifiez le SIRET et enregistrez le profil. Une fois l'entreprise validee, vous pouvez ajouter une offre dans “Mes offres”.</p>
                    </details>
                    <details class="faq-item">
                        <summary>Pourquoi mon offre n'est-elle pas encore visible ?</summary>
                        <p>Une offre reste en attente tant que l'administration n'a pas valide l'entreprise puis l'offre elle-meme. Cela evite les publications trop rapides pour un public mineur.</p>
                    </details>
                    <details class="faq-item">
                        <summary>Comment repondre a un collégien ?</summary>
                        <p>Tout se fait dans la discussion integree a la candidature. L'email du collégien n'est pas affiche.</p>
                    </details>
                    <details class="faq-item">
                        <summary>Comment savoir si mon entreprise a ete validee ?</summary>
                        <p>Le statut apparait dans “Mon entreprise” et une alerte arrive dans “Mes news”. Tant que l'entreprise n'est pas validee, les offres ne peuvent pas etre publiees.</p>
                    </details>
                    <details class="faq-item">
                        <summary>Puis-je rendre une offre invisible sans la supprimer ?</summary>
                        <p>Oui. Depuis “Mes offres”, vous pouvez la passer en invisible. Elle ne sera plus visible cote eleve.</p>
                    </details>
                    <details class="faq-item">
                        <summary>Comment savoir si j'ai recu une nouvelle candidature ?</summary>
                        <p>La plateforme envoie une alerte email neutre, puis le detail est visible dans “Candidatures” et dans “Mes news”.</p>
                    </details>
                    <details class="faq-item">
                        <summary>Pourquoi l'offre passe-t-elle aussi par une validation ?</summary>
                        <p>Parce que la plateforme s'adresse a des mineurs. La moderation verifie d'abord l'entreprise, puis l'offre, avant de la rendre visible aux eleves.</p>
                    </details>
                </div>
            </article>
        </section>

        <section class="faq-columns" style="margin-top: 1rem;">
            <article class="faq-group">
                <p class="eyebrow">Professeurs</p>
                <h3>Suivi d'une classe</h3>
                <p>Pour les professeurs principaux et les responsables de niveau.</p>
                <div class="faq-list" style="margin-top: 1rem;">
                    <details class="faq-item" open>
                        <summary>Que voit un professeur principal ?</summary>
                        <p>Le professeur principal voit seulement sa classe, definie dans `managed_class`. Il peut filtrer, consulter les candidatures et exporter le suivi.</p>
                    </details>
                    <details class="faq-item">
                        <summary>Que voit un responsable de niveau ?</summary>
                        <p>Le responsable de niveau voit l'ensemble du niveau dans le tableau college. Il n'est pas limite a une seule classe.</p>
                    </details>
                    <details class="faq-item">
                        <summary>Comment retrouver un eleve rapidement ?</summary>
                        <p>Le tableau college propose une recherche par prenom ou nom, ainsi qu'un annuaire interne par classe.</p>
                    </details>
                    <details class="faq-item">
                        <summary>Pourquoi je ne vois pas toutes les classes ?</summary>
                        <p>Un professeur principal est limite a sa classe. Seul un responsable de niveau ou un admin peut suivre un ensemble plus large.</p>
                    </details>
                    <details class="faq-item">
                        <summary>Est-ce que je vois l'email des eleves ?</summary>
                        <p>Non. Les tableaux de suivi sont concus pour afficher les identites internes utiles au college, sans diffuser l'adresse email des mineurs.</p>
                    </details>
                </div>
            </article>

            <article class="faq-group">
                <p class="eyebrow">Administration</p>
                <h3>Moderation et comptes</h3>
                <p>Pour la gestion globale de la campagne.</p>
                <div class="faq-list" style="margin-top: 1rem;">
                    <details class="faq-item" open>
                        <summary>Comment se connecte un admin ?</summary>
                        <p>L'admin se connecte lui aussi par Magic Link. Son compte doit simplement exister dans la base avec le role `admin`.</p>
                    </details>
                    <details class="faq-item">
                        <summary>Comment creer un compte professeur ?</summary>
                        <p>Depuis “Comptes staff”, l'admin cree un compte `teacher` ou `level_manager`. Le Magic Link sera ensuite utilise comme pour les autres profils.</p>
                    </details>
                    <details class="faq-item">
                        <summary>Pourquoi valider l'entreprise puis l'offre ?</summary>
                        <p>Cette double validation evite de publier des offres non controlees sur une plateforme destinee a des mineurs.</p>
                    </details>
                    <details class="faq-item">
                        <summary>Comment recevoir les alertes de la plateforme ?</summary>
                        <p>L'email sert seulement de signal. Le detail des actions, des refus, des validations et des messages se lit ensuite dans “Mes news” et dans les ecrans concernes.</p>
                    </details>
                    <details class="faq-item">
                        <summary>Comment creer le premier admin ?</summary>
                        <p>Le premier compte admin doit etre prepare en base. Ensuite cet admin peut creer les comptes staff directement depuis “Comptes staff”.</p>
                    </details>
                </div>
            </article>
        </section>
    </main>
</body>
</html>
