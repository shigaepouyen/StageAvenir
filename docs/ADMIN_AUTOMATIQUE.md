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

## Commande technique OVH

Exemple de commande CRON :

```bash
/usr/local/bin/php /homez.xxx/votre_login/www/StageAvenir/scripts/cron/cron_nettoyage.php
```

Frequence recommandee :
- une execution annuelle le 15 juillet
