<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class TagMappingRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findDistinctTagNames(): array
    {
        $statement = $this->pdo->prepare(
            'SELECT DISTINCT tag_name
             FROM tags_mapping
             ORDER BY tag_name ASC'
        );
        $statement->execute();

        return array_map(
            static fn (array $row): string => (string) $row['tag_name'],
            $statement->fetchAll()
        );
    }
}
