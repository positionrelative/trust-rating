<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\CompanyRepository;
use App\Repository\ReviewRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CompanyController extends AbstractController
{
    #[Route('/companies', name: 'company_index', methods: ['GET'])]
    public function index(ReviewRepository $reviewRepository): Response
    {
        return $this->render('company/index.html.twig', [
            'statistics' => $reviewRepository->findCompanyStatistics(),
        ]);
    }

    #[Route('/companies/autocomplete', name: 'company_autocomplete', methods: ['GET'])]
    public function autocomplete(Request $request, CompanyRepository $companyRepository): JsonResponse
    {
        $names = $companyRepository->findNamesMatching((string) $request->query->get('query', ''));

        return new JsonResponse([
            'results' => array_map(static fn (string $name): array => ['value' => $name, 'text' => $name], $names),
            'next_page' => null,
        ]);
    }
}
