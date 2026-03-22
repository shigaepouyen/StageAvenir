<?php

declare(strict_types=1);

use App\Repositories\ApplicationRepository;
use App\Repositories\CleanupRepository;
use App\Support\Env;

/*
|---------------------------------------------------------------------------
| CRON de nettoyage annuel - 15 juillet
|---------------------------------------------------------------------------
| Ce script est prevu pour etre lance automatiquement chaque 15 juillet.
|
| Effets :
| 1. Les offres non archivees passent en `sleeping` afin de devenir invisibles
|    cote eleve pour la nouvelle coupure estivale.
| 2. Les journaux de candidatures sont anonymises pour reduire la conservation
|    des donnees personnelles eleves.
|
| RGPD :
| - on applique ici une logique de minimisation des donnees conservees ;
| - on supprime le lien direct vers l'eleve (`student_id`) ;
| - on remplace le contenu libre par des valeurs neutralisees, car il peut
|   contenir des noms, prenoms, coordonnees ou autres donnees identifiantes.
|
| Parametrage :
| - NETTOYAGE_MONTH / NETTOYAGE_DAY pilotent la date theorique de campagne.
| - le script peut etre relance manuellement sans effet destructif supplementaire
|   sur les candidatures deja anonymisees.
*/

$rootDir = dirname(__DIR__, 2);
$autoloadPath = $rootDir . '/vendor/autoload.php';

if (!is_file($autoloadPath)) {
    fwrite(STDERR, "Dependances manquantes. Lancez 'composer install'.\n");
    exit(1);
}

require $autoloadPath;

Env::load($rootDir . '/.env.local');

/** @var \PDO $pdo */
$pdo = require $rootDir . '/config/database.php';
$cleanupConfig = require $rootDir . '/config/cleanup.php';

$cleanupRepo = new CleanupRepository($pdo);
$applications = new ApplicationRepository($pdo);

$today = new DateTimeImmutable('today');
$expectedDate = new DateTimeImmutable(sprintf(
    '%d-%02d-%02d',
    (int) $today->format('Y'),
    (int) $cleanupConfig['month'],
    (int) $cleanupConfig['day']
));

if ($today->format('Y-m-d') !== $expectedDate->format('Y-m-d')) {
    fwrite(
        STDOUT,
        "Information: execution hors date theorique de nettoyage (" . $expectedDate->format('Y-m-d') . ").\n"
    );
}

try {
    $pdo->beginTransaction();

    $sleepingCount = $cleanupRepo->sleepVisibleInternships();
    $anonymizedCount = $applications->anonymizeAllStudentData();

    $pdo->commit();

    fwrite(
        STDOUT,
        sprintf(
            "Nettoyage termine. Offres passees en sleeping: %d. Candidatures anonymisees: %d.\n",
            $sleepingCount,
            $anonymizedCount
        )
    );
    exit(0);
} catch (\Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, "Echec nettoyage annuel : " . $exception->getMessage() . "\n");
    exit(1);
}
