<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CompanyAutocompleteTest extends WebTestCase
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

    private function decodeJsonResponse(): array
    {
        return json_decode((string) $this->client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
    }

    public function testAutocompleteReturnsMatchingCompanyNames(): void
    {
        $this->companyRepository->findOrCreate('Alpha Consulting');
        $this->companyRepository->findOrCreate('Beta Solutions');

        $this->client->request('GET', '/companies/autocomplete', ['query' => 'alp']);

        self::assertResponseIsSuccessful();
        $payload = $this->decodeJsonResponse();

        self::assertSame([['value' => 'Alpha Consulting', 'text' => 'Alpha Consulting']], $payload['results']);
    }

    public function testAutocompleteIsCaseInsensitive(): void
    {
        $this->companyRepository->findOrCreate('Alpha Consulting');

        $this->client->request('GET', '/companies/autocomplete', ['query' => 'ALPHA']);

        self::assertSame(
            ['Alpha Consulting'],
            array_column($this->decodeJsonResponse()['results'], 'value'),
        );
    }

    public function testAutocompleteWithEmptyQueryReturnsAllCompaniesAlphabetically(): void
    {
        $this->companyRepository->findOrCreate('Beta Solutions');
        $this->companyRepository->findOrCreate('Alpha Consulting');

        $this->client->request('GET', '/companies/autocomplete', ['query' => '']);

        self::assertSame(
            ['Alpha Consulting', 'Beta Solutions'],
            array_column($this->decodeJsonResponse()['results'], 'value'),
        );
    }

    public function testAutocompleteReturnsEmptyResultsForNoMatch(): void
    {
        $this->companyRepository->findOrCreate('Alpha Consulting');

        $this->client->request('GET', '/companies/autocomplete', ['query' => 'no-such-company']);

        self::assertResponseIsSuccessful();
        self::assertSame([], $this->decodeJsonResponse()['results']);
    }
}
