<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\RoleEnum;
use App\Repository\CompanyRepository;
use App\Repository\OfferRepository;
use App\Util;
use Twig\Environment;

class OfferController extends BaseController
{
    private OfferRepository $offerRepository;
    private CompanyRepository $companyRepository;

    public function __construct(OfferRepository $offerRepository, CompanyRepository $companyRepository, Environment $twig)
    {
        $this->offerRepository = $offerRepository;
        $this->companyRepository = $companyRepository;
        $this->twig = $twig;
    }

    public function index(): void
    {
        $filters = [
            'keyword' => trim($_GET['keyword'] ?? ''),
            'location' => trim($_GET['location'] ?? ''),
            'company_id' => trim($_GET['company_id'] ?? ''),
            'duration' => trim($_GET['duration'] ?? ''),
            'sort' => trim($_GET['sort'] ?? 'recent'),
        ];

        $offers = $this->offerRepository->search($filters);
        $locations = $this->offerRepository->getUniqueLocations();

        echo $this->twig->render('offers/offer_search.html.twig', [
            'offers' => $offers,
            'count' => count($offers),
            'filters' => $filters,
            'locations' => $locations,
            'companies' => $this->offerRepository->getCompaniesWithActiveOffers(),
            // The current data model has no contract type column yet.
            'jobTypes' => [],
            'currentPage' => 'search',
            'user' => Util::getUser() ?? null,
        ]);
    }

    public function publish(): void
    {
        if (!Util::isLoggedIn()) {
            header('Location: /login');
            exit;
        }

        $role = Util::getRole();
        if ($role !== RoleEnum::Admin && $role !== RoleEnum::Pilote) {
            http_response_code(403);
            die('Acces refuse.');
        }

        echo $this->twig->render('offers/offer_editor.html.twig', [
            // Reuse existing company list so the page is migrated to MVC routes.
            'companies' => $this->offerRepository->getCompaniesWithActiveOffers(),
            'error' => null,
            'currentPage' => 'editor',
            'user' => Util::getUser() ?? null,
        ]);
    }

    public function show(string $id): void
    {
        $offer = $this->offerRepository->findById($id);
        if ($offer === null) {
            http_response_code(404);
            die('Offre introuvable.');
        }

        echo $this->twig->render('offers/offer_detail.html.twig', [
            'offer' => $offer,
            'alreadyApplied' => false,
            'currentPage' => 'search',
            'user' => Util::getUser() ?? null,
        ]);
    }
}