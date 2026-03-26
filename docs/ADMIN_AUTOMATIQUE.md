# Automatisations admin

## Nettoyage annuel du 15 juillet

Ce traitement est automatique.

Il ne demande aucune action manuelle de votre part.

Chaque 15 juillet, le script :
- rend les offres invisibles pour les eleves en les passant en statut `sleeping`
- anonymise les anciennes candidatures eleves pour limiter la conservation de donnees personnelles

Ce que cela signifie pour vous :
- les offres ne disparaissent pas
- elles restent dans l'application, mais ne sont plus visibles cote eleve
- les informations sensibles des candidatures sont neutralisees automatiquement

En cas de besoin, l'equipe technique peut relancer le script manuellement.

## Ce que l'admin n'a pas a faire

Ce script ne demande pas :
- de cliquer dans l'application
- de modifier les offres une par une
- de supprimer manuellement les candidatures

Le traitement est pense pour rester compatible avec le RGPD et limiter la conservation de donnees eleves d'une campagne a l'autre.

## Rappel sur les comptes staff

Les comptes professeurs principaux et responsables de niveau ne sont pas geres par ce script.

Ils se gerent directement dans l'application, dans l'espace admin :
- `/admin/staff`

Le nettoyage annuel ne modifie pas ces comptes.

## Commande technique OVH

Exemple de commande CRON :

```bash
/usr/local/bin/php /homez.xxx/votre_login/www/StageAvenir/scripts/cron/cron_nettoyage.php
```

Frequence recommandee :
- une execution annuelle le 15 juillet
