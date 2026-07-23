<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Review;
use App\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ReviewPagesTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private CompanyRepository $companyRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->companyRepository = static::getContainer()->get(CompanyRepository::class);
        $this->entityManager->createQuery('DELETE FROM App\Entity\Review')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Company')->execute();
    }

    protected function tearDown(): void
    {
        $this->entityManager->close();

        parent::tearDown();
    }

    private function persistReview(string $companyName, int $rating, string $reviewText, string $authorEmail): Review
    {
        $review = new Review($this->companyRepository->findOrCreate($companyName), $rating, $reviewText, $authorEmail);
        $this->entityManager->persist($review);
        $this->entityManager->flush();

        return $review;
    }

    public function testIndexShowsEmptyStateWhenThereAreNoReviews(): void
    {
        $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.empty-state');
    }

    public function testIndexListsReviewsNewestFirstWithStarsAndTruncatedText(): void
    {
        $this->persistReview('Older Corp', 3, 'An older review text.', 'first@example.com');
        $longText = str_repeat('A very long review sentence. ', 20);
        $this->persistReview('Newer Corp', 5, $longText, 'second@example.com');

        $crawler = $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();

        $companies = $crawler->filter('.review-card__company')->each(static fn ($node) => $node->text());
        self::assertSame(['Newer Corp', 'Older Corp'], $companies);

        self::assertSelectorExists('.stars');
        self::assertSelectorExists('a[href*="/reviews/"]');

        $renderedText = $crawler->filter('.review-card__text')->first()->text();
        self::assertLessThan(\strlen($longText), \strlen($renderedText));
        self::assertStringEndsWith('…', trim($renderedText));
    }

    public function testIndexFiltersBySearchTermAndPreservesItInTheForm(): void
    {
        $this->persistReview('Alpha Consulting', 5, 'Great alpha experience.', 'a@example.com');
        $this->persistReview('Beta Solutions', 4, 'Great beta experience.', 'b@example.com');

        $crawler = $this->client->request('GET', '/', ['q' => 'alp']);

        self::assertResponseIsSuccessful();
        $companies = $crawler->filter('.review-card__company')->each(static fn ($node) => $node->text());
        self::assertSame(['Alpha Consulting'], $companies);
        self::assertSame('alp', $crawler->filter('input[name="q"]')->attr('value'));
    }

    public function testIndexShowsNoResultsStateForNonMatchingSearch(): void
    {
        $this->persistReview('Alpha Consulting', 5, 'Great alpha experience.', 'a@example.com');

        $this->client->request('GET', '/', ['q' => 'no-such-company']);

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.empty-state');
    }

    public function testIndexPaginatesTheReviewList(): void
    {
        for ($i = 1; $i <= 15; ++$i) {
            $this->persistReview('Company '.$i, 5, 'Review text number '.$i.'.', 'reviewer'.$i.'@example.com');
        }

        $firstPageCrawler = $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertCount(10, $firstPageCrawler->filter('.review-card__company'));
        self::assertSelectorTextContains('.pagination__status', '1. oldal / 2');
        self::assertStringNotContainsString('Előző', $firstPageCrawler->filter('.pagination')->text());

        $secondPageCrawler = $this->client->request('GET', '/', ['page' => 2]);

        self::assertResponseIsSuccessful();
        self::assertCount(5, $secondPageCrawler->filter('.review-card__company'));
        self::assertSelectorTextContains('.pagination__status', '2. oldal / 2');
    }

    public function testShowDisplaysFullReviewWithoutExposingAuthorEmail(): void
    {
        $longText = str_repeat('Full detail text. ', 30);
        $review = $this->persistReview('Detail Corp', 4, $longText, 'private@example.com');

        $crawler = $this->client->request('GET', '/reviews/'.$review->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Detail Corp');
        self::assertStringContainsString($longText, $crawler->filter('body')->text());
        self::assertSelectorExists('.stars');
        self::assertStringNotContainsString('private@example.com', $crawler->filter('body')->text());
    }

    public function testShowReturnsNotFoundForUnknownId(): void
    {
        $this->client->request('GET', '/reviews/999999');

        self::assertResponseStatusCodeSame(404);
    }

    public function testCompanyStatisticsPageRendersRows(): void
    {
        $this->persistReview('Alpha Consulting', 5, 'Great experience.', 'a@example.com');
        $this->persistReview('Alpha Consulting', 5, 'Another great one.', 'a2@example.com');

        $crawler = $this->client->request('GET', '/companies');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('table.stats-table', 'Alpha Consulting');
        self::assertSelectorTextContains('table.stats-table', '5.00');
    }

    public function testCompanyStatisticsPageShowsEmptyStateWhenNoReviews(): void
    {
        $this->client->request('GET', '/companies');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.empty-state');
    }
}
