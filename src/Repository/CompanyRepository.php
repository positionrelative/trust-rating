<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Company;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class CompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Company::class);
    }

    public function findOneByNameCaseInsensitive(string $name): ?Company
    {
        return $this->createQueryBuilder('company')
            ->andWhere('LOWER(company.name) = LOWER(:name)')
            ->setParameter('name', trim($name))
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOrCreate(string $name): Company
    {
        $existing = $this->findOneByNameCaseInsensitive($name);

        if (null !== $existing) {
            return $existing;
        }

        $company = new Company($name);
        $entityManager = $this->getEntityManager();
        $entityManager->persist($company);
        $entityManager->flush();

        return $company;
    }

    public function findNamesMatching(string $query, int $limit = 10): array
    {
        $queryBuilder = $this->createQueryBuilder('company')
            ->select('company.name AS name')
            ->orderBy('company.name', 'ASC')
            ->setMaxResults($limit);

        $trimmedQuery = trim($query);
        if ('' !== $trimmedQuery) {
            $queryBuilder
                ->andWhere('LOWER(company.name) LIKE LOWER(:query)')
                ->setParameter('query', '%'.$trimmedQuery.'%');
        }

        return array_column($queryBuilder->getQuery()->getArrayResult(), 'name');
    }
}
