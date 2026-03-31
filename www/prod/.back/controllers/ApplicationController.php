<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\ApplicationModel;
use App\Repository\ApplicationRepository;
use Twig\Environment;
use App\Util;

class ApplicationController extends BaseController
{
    private ApplicationRepository $repo;

    public function __construct(ApplicationRepository $repo, Environment $twig)
    {
        parent::__construct($twig);
        $this->repo = $repo;
    }

    // --- SECTION : VUES WEB (Rendu Twig) ---

    /**
     * GET /app/apply/{offerId}
     * Affiche le formulaire de candidature pour une offre spécifique
     */
    public function viewApply(string $offerId): void
    {
        echo $this->twig->render('applications/apply.html.twig', [
            'offer_id' => $offerId
        ]);
    }

    /**
     * POST /app/apply/submit
     * Traitement classique de formulaire avec redirection
     */
    public function doApply(): void
    {
        $application = ApplicationModel::fromArray([
            'student_id'        => Util::getUserId(),
            'offer_id'          => $_POST['offer_id'] ?? '',
            'cv_path'           => $_POST['cv_path'] ?? null,
            'cover_letter_path' => $_POST['cover_letter_path'] ?? null,
            'status'            => 'pending'
        ]);

        if ($this->repo->push($application)) {
            header('Location: /app/my-applications?status=success');
        } else {
            header('Location: /app/apply/' . $application->offer_id . '?error=save_failed');
        }
        exit;
    }

    // --- SECTION : API AJAX (JSON) ---

    /**
     * POST /api/apply
     * Création d'une candidature via AJAX
     */
    public function applyAjax(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['offer_id'])) {
            $this->jsonResponse(['error' => 'ID de l\'offre manquant'], 400);
        }

        $application = ApplicationModel::fromArray([
            'student_id'        => Util::getUserId(),
            'offer_id'          => $input['offer_id'],
            'cv_path'           => $input['cv_path'] ?? null,
            'cover_letter_path' => $input['cover_letter_path'] ?? null,
            'status'            => 'pending'
        ]);

        if ($this->repo->push($application)) {
            $this->jsonResponse(['status' => 'success', 'data' => $application], 201);
        }

        $this->jsonResponse(['error' => 'Erreur lors de la sauvegarde'], 500);
    }

    /**
     * DELETE /api/applications/{id}
     * Suppression AJAX avec vérification de propriété
     */
    public function deleteAjax(string $id): void
    {
        $app = $this->repo->findById($id);

        if (!$app) {
            $this->jsonResponse(['error' => 'Candidature introuvable'], 404);
        }

        // Sécurité résiduelle : Seul le propriétaire (ou un admin via le routeur) peut supprimer
        if (!$this->repo->isOwner($app->id, $app->student_id) || $this->isSuperUser()) {
            $this->jsonResponse(['error' => 'Action non autorisée'], 403);
        }

        $this->repo->delete($id) 
            ? $this->jsonResponse(['status' => 'deleted']) 
            : $this->jsonResponse(['error' => 'Échec de la suppression'], 500);
    }

    /**
     * GET /api/applications/{id}
     * Récupère les détails d'une candidature en JSON
     */
    public function showJson(string $id): void
    {
        $app = $this->repo->findById($id);
        
        $app ? $this->jsonResponse($app) : $this->jsonResponse(['error' => 'Not found'], 404);
    }

    /**
     * PATCH /api/applications/status
     * Mise à jour du statut par un Pilote ou Admin
     */
    public function updateStatusAjax(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        $status = $input['status'] ?? null;

        if (!$id || !$status) {
            $this->jsonResponse(['error' => 'Données incomplètes'], 400);
        }

        $app = $this->repo->findById($id);
        if (!$app) {
            $this->jsonResponse(['error' => 'Inexistant'], 404);
        }

        $app->status = $status;
        $this->repo->push($app); // Utilise push pour l'Update

        $this->jsonResponse(['status' => 'updated', 'new_status' => $status]);
    }
}