<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class CompanyStatistics
{
    public function __construct(
        public string $companyName,
        public int $reviewCount,
        public float $averageRating,
    ) {
    }
}
