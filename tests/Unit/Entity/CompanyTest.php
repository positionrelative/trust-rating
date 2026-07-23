<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Company;
use PHPUnit\Framework\TestCase;

final class CompanyTest extends TestCase
{
    public function testStoresTheGivenName(): void
    {
        $company = new Company('Test Kft');

        self::assertSame('Test Kft', $company->getName());
    }

    public function testTrimsSurroundingWhitespaceFromTheName(): void
    {
        $company = new Company('  Test Kft  ');

        self::assertSame('Test Kft', $company->getName());
    }
}
