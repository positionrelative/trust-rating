<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\Review;
use App\Repository\CompanyRepository;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ReviewRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private ReviewRepository $reviewRepository;
    private CompanyRepository $companyRepository;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->reviewRepository = $container->get(ReviewRepository::class);
        $this->companyRepository = $container->get(CompanyRepository::class);

        $this->entityManager->createQuery('DELETE FROM App\Entity\Review')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Company')->execute();
    }

    protected function tearDown(): void
    {
        $this->entityManager->close();

        parent::tearDown();
    }

    private function persistReview(string $companyName, int $rating): Review
    {
        $review = new Review(
            $this->companyRepository->findOrCreate($companyName),
            $rating,
            'A review text long enough to be meaningful.',
            'reviewer@example.com',
        );
        $this->entityManager->persist($review);

        return $review;
    }

    public function testFindCompanyStatisticsReturnsEmptyListForEmptyDatabase(): void
    {
        self::assertSame([], $this->reviewRepository->findCompanyStatistics());
    }

    public function testFindCompanyStatisticsCalculatesAverageAndDeterministicOrdering(): void
    {
        $this->persistReview('Alpha', 5);
        $this->persistReview('Alpha', 5);
        $this->persistReview('Beta', 5);
        $this->persistReview('Gamma', 4);
        $this->persistReview('Gamma', 3);
        $this->persistReview('Delta', 3);
        $this->persistReview('Delta', 4);
        $this->entityManager->flush();

        $statistics = $this->reviewRepository->findCompanyStatistics();

        self::assertCount(4, $statistics);

        self::assertSame('Alpha', $statistics[0]->companyName);
        self::assertSame(2, $statistics[0]->reviewCount);
        self::assertEqualsWithDelta(5.0, $statistics[0]->averageRating, 0.001);

        self::assertSame('Beta', $statistics[1]->companyName);
        self::assertSame(1, $statistics[1]->reviewCount);
        self::assertEqualsWithDelta(5.0, $statistics[1]->averageRating, 0.001);

        self::assertSame('Delta', $statistics[2]->companyName);
        self::assertSame(2, $statistics[2]->reviewCount);
        self::assertEqualsWithDelta(3.5, $statistics[2]->averageRating, 0.001);

        self::assertSame('Gamma', $statistics[3]->companyName);
        self::assertSame(2, $statistics[3]->reviewCount);
        self::assertEqualsWithDelta(3.5, $statistics[3]->averageRating, 0.001);
    }

    public function testFindLatestOrdersNewestFirstWithDeterministicSecondaryOrder(): void
    {
        $first = $this->persistReview('Acme', 4);
        $this->entityManager->flush();

        $second = $this->persistReview('Acme', 5);
        $this->entityManager->flush();

        $reviews = $this->reviewRepository->findLatest();

        self::assertCount(2, $reviews);
        self::assertSame($second->getId(), $reviews[0]->getId());
        self::assertSame($first->getId(), $reviews[1]->getId());
    }

    public function testFindLatestFiltersByPartialCaseInsensitiveCompanyName(): void
    {
        $this->persistReview('Alpha Consulting', 5);
        $this->persistReview('Beta Solutions', 4);
        $this->entityManager->flush();

        $reviews = $this->reviewRepository->findLatest('alp');

        self::assertCount(1, $reviews);
        self::assertSame('Alpha Consulting', $reviews[0]->getCompany()->getName());
    }

    public function testFindLatestReturnsEmptyListForNoMatch(): void
    {
        $this->persistReview('Alpha Consulting', 5);
        $this->entityManager->flush();

        self::assertSame([], $this->reviewRepository->findLatest('no-such-company'));
    }

    public function testFindLatestTreatsEmptyQueryAsNoFilter(): void
    {
        $this->persistReview('Alpha Consulting', 5);
        $this->persistReview('Beta Solutions', 4);
        $this->entityManager->flush();

        self::assertCount(2, $this->reviewRepository->findLatest(''));
    }

    public function testFindLatestParameterizesSpecialCharactersSafely(): void
    {
        $this->persistReview('Alpha % Co', 5);
        $this->entityManager->flush();

        self::assertSame([], $this->reviewRepository->findLatest("'; DROP TABLE review; --"));
    }

    public function testFindLatestTreatsPercentAndUnderscoreAsLiteralCharacters(): void
    {
        $this->persistReview('50% Off Store', 5);
        $this->persistReview('50X Off Store', 4);
        $this->entityManager->flush();

        $reviews = $this->reviewRepository->findLatest('50%');

        self::assertCount(1, $reviews);
        self::assertSame('50% Off Store', $reviews[0]->getCompany()->getName());
    }

    public function testFindLatestPaginatesResults(): void
    {
        for ($i = 1; $i <= 15; ++$i) {
            $this->persistReview('Company '.$i, 5);
        }
        $this->entityManager->flush();

        $firstPage = $this->reviewRepository->findLatest(page: 1, perPage: 10);
        $secondPage = $this->reviewRepository->findLatest(page: 2, perPage: 10);

        self::assertCount(10, $firstPage);
        self::assertCount(5, $secondPage);
        self::assertNotSame($firstPage[0]->getId(), $secondPage[0]->getId());
    }

    public function testCountLatestMatchesTheFilteredTotal(): void
    {
        $this->persistReview('Alpha Consulting', 5);
        $this->persistReview('Beta Solutions', 4);
        $this->entityManager->flush();

        self::assertSame(2, $this->reviewRepository->countLatest());
        self::assertSame(1, $this->reviewRepository->countLatest('alp'));
        self::assertSame(0, $this->reviewRepository->countLatest('no-such-company'));
    }

    public function testFindOrCreateReusesAnExistingCompanyCaseInsensitively(): void
    {
        $original = $this->companyRepository->findOrCreate('Acme Ltd');
        $this->entityManager->flush();

        $reused = $this->companyRepository->findOrCreate('ACME LTD');

        self::assertSame($original->getId(), $reused->getId());
    }
}
