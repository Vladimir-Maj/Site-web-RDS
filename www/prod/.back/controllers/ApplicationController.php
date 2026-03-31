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
     * GET /app/offers/{id}/apply
     * Affiche le formulaire de candidature
     */
    public function viewApply(string $id): void
    {
        echo $this->twig->render('applications/apply.html.twig', [
            'offer_id' => $id
        ]);
    }

    /**
     * POST /app/offers/{id}/apply
     * Traitement classique de formulaire avec redirection
     */
    public function doApply(string $id): void
    {
        $application = ApplicationModel::fromArray([
            'student_id'        => Util::getUserId(),
            'offer_id'          => $id,
            'cv_path'           => $_POST['cv_path'] ?? null,
            'cover_letter_path' => $_POST['cover_letter_path'] ?? null,
            'status'            => 'pending'
        ]);

        if ($this->repo->push($application)) {
            header('Location: /app/my-applications?status=success');
        } else {
            header("Location: /app/offers/{$id}/apply?error=save_failed");
        }
        exit;
    }

    // --- SECTION : API AJAX (JSON) ---

    /**
     * POST /api/offers/{id}/apply
     * Création d'une candidature via AJAX
     */
    public function applyAjax(string $id): void
    {
        $input = json_decode(file_get_contents('php://input'), true);

        $application = ApplicationModel::fromArray([
            'student_id'        => Util::getUserId(),
            'offer_id'          => $id,
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
     */
    public function deleteAjax(string $id): void
    {
        $app = $this->repo->findById($id);

        if (!$app) {
            $this->jsonResponse(['error' => 'Candidature introuvable'], 404);
        }

        // Sécurité : Autoriser si c'est le propriétaire OU un utilisateur privilégié (Admin/Pilote)
        if ($app->student_id !== Util::getUserId() && !$this->isPrivileged()) {
            $this->jsonResponse(['error' => 'Action non autorisée'], 403);
        }

        $this->repo->delete($id) 
            ? $this->jsonResponse(['status' => 'deleted']) 
            : $this->jsonResponse(['error' => 'Échec de la suppression'], 500);
    }

    /**
     * GET /api/applications/{id}
     */
    public function showJson(string $id): void
    {
        $app = $this->repo->findById($id);
        
        if (!$app) {
            $this->jsonResponse(['error' => 'Not found'], 404);
        }

        $this->jsonResponse($app);
    }

    /**
     * PATCH /api/applications/{id}/status
     */
    public function updateStatusAjax(string $id): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $status = $input['status'] ?? null;

        if (!$status) {
            $this->jsonResponse(['error' => 'Statut manquant'], 400);
        }

        $app = $this->repo->findById($id);
        if (!$app) {
            $this->jsonResponse(['error' => 'Candidature inexistante'], 404);
        }

        $app->status = $status;
        $this->repo->push($app); 

        $this->jsonResponse(['status' => 'updated', 'new_status' => $status]);
    }
}