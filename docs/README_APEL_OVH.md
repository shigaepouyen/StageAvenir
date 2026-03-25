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

## 3. Configuration
Copiez `.env.example` vers `.env.local`, puis adaptez au minimum :

- `APP_URL`
- `MAIL_FROM`
- `MAIL_FROM_NAME`
- `DB_HOST`
- `DB_PORT`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`

Exemple si l'application est servie dans un sous-repertoire OVH :

- `APP_URL=https://votre-domaine.fr/StageAvenir`

Sur OVH, placez `.env.local` au meme niveau que `index.php`, donc par exemple :

- `www/StageAvenir/.env.local`

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

## 8. Verification apres mise en ligne

1. ouvrez la page d'accueil
2. testez `/login`
3. verifiez qu'un email Magic Link part bien
4. verifiez qu'une entreprise peut creer une offre
5. verifiez qu'une entreprise reste en attente de validation tant que l'admin n'a pas valide son profil
6. verifiez qu'une offre nouvellement soumise reste en attente de validation avant publication
7. verifiez qu'un eleve peut rechercher, candidater puis discuter dans la webapp sans diffusion de son email
8. verifiez que l'admin peut ouvrir `/admin/dashboard` et exporter le CSV de suivi
9. verifiez que le role professeur ne voit que sa classe et que le role responsable de niveau voit tout le niveau
10. testez un script CRON manuellement une premiere fois

## 9. Point d'attention OVH
Le cookie de session est prevu pour un site en HTTPS. Il faut donc activer le certificat SSL sur le domaine avant usage normal.
