<?php
// .back/repository/CompanyRepository.php

declare(strict_types=1);

class CompanyRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return CompanyModel[]
     */
    public function findAll(): array
    {
        $sql = "SELECT * FROM companies ORDER BY name ASC";
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map([CompanyModel::class, 'fromArray'], $rows);
    }

    /**
     * Fetches a single company by ID, returning a Model or null
     */
    public function findById(int $id): ?CompanyModel
    {
        $stmt = $this->pdo->prepare("SELECT * FROM companies WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? CompanyModel::fromArray($row) : null;
    }

    /**
     * Returns companies that have active offers
     * @return CompanyModel[]
     */
    public function getCompaniesWithActiveOffers(): array
    {
        $sql = "SELECT DISTINCT c.* FROM companies c
                INNER JOIN offers o ON c.id = o.company_id
                WHERE o.state = 'open'
                ORDER BY c.name ASC";
        
        $rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        return array_map([CompanyModel::class, 'fromArray'], $rows);
    }

    public function getAllCompagnies(): array
    {
        $sql = 'SELECT DISTICT c.* FROM companies c';

        $rows= $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        return array_map([CompanyModel::class,'fromArray'], $rows);
    }
}