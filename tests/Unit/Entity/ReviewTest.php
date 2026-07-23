<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Company;
use App\Entity\Review;
use PHPUnit\Framework\TestCase;

final class ReviewTest extends TestCase
{
    private function createReview(
        Company $company = new Company('Test Kft'),
        int $rating = 5,
        string $reviewText = 'Excellent service.',
        string $authorEmail = 'reviewer@example.com',
    ): Review {
        return new Review($company, $rating, $reviewText, $authorEmail);
    }

    public function testCreatesAReviewWithValidValues(): void
    {
        $company = new Company('Test Kft');
        $review = $this->createReview(company: $company);

        self::assertSame($company, $review->getCompany());
        self::assertSame(5, $review->getRating());
        self::assertSame('Excellent service.', $review->getReviewText());
        self::assertSame('reviewer@example.com', $review->getAuthorEmail());
    }

    public function testAssignsCreatedAtAndUpdatedAtOnConstruction(): void
    {
        $before = new \DateTimeImmutable();
        $review = $this->createReview();
        $after = new \DateTimeImmutable();

        self::assertGreaterThanOrEqual($before, $review->getCreatedAt());
        self::assertLessThanOrEqual($after, $review->getCreatedAt());
    }

    public function testInitialTimestampsAreConsistent(): void
    {
        $review = $this->createReview();

        self::assertEquals($review->getCreatedAt(), $review->getUpdatedAt());
    }

    public function testRejectsRatingBelowOne(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->createReview(rating: 0);
    }

    public function testRejectsRatingAboveFive(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->createReview(rating: 6);
    }

    public function testAcceptsBoundaryRatingOfOne(): void
    {
        $review = $this->createReview(rating: 1);

        self::assertSame(1, $review->getRating());
    }

    public function testAcceptsBoundaryRatingOfFive(): void
    {
        $review = $this->createReview(rating: 5);

        self::assertSame(5, $review->getRating());
    }

    public function testTrimsSurroundingWhitespaceFromTextualFields(): void
    {
        $review = $this->createReview(reviewText: '  Excellent service.  ', authorEmail: '  reviewer@example.com  ');

        self::assertSame('Excellent service.', $review->getReviewText());
        self::assertSame('reviewer@example.com', $review->getAuthorEmail());
    }

    public function testSetRatingRejectsInvalidValueAfterConstruction(): void
    {
        $review = $this->createReview();

        $this->expectException(\InvalidArgumentException::class);

        $review->setRating(0);
    }

    public function testSetCompanyReplacesTheAssociatedCompany(): void
    {
        $review = $this->createReview(company: new Company('Old Name'));
        $newCompany = new Company('New Name');

        $review->setCompany($newCompany);

        self::assertSame($newCompany, $review->getCompany());
    }
}
