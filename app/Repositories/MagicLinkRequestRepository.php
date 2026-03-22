<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class MagicLinkRequestRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function countRecentByEmail(string $email, int $windowMinutes): int
    {
        $cutoff = (new \DateTimeImmutable())
            ->modify(sprintf('-%d minutes', max(1, $windowMinutes)))
            ->format('Y-m-d H:i:s');

        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) AS total
             FROM magic_link_requests
             WHERE email = :email
               AND requested_at >= :cutoff'
        );
        $statement->execute([
            'email' => $email,
            'cutoff' => $cutoff,
        ]);

        return (int) $statement->fetchColumn();
    }

    public function countRecentByIp(string $ipAddress, int $windowMinutes): int
    {
        $cutoff = (new \DateTimeImmutable())
            ->modify(sprintf('-%d minutes', max(1, $windowMinutes)))
            ->format('Y-m-d H:i:s');

        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) AS total
             FROM magic_link_requests
             WHERE ip_address = :ip_address
               AND requested_at >= :cutoff'
        );
        $statement->execute([
            'ip_address' => $ipAddress,
            'cutoff' => $cutoff,
        ]);

        return (int) $statement->fetchColumn();
    }

    public function create(string $email, string $ipAddress): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO magic_link_requests (email, ip_address, requested_at)
             VALUES (:email, :ip_address, NOW())'
        );
        $statement->execute([
            'email' => $email,
            'ip_address' => $ipAddress,
        ]);
    }

    public function deleteOlderThanHours(int $hours): void
    {
        $cutoff = (new \DateTimeImmutable())
            ->modify(sprintf('-%d hours', max(1, $hours)))
            ->format('Y-m-d H:i:s');

        $statement = $this->pdo->prepare(
            'DELETE FROM magic_link_requests
             WHERE requested_at < :cutoff'
        );
        $statement->execute(['cutoff' => $cutoff]);
    }
}
