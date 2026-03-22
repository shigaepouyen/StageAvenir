<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class CompanyRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByUserId(int $userId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, user_id, siret, name, naf_code, address, lat, lng
             FROM companies
             WHERE user_id = :user_id
             LIMIT 1'
        );
        $statement->execute(['user_id' => $userId]);
        $company = $statement->fetch();

        return $company === false ? null : $company;
    }

    public function findBySiret(string $siret): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, user_id, siret, name, naf_code, address, lat, lng
             FROM companies
             WHERE siret = :siret
             LIMIT 1'
        );
        $statement->execute(['siret' => $siret]);
        $company = $statement->fetch();

        return $company === false ? null : $company;
    }

    public function create(int $userId, string $siret): int
    {
        return $this->createProfile($userId, [
            'siret' => $siret,
            'name' => null,
            'naf_code' => null,
            'address' => null,
            'lat' => null,
            'lng' => null,
        ]);
    }

    public function createProfile(int $userId, array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO companies (user_id, siret, name, naf_code, address, lat, lng)
             VALUES (:user_id, :siret, :name, :naf_code, :address, :lat, :lng)'
        );
        $statement->execute([
            'user_id' => $userId,
            'siret' => $data['siret'],
            'name' => $data['name'],
            'naf_code' => $data['naf_code'],
            'address' => $data['address'],
            'lat' => $data['lat'],
            'lng' => $data['lng'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateSiretByUserId(int $userId, string $siret): void
    {
        $this->updateProfileByUserId($userId, [
            'siret' => $siret,
            'name' => null,
            'naf_code' => null,
            'address' => null,
            'lat' => null,
            'lng' => null,
        ], true);
    }

    public function updateProfileByUserId(int $userId, array $data, bool $preserveExisting = false): void
    {
        if ($preserveExisting) {
            $statement = $this->pdo->prepare(
                'UPDATE companies
                 SET siret = :siret,
                     name = COALESCE(name, :name),
                     naf_code = COALESCE(naf_code, :naf_code),
                     address = COALESCE(address, :address),
                     lat = COALESCE(lat, :lat),
                     lng = COALESCE(lng, :lng)
                 WHERE user_id = :user_id'
            );
        } else {
            $statement = $this->pdo->prepare(
                'UPDATE companies
                 SET siret = :siret,
                     name = :name,
                     naf_code = :naf_code,
                     address = :address,
                     lat = :lat,
                     lng = :lng
                 WHERE user_id = :user_id'
            );
        }

        $statement->execute([
            'user_id' => $userId,
            'siret' => $data['siret'],
            'name' => $data['name'],
            'naf_code' => $data['naf_code'],
            'address' => $data['address'],
            'lat' => $data['lat'],
            'lng' => $data['lng'],
        ]);
    }
}
