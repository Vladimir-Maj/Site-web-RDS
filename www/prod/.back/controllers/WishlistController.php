<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repository\WishlistRepository;
use Twig\Environment;
use App\Util;

class WishlistController extends BaseController
{
    public function __construct(
        private WishlistRepository $wishlistRepository,
        protected Environment $twig
    ) {
        parent::__construct($twig);
    }

    public function index(): void
    {
        $this->checkRole(['student']);

        $studentId = (int) Util::getUserId();
        $offers = $this->wishlistRepository->findOffersByStudent($studentId);

        echo $this->twig->render('wishlist/index.html.twig', [
            'offers' => $offers,
            'pageTitle' => 'Ma wishlist',
            'currentPage' => 'wishlist',
            'isPrivileged' => false,
        ]);
    }

    public function toggle(string $offerId): void
    {
        $this->checkRole(['student']);

        $studentId = (int) Util::getUserId();
        $offerIdInt = (int) $offerId;

        $alreadyExists = $this->wishlistRepository->exists($studentId, $offerIdInt);

        if ($alreadyExists) {
            $success = $this->wishlistRepository->remove($studentId, $offerIdInt);

            $_SESSION['flash_message'] = $success
                ? "L'offre a été retirée de votre wishlist."
                : "Impossible de retirer l'offre de votre wishlist.";
            $_SESSION['flash_type'] = $success ? 'info' : 'error';
        } else {
            $success = $this->wishlistRepository->add($studentId, $offerIdInt);

            $_SESSION['flash_message'] = $success
                ? "L'offre a bien été ajoutée à votre wishlist."
                : "Impossible d'ajouter l'offre à votre wishlist.";
            $_SESSION['flash_type'] = $success ? 'success' : 'error';
        }

        $redirect = $_SERVER['HTTP_REFERER'] ?? '/app/wishlist';
        header('Location: ' . $redirect);
        exit;
    }
}
