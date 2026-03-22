<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class CleanupRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function sleepVisibleInternships(): int
    {
        $statement = $this->pdo->prepare(
            'UPDATE internships
             SET status = :sleeping
             WHERE status <> :archived'
        );
        $statement->execute([
            'sleeping' => 'sleeping',
            'archived' => 'archived',
        ]);

        return $statement->rowCount();
    }
}
