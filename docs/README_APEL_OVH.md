# README APEL - Deploiement OVH

## Objectif
Ce document explique comment mettre Avenir Pro en ligne sur un hebergement OVH mutualise, sans outil technique avance.

## 1. Fichiers a envoyer
Envoyez tout le contenu du projet dans votre espace FTP OVH, par exemple dans :

- `www/StageAvenir/`
- n'oubliez pas le fichier cache `.htaccess`

Le point d'entree de l'application est :

- `www/StageAvenir/index.php`

Les dossiers importants sont :

- `www/StageAvenir/app/`
- `www/StageAvenir/config/`
- `www/StageAvenir/public/`
- `www/StageAvenir/scripts/cron/`

## 2. Base de donnees MariaDB
Dans phpMyAdmin OVH :

1. creez une base MariaDB si ce n'est pas deja fait
2. importez d'abord `schema.sql`
3. importez ensuite `seed.sql` si vous voulez charger les donnees d'exemple

Si vous mettez a jour une base deja existante, importez aussi :

- `scripts/sql/step15_hardening.sql`
- `scripts/sql/step18_company_applications.sql`
- `scripts/sql/step20_security_moderation_messaging.sql`
- `scripts/sql/step21_notifications.sql`

## 3. Configuration
Copiez `.env.example` vers `.env.local`, puis adaptez au minimum :

- `APP_URL`
- `MAIL_FROM`
- `MAIL_FROM_NAME`
- `SMTP_HOST`
- `SMTP_PORT`
- `SMTP_ENCRYPTION`
- `SMTP_USERNAME`
- `SMTP_PASSWORD`
- `DB_HOST`
- `DB_PORT`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`

Exemple si l'application est servie dans un sous-repertoire OVH :

- `APP_URL=https://votre-domaine.fr/StageAvenir`

Sur OVH, placez `.env.local` au meme niveau que `index.php`, donc par exemple :

- `www/StageAvenir/.env.local`

Exemple de configuration SMTP Google Workspace :

```env
MAIL_FROM=contact@votre-domaine.fr
MAIL_FROM_NAME=APEL - Stage Avenir
SMTP_HOST=smtp.gmail.com
SMTP_PORT=465
SMTP_ENCRYPTION=ssl
SMTP_USERNAME=contact@votre-domaine.fr
SMTP_PASSWORD=mot-de-passe-application-google
SMTP_FROM_EMAIL=contact@votre-domaine.fr
SMTP_FROM_NAME=APEL - Stage Avenir
SMTP_TIMEOUT_SECONDS=15
```

Important :
- utilisez un mot de passe d'application Google, pas le mot de passe habituel du compte
- laissez `.env.local` hors du depot git
- en cas de doute sur l'envoi des emails, utilisez temporairement `mail_diagnostic.php`, puis supprimez-le du serveur

## 4. Dependances PHP
Le projet utilise FlightPHP via Composer. Il faut donc que le dossier `vendor/` soit present sur l'hebergement.

Deux options pratiques :

- lancer `composer install --no-dev` en local puis envoyer aussi le dossier `vendor/` en FTP
- ou lancer Composer directement sur l'hebergement si votre offre et votre acces OVH le permettent

## 5. Droits et emplacement
Sur hebergement mutualise, gardez une structure simple :

- application dans `www/StageAvenir/`
- scripts CRON dans `www/StageAvenir/scripts/cron/`
- SQL a conserver localement ou dans un dossier d'administration non public si besoin

## 6. Taches CRON OVH
Dans le manager OVH, creez une tache planifiee par script.

Commande type :

```bash
/usr/local/bin/php /homez.xxx/votre_login/www/StageAvenir/scripts/cron/import_onisep.php
```

Adaptez le chemin `homez.xxx/votre_login` a votre hebergement OVH.

CRON conseilles :

- import ONISEP : une fois par mois, par exemple le 1er a 03:00
- reveil annuel : premier lancement le 1er septembre, puis relance quotidienne pendant la campagne de relance
- nettoyage annuel : le 15 juillet

Exemples de scripts :

- `scripts/cron/import_onisep.php`
- `scripts/cron/cron_reveil.php`
- `scripts/cron/cron_nettoyage.php`

## 7. Securite deja prevue dans l'application

- requetes SQL via PDO et statements prepares
- echappement HTML avec `htmlspecialchars`
- protection CSRF sur tous les formulaires POST
- session PHP avec cookie `HttpOnly`
- aucune diffusion de l'email eleve vers l'entreprise
- discussions eleve/entreprise uniquement dans la webapp
- alertes email volontairement neutres, avec lecture detaillee apres connexion

## 8. Premier compte admin

Le formulaire `/login` ne cree jamais un compte `admin`.

Il faut donc preparer le premier admin en base de donnees, par exemple dans phpMyAdmin :

```sql
INSERT INTO users (email, role, first_name, last_name, created_at)
VALUES ('admin@votre-domaine.fr', 'admin', 'Admin', 'APEL', NOW());
```

Si le compte existe deja avec un autre role :

```sql
UPDATE users
SET role = 'admin'
WHERE email = 'admin@votre-domaine.fr';
```

Ensuite l'admin se connecte normalement sur `/login` avec son email, puis recoit un Magic Link.

Une fois connecte, l'admin peut :
- ouvrir `/admin/dashboard`
- moderer les entreprises et offres sur `/admin/internships`
- gerer les comptes professeurs et responsables de niveau sur `/admin/staff`

## 9. Verification apres mise en ligne

1. ouvrez la page d'accueil
2. testez `/login`
3. verifiez qu'un email Magic Link part bien
4. verifiez qu'un admin peut se connecter puis ouvrir `/admin/staff`
5. verifiez qu'un admin peut creer un compte professeur principal
6. verifiez qu'un admin peut creer un compte responsable de niveau
7. verifiez qu'une entreprise peut creer une offre
8. verifiez qu'une entreprise reste en attente de validation tant que l'admin n'a pas valide son profil
9. verifiez qu'une offre nouvellement soumise reste en attente de validation avant publication
10. verifiez qu'un eleve peut rechercher, candidater puis discuter dans la webapp sans diffusion de son email
11. verifiez que l'admin peut ouvrir `/admin/dashboard` et exporter le CSV de suivi
12. verifiez que le role professeur ne voit que sa classe et que le role responsable de niveau voit tout le niveau
13. testez un script CRON manuellement une premiere fois

## 10. Point d'attention OVH
Le cookie de session est prevu pour un site en HTTPS. Il faut donc activer le certificat SSL sur le domaine avant usage normal.
