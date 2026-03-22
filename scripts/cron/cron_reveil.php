<?php

declare(strict_types=1);

use App\Repositories\InternshipRepository;
use App\Repositories\InternshipRevivalRepository;
use App\Support\Env;
use App\Support\RevivalMailer;

/*
|---------------------------------------------------------------------------
| Parametrage du reveil
|---------------------------------------------------------------------------
| - REVIVAL_START_MONTH / REVIVAL_START_DAY :
|   date a partir de laquelle la campagne de reveil peut commencer.
| - REVIVAL_MAX_EMAILS :
|   nombre total d'emails envoyes par offre avant archivage automatique.
| - REVIVAL_REMINDER_DELAY_DAYS :
|   delai entre deux envois, et delai final avant archivage apres le dernier mail.
|
| Le script peut etre lance une premiere fois le 1er septembre, puis relance
| quotidiennement pendant la campagne. Un seul lancement le 1er septembre ne
| suffit pas pour gerer les 3 relances espacees dans le temps.
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
$revivalConfig = require $rootDir . '/config/revival.php';

$internships = new InternshipRepository($pdo);
$revivals = new InternshipRevivalRepository($pdo);
$mailer = new RevivalMailer($revivalConfig['mail'], $revivalConfig['app_url']);

$today = new DateTimeImmutable('today');
$campaignStart = new DateTimeImmutable(sprintf(
    '%d-%02d-%02d',
    (int) $today->format('Y'),
    (int) $revivalConfig['start_month'],
    (int) $revivalConfig['start_day']
));

if ($today < $campaignStart) {
    fwrite(STDOUT, "Campagne de reveil non demarree. Debut prevu le " . $campaignStart->format('Y-m-d') . ".\n");
    exit(0);
}

$currentAcademicYear = currentAcademicYear($today);
$previousAcademicYear = previousAcademicYear($currentAcademicYear);
$maxEmails = max(1, (int) $revivalConfig['max_emails']);
$delayDays = max(1, (int) $revivalConfig['reminder_delay_days']);
$processed = 0;
$sent = 0;
$archived = 0;

try {
    $candidates = $internships->findPreviousYearNonArchivedForRevival($previousAcademicYear);

    foreach ($candidates as $internship) {
        $processed++;

        $ownerEmail = trim((string) ($internship['owner_email'] ?? ''));

        if ($ownerEmail === '' || filter_var($ownerEmail, FILTER_VALIDATE_EMAIL) === false) {
            fwrite(STDERR, "Email parent invalide pour l'offre #" . $internship['id'] . ".\n");
            continue;
        }

        $revival = $revivals->findByInternshipIdAndTargetYear((int) $internship['id'], $currentAcademicYear);

        if ($revival === null) {
            $selector = bin2hex(random_bytes(12));
            $validator = bin2hex(random_bytes(32));
            $hashedValidator = hash('sha256', $validator);

            $revivalId = $revivals->create(
                (int) $internship['id'],
                $currentAcademicYear,
                $selector,
                $hashedValidator
            );

            if (!$mailer->sendRevivalEmail($ownerEmail, (string) $internship['title'], $selector, $validator)) {
                $revivals->deleteById($revivalId);
                fwrite(STDERR, "Echec envoi reveil pour l'offre #" . $internship['id'] . ".\n");
                continue;
            }

            $revivals->markEmailSent($revivalId);
            $sent++;
            continue;
        }

        if ($revival['confirmed_at'] !== null || $revival['archived_at'] !== null) {
            continue;
        }

        $lastSentAt = $revival['last_sent_at'] !== null
            ? new DateTimeImmutable((string) $revival['last_sent_at'])
            : null;

        if ($lastSentAt === null && (int) $revival['emails_sent'] === 0) {
            $validator = bin2hex(random_bytes(32));
            $selector = (string) $revival['selector'];
            $hashedValidator = hash('sha256', $validator);
            $previousHash = (string) $revival['hashed_validator'];

            replaceRevivalValidator($pdo, (int) $revival['id'], $hashedValidator);

            if (!$mailer->sendRevivalEmail($ownerEmail, (string) $internship['title'], $selector, $validator)) {
                replaceRevivalValidator($pdo, (int) $revival['id'], $previousHash);
                fwrite(STDERR, "Echec reenvoi reveil initial pour l'offre #" . $internship['id'] . ".\n");
                continue;
            }

            $revivals->markEmailSent((int) $revival['id']);
            $sent++;
            continue;
        }

        if ($lastSentAt === null) {
            continue;
        }

        $nextActionDate = $lastSentAt->modify('+' . $delayDays . ' days');

        if ($today < $nextActionDate) {
            continue;
        }

        if ((int) $revival['emails_sent'] < $maxEmails) {
            $validator = bin2hex(random_bytes(32));
            $selector = (string) $revival['selector'];
            $hashedValidator = hash('sha256', $validator);
            $previousHash = (string) $revival['hashed_validator'];

            replaceRevivalValidator($pdo, (int) $revival['id'], $hashedValidator);

            if (!$mailer->sendRevivalEmail($ownerEmail, (string) $internship['title'], $selector, $validator)) {
                replaceRevivalValidator($pdo, (int) $revival['id'], $previousHash);
                fwrite(STDERR, "Echec relance reveil pour l'offre #" . $internship['id'] . ".\n");
                continue;
            }

            $revivals->markEmailSent((int) $revival['id']);
            $sent++;
            continue;
        }

        $internships->updateStatusAndAcademicYear((int) $internship['id'], 'archived', (string) $internship['academic_year']);
        $revivals->markArchived((int) $revival['id']);
        $archived++;
    }

    fwrite(STDOUT, sprintf(
        "Campagne reveil terminee. Offres traitees: %d, emails envoyes: %d, offres archivees: %d.\n",
        $processed,
        $sent,
        $archived
    ));
    exit(0);
} catch (\Throwable $exception) {
    fwrite(STDERR, "Echec campagne reveil : " . $exception->getMessage() . "\n");
    exit(1);
}

function currentAcademicYear(DateTimeImmutable $date): string
{
    $year = (int) $date->format('Y');
    $month = (int) $date->format('n');

    if ($month >= 9) {
        return $year . '-' . ($year + 1);
    }

    return ($year - 1) . '-' . $year;
}

function previousAcademicYear(string $currentAcademicYear): string
{
    [$startYear, $endYear] = array_map('intval', explode('-', $currentAcademicYear));
    return ($startYear - 1) . '-' . ($endYear - 1);
}

function replaceRevivalValidator(\PDO $pdo, int $revivalId, string $hashedValidator): void
{
    $statement = $pdo->prepare(
        'UPDATE internship_revival_requests
         SET hashed_validator = :hashed_validator
         WHERE id = :id'
    );
    $statement->execute([
        'id' => $revivalId,
        'hashed_validator' => $hashedValidator,
    ]);
}
