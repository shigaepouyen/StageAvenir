# Avenir Pro

## Description
Base initiale de l'application web Avenir Pro en PHP 8.1+ avec MariaDB et FlightPHP, compatible avec un hebergement OVH mutualise.

## Structure
- `index.php` : point d'entree de l'application.
- `config/database.php` : connexion PDO vers MariaDB.
- `config/auth.php` : configuration Magic Link et session.
- `app/Controllers/` : controleurs.
- `app/Models/` : modeles.
- `app/Views/` : vues.
- `public/` : assets publics.
- `schema.sql` : schema de base de donnees.
- `seed.sql` : donnees d'exemple.

## Installation
1. Lancer `composer install`.
2. Copier `.env.example` vers `.env.local`.
3. Renseigner les acces MariaDB.

## Contraintes retenues
- PDO avec prepared statements.
- Sorties HTML echappees avec `htmlspecialchars`.
- Protection CSRF sur tous les formulaires `POST`.
- URLs internes centralisees pour supporter un deploiement en sous-repertoire OVH.
- Aucun framework lourd.
- Aucun SQLite.

## Authentification
- `/login` affiche le formulaire d'authentification par email.
- Un Magic Link avec `selector` et `token` est envoye par email.
- Le token expire au bout de 20 minutes par defaut.
- Un rate limiting simple limite les demandes par email et par IP.
- La session PHP est configuree avec cookies `HttpOnly` et `Secure` pour 30 jours.

## Profil entreprise
- `/company-profile` permet a un compte `parent`, `company` ou `admin` d'enregistrer son SIRET.
- Le SIRET est verifie avec un format simple `14 chiffres`.
- L'unicite du SIRET est controlee en base et dans le controleur.
- Une recherche Sirene permet de retrouver une entreprise par nom ou SIRET.
- Les donnees `name`, `address`, `naf_code`, `lat` et `lng` sont pre-remplies depuis l'API puis sauvegardees.
- Le profil entreprise passe ensuite par une validation administrative (`pending`, `approved`, `rejected`) avant de pouvoir publier des offres visibles.

## Offres de stage
- `/internships` liste les offres de l'entreprise connectee.
- `/internships/create` permet de soumettre une offre.
- Les champs `title`, `description`, `places_count` et la certification sont verifies cote serveur.
- Le statut initial est `active`, l'annee scolaire est calculee automatiquement et la validation initiale est `pending`.
- Un tag sectoriel simple peut etre associe a chaque offre.
- `/internships/{id}/sleep` passe une offre en `sleeping` et la rend invisible pour les eleves.
- `/admin/internships` permet a un `admin` de valider ou refuser une entreprise, de valider ou refuser une offre, puis d'archiver une offre.
- Les helpers globaux `set_internship_status($id, $new_status)`, `get_active_internships()` et `get_archived_internships()` sont disponibles dans `app/Support/internship_helpers.php`.
- `/offers` n'affiche que les offres `active` dont l'entreprise et l'offre elle-meme sont validees.
- `/search` permet une recherche eleve par mots-cles et secteur, avec lien vers `/offers/{id}`.
- Le tri geographique utilise une fonction PHP `haversine_distance_km()` et des coordonnees eleve/college saisies dans la recherche.
- Les cartes Leaflet affichent uniquement les offres disposant de coordonnees `lat/lng`.
- `/offers/{id}` affiche un formulaire de candidature eleve.
- Les candidatures sont stockees dans `applications` puis transformees en discussion interne dans la webapp (`application_messages`).
- Les emails servent uniquement de notification neutre avec un lien vers la discussion. Les adresses email eleves ne sont jamais diffusees a l'entreprise.
- Une meme offre ne peut pas recevoir plusieurs candidatures du meme eleve tant que la candidature precedente n'a pas ete anonymisee.
- `/company-applications` permet au parent ou a l'entreprise de filtrer les candidatures recues et de faire evoluer leur statut (`new`, `contacted`, `accepted`, `rejected`).
- `/applications/{id}` centralise les echanges entre eleve et entreprise dans la webapp.
- `/news` centralise les alertes internes de la plateforme. Les emails d'alerte restent generiques et invitent a se reconnecter pour lire la nouveaute en mode connecte.
- Lorsqu'une offre atteint son nombre de places via les candidatures `accepted`, elle passe automatiquement en `sleeping` pour devenir invisible cote eleve.
- Si une candidature `accepted` repasse ensuite a un autre statut et qu'une place se libere, l'offre peut redevenir automatiquement `active`.
- `/admin/dashboard` fournit un tableau de bord college avec filtres, alertes simples et export CSV des candidatures.
- `/admin/dashboard` fournit aussi un annuaire interne des eleves par classe avec recherche par prenom ou nom, sans afficher leurs adresses email.
- Le role `teacher` est limite a sa classe (`managed_class`), tandis que le role `level_manager` peut suivre l'ensemble des eleves du niveau.

## Referentiel ONISEP
- `ref_jobs` stocke un referentiel local des metiers ONISEP.
- `scripts/cron/import_onisep.php` telecharge la ressource JSON officielle, puis insere ou met a jour `ref_jobs`.
- Le script est prevu pour etre lance en CRON sur OVH mutualise.

## Reveil annuel des offres
- `scripts/cron/cron_reveil.php` pilote la campagne annuelle de reactivation.
- Le script cible les offres de l'annee scolaire precedente dont le statut n'est pas `archived`.
- Chaque offre peut recevoir jusqu'a 3 emails, espaces par 3 jours par defaut.
- Sans clic sur le lien `Oui` apres la derniere relance et le delai parametre, l'offre passe en `archived`.
- Le lien de confirmation appelle `/internships/revival/confirm` via un token securise.

## Nettoyage annuel et RGPD
- `scripts/cron/cron_nettoyage.php` passe les offres non archivees en `sleeping`.
- Le script anonymise ensuite les candidatures eleves en supprimant le lien direct avec le compte eleve.
- La documentation admin non technique est dans `docs/ADMIN_AUTOMATIQUE.md`.

## Deploiement OVH
- Un guide simple pour l'admin APEL est disponible dans `docs/README_APEL_OVH.md`.
- Un script SQL de mise a niveau rapide est disponible dans `scripts/sql/step15_hardening.sql`.
- Un script SQL complementaire est disponible dans `scripts/sql/step18_company_applications.sql`.
- Un script SQL complementaire est disponible dans `scripts/sql/step20_security_moderation_messaging.sql`.
- Un script SQL complementaire est disponible dans `scripts/sql/step21_notifications.sql`.
