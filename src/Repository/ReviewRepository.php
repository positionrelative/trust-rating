<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\CompanyStatistics;
use App\Entity\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

final class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    public function findLatest(?string $companyQuery = null, int $page = 1, int $perPage = 10): array
    {
        $queryBuilder = $this->createQueryBuilder('review')
            ->addSelect('company')
            ->join('review.company', 'company')
            ->orderBy('review.createdAt', 'DESC')
            ->addOrderBy('review.id', 'DESC')
            ->setFirstResult(max(0, $page - 1) * $perPage)
            ->setMaxResults($perPage);

        self::applyCompanyNameFilter($queryBuilder, $companyQuery);

        return $queryBuilder->getQuery()->getResult();
    }

    public function countLatest(?string $companyQuery = null): int
    {
        $queryBuilder = $this->createQueryBuilder('review')
            ->select('COUNT(review.id)')
            ->join('review.company', 'company');

        self::applyCompanyNameFilter($queryBuilder, $companyQuery);

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function findCompanyStatistics(): array
    {
        $rows = $this->createQueryBuilder('review')
            ->join('review.company', 'company')
            ->select('company.name AS companyName')
            ->addSelect('COUNT(review.id) AS reviewCount')
            ->addSelect('AVG(review.rating) AS averageRating')
            ->groupBy('company.id')
            ->addGroupBy('company.name')
            ->orderBy('averageRating', 'DESC')
            ->addOrderBy('reviewCount', 'DESC')
            ->addOrderBy('company.name', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map(
            static fn (array $row): CompanyStatistics => new CompanyStatistics(
                companyName: (string) $row['companyName'],
                reviewCount: (int) $row['reviewCount'],
                averageRating: (float) $row['averageRating'],
            ),
            $rows,
        );
    }

    private static function applyCompanyNameFilter(QueryBuilder $queryBuilder, ?string $companyQuery): void
    {
        if (null !== $companyQuery && '' !== $companyQuery) {
            $queryBuilder
                ->andWhere("LOWER(company.name) LIKE LOWER(:companyQuery) ESCAPE '!'")
                ->setParameter('companyQuery', '%'.self::escapeLike($companyQuery).'%');
        }
    }

    private static function escapeLike(string $value): string
    {
        return str_replace(
            ['!', '%', '_'],
            ['!!', '!%', '!_'],
            $value,
        );
    }
}
