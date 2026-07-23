<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Company;
use App\Entity\Review;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ReviewSubmissionTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->createQuery('DELETE FROM App\Entity\Review')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Company')->execute();
    }

    protected function tearDown(): void
    {
        $this->entityManager->close();

        parent::tearDown();
    }

    private function countPersistedReviews(): int
    {
        return \count($this->entityManager->getRepository(Review::class)->findAll());
    }

    public function testSuccessfulSubmissionPersistsReviewAndShowsFlashMessage(): void
    {
        $this->client->request('GET', '/reviews/new');

        $this->client->submitForm('Vélemény beküldése', [
            'review[companyName]' => 'Acme Ltd',
            'review[rating]' => '5',
            'review[reviewText]' => 'Great service, highly recommend working with them.',
            'review[authorEmail]' => 'reviewer@example.com',
        ]);

        self::assertResponseRedirects('/');
        $this->client->followRedirect();

        self::assertSelectorTextContains('.flash--success', 'Köszönjük a véleményed!');

        $reviews = $this->entityManager->getRepository(Review::class)->findAll();

        self::assertCount(1, $reviews);
        self::assertSame('Acme Ltd', $reviews[0]->getCompany()->getName());
        self::assertSame(5, $reviews[0]->getRating());
        self::assertSame('Great service, highly recommend working with them.', $reviews[0]->getReviewText());
        self::assertSame('reviewer@example.com', $reviews[0]->getAuthorEmail());
    }

    public function testSubmittingTheSameCompanyNameTwiceReusesOneCompany(): void
    {
        $this->client->request('GET', '/reviews/new');
        $this->client->submitForm('Vélemény beküldése', [
            'review[companyName]' => 'Acme Ltd',
            'review[rating]' => '5',
            'review[reviewText]' => 'First review for this company.',
            'review[authorEmail]' => 'first@example.com',
        ]);

        $this->client->request('GET', '/reviews/new');
        $this->client->submitForm('Vélemény beküldése', [
            'review[companyName]' => '  ACME LTD  ',
            'review[rating]' => '4',
            'review[reviewText]' => 'Second review, different casing and whitespace.',
            'review[authorEmail]' => 'second@example.com',
        ]);

        self::assertCount(1, $this->entityManager->getRepository(Company::class)->findAll());
        self::assertCount(2, $this->entityManager->getRepository(Review::class)->findAll());
    }

    public function testInvalidEmailIsRejectedAndNotPersisted(): void
    {
        $this->client->request('GET', '/reviews/new');

        $this->client->submitForm('Vélemény beküldése', [
            'review[companyName]' => 'Acme Ltd',
            'review[rating]' => '5',
            'review[reviewText]' => 'Great service, highly recommend working with them.',
            'review[authorEmail]' => 'not-an-email',
        ]);

        self::assertResponseIsUnprocessable();
        self::assertSelectorExists('.form-errors');
        self::assertSame(0, $this->countPersistedReviews());
    }

    public function testInvalidRatingIsRejectedAndNotPersisted(): void
    {
        $crawler = $this->client->request('GET', '/reviews/new');

        $form = $crawler->selectButton('Vélemény beküldése')->form([
            'review[companyName]' => 'Acme Ltd',
            'review[reviewText]' => 'Great service, highly recommend working with them.',
            'review[authorEmail]' => 'reviewer@example.com',
        ]);

        $values = $form->getPhpValues();
        $values['review']['rating'] = '99';

        $this->client->request($form->getMethod(), $form->getUri(), $values);

        self::assertResponseIsUnprocessable();
        self::assertSelectorExists('.form-errors');
        self::assertSame(0, $this->countPersistedReviews());
    }

    public function testMissingRequiredFieldsAreRejectedAndNotPersisted(): void
    {
        $this->client->request('GET', '/reviews/new');

        $this->client->submitForm('Vélemény beküldése', [
            'review[companyName]' => '',
            'review[rating]' => '5',
            'review[reviewText]' => '',
            'review[authorEmail]' => 'reviewer@example.com',
        ]);

        self::assertResponseIsUnprocessable();
        self::assertSelectorExists('.form-errors');
        self::assertSame(0, $this->countPersistedReviews());
    }
}
