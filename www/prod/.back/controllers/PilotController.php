<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repository\UserRepository;
use App\Repository\PromotionRepository; // Ajout du repository des promotions
use App\Util;
use Exception;
use Twig\Environment;

class PilotController extends BaseController
{
    // On ajoute PDO dans le constructeur
    public function __construct(
        private UserRepository $repo,
        protected Environment $twig,
        private \PDO $pdo 
    ) {
        parent::__construct($twig);
        if (Util::getCSRFToken() === null) {
            Util::setCSRFToken(bin2hex(random_bytes(32)));
        }
    }

    public function renderList(): void
    {
        $this->abortIfNotPriv();

        $filters = [
            'name'   => $_GET['name'] ?? null,
            'status' => $_GET['status'] ?? null,
            'page'   => (int) ($_GET['page'] ?? 1),
            'limit'  => 10
        ];

        $pilots = $this->repo->searchPilots($filters);

        echo $this->twig->render('pilots/pilot_list.html.twig', [
            'pilots'  => $pilots,
            'filters' => $filters,
            'sidebar_active' => 'pilots'
        ]);
    }

    public function renderEditForm(string $id): void
    {
        $this->abortIfNotPriv();
        
        $pilot = $this->repo->findById($id);
        if (!$pilot) {
            $this->abort(404, "Pilote introuvable.");
        }

        // 1. On récupère la promotion actuelle du pilote
        $currentPromo = $this->repo->getPromoByPilote($id);

        // 2. On récupère TOUTES les promotions pour le menu déroulant
        // (On utilise une requête directe simple ici pour éviter de modifier ton PromotionRepository)
        $stmt = $this->pdo->query("SELECT HEX(id) as id, label, academic_year FROM promotion ORDER BY academic_year DESC");
        $allPromotions = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        echo $this->twig->render('pilots/pilot_editor.html.twig', [
            'id'             => $id,
            'pilot'          => $pilot,
            'current_promo'  => $currentPromo,
            'all_promotions' => $allPromotions,
            'csrf_token'     => Util::getCSRFToken(),
            'error'          => $_SESSION['flash_error'] ?? null,
            'success'        => $_GET['success'] ?? null,
            'sidebar_active' => 'pilots'
        ]);
        unset($_SESSION['flash_error']);
    }

    public function handleUpdate(string $id): void
    {
        $this->abortIfNotPriv();
        try {
            $data = $_POST;
            
            // 1. Mise à jour des informations de base (Nom, Prénom, Statut)
            if (!$this->repo->updateUser($id, $data)) {
                throw new Exception("Erreur lors de la mise à jour de l'identité.");
            }

            // 2. Mise à jour du mot de passe (Seulement si le champ a été rempli)
            if (!empty($_POST['password'])) {
                $hashedPassword = password_hash($_POST['password'], PASSWORD_ARGON2ID); // Comme dans ton AuthController
                $this->repo->updatePassword($id, $hashedPassword);
            }

            // 3. Assignation d'une nouvelle promotion
            if (!empty($_POST['promotion_id'])) {
                $this->repo->assignPromotionToPilot($id, $_POST['promotion_id']);
            }

            header("Location: /dashboard/pilotes/{$id}?success=1");
            exit;

        } catch (Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}