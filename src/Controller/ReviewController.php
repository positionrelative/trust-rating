<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Review;
use App\Form\ReviewType;
use App\Repository\CompanyRepository;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ReviewController extends AbstractController
{
    private const REVIEWS_PER_PAGE = 10;

    #[Route('/', name: 'review_index', methods: ['GET'])]
    public function index(Request $request, ReviewRepository $reviewRepository): Response
    {
        $companyQuery = trim((string) $request->query->get('q', ''));
        $normalizedQuery = '' !== $companyQuery ? $companyQuery : null;

        $totalCount = $reviewRepository->countLatest($normalizedQuery);
        $totalPages = max(1, (int) ceil($totalCount / self::REVIEWS_PER_PAGE));
        $currentPage = min(max(1, $request->query->getInt('page', 1)), $totalPages);

        return $this->render('review/index.html.twig', [
            'reviews' => $reviewRepository->findLatest($normalizedQuery, $currentPage, self::REVIEWS_PER_PAGE),
            'companyQuery' => $companyQuery,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('/reviews/new', name: 'review_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, CompanyRepository $companyRepository): Response
    {
        $form = $this->createForm(ReviewType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $review = $form->getData();
            $companyName = (string) $form->get('companyName')->getData();

            $review->setCompany($companyRepository->findOrCreate($companyName));

            $entityManager->persist($review);
            $entityManager->flush();

            $this->addFlash('success', 'flash.review_submitted');

            return $this->redirectToRoute('review_index');
        }

        return $this->render('review/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/reviews/{id}', name: 'review_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Review $review): Response
    {
        return $this->render('review/show.html.twig', [
            'review' => $review,
        ]);
    }
}
