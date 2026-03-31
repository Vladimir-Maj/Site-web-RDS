<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\SkillModel;
use App\Repository\SkillRepository;
use Twig\Environment;
use App\Util;

class SkillController extends BaseController
{
    private SkillRepository $repo;

    public function __construct(SkillRepository $repo, Environment $twig)
    {
        parent::__construct($twig);
        $this->repo = $repo;
    }

    /**
     * GET /app/skills
     * Displays the management list of all skills
     */
    /**
     * GET /app/skills
     * Displays the management list of all skills
     */
    public function index(): void
    {
        $this->abortIfNotPriv();

        $skills = $this->repo->findAll();

        echo $this->twig->render('skills/index.html.twig', [
            'skills' => $skills,
            // Explicitly pass GET parameters to the template
            'status' => $_GET['status'] ?? null,
            'error' => $_GET['error'] ?? null
        ]);
    }

    /**
     * POST /app/skills/save
     * Handles both creation (ID null) and update
     */
    public function handleSave(): void
    {
        $this->abortIfNotPriv();

        $id = $_POST['id'] ?? null;
        $label = trim($_POST['label'] ?? '');

        if (empty($label)) {
            // Usually, you'd redirect back with an error flash message
            header('Location: /app/skills?error=label_required');
            exit;
        }

        $skill = new SkillModel();
        $skill->id = $id ?: null; // Hex string or null
        $skill->label = $label;

        $success = $this->repo->pushSkill($skill);

        if ($success) {
            header('Location: /app/skills?status=success');
        } else {
            header('Location: /app/skills?error=save_failed');
        }
        exit;
    }

    /**
     * POST /app/skills/delete/([a-fA-F0-9]{32})
     */
    public function delete(string $hexId): void
    {
        $this->abortIfNotPriv();

        // Check if the skill exists before deleting
        $skill = $this->repo->getById($hexId);
        if (!$skill) {
            $this->abort(404, "Skill not found.");
        }

        $this->repo->deleteById($hexId);

        header('Location: /app/skills?status=deleted');
        exit;
    }

    /**
     * GET /api/skills
     * API Endpoint for dynamic search or tag suggestions
     */
    public function listJson(): void
    {
        $skills = $this->repo->findAll();

        header('Content-Type: application/json');
        echo json_encode($skills);
        exit;
    }
    /**
     * POST /api/skills/create
     * Crée une nouvelle compétence via AJAX
     */
    public function createAjax(): void
    {
        header('Content-Type: application/json');

        // On récupère les données JSON envoyées dans le corps de la requête
        $input = json_decode(file_get_contents('php://input'), true);
        $label = trim($input['label'] ?? '');

        if (empty($label)) {
            http_response_code(400);
            echo json_encode(['error' => 'Le label est requis']);
            exit;
        }

        $skill = new SkillModel();
        $skill->label = $label;

        // On suppose que pushSkill retourne le nouveau SkillModel ou l'ID en cas de succès
        $success = $this->repo->pushSkill($skill);

        if ($success) {
            http_response_code(201); // Created
            echo json_encode([
                'status' => 'success',
                'skill' => $skill
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la sauvegarde']);
        }
        exit;
    }

    /**
     * DELETE /api/skills/delete/{hexId}
     * Supprime une compétence via une requête DELETE
     */
    public function deleteAjax(string $hexId): void
    {
        header('Content-Type: application/json');

        $skill = $this->repo->getById($hexId);
        if (!$skill) {
            http_response_code(404);
            echo json_encode(['error' => 'Compétence non trouvée']);
            exit;
        }

        $deleted = $this->repo->deleteById($hexId);

        if ($deleted) {
            echo json_encode(['status' => 'deleted', 'id' => $hexId]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Échec de la suppression']);
        }
        exit;
    }

    /**
     * PATCH /api/skills/update/{hexId}
     * Met à jour partiellement une compétence
     */
    public function updateAjax(string $hexId): void
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);
        $label = trim($input['label'] ?? '');

        $skill = $this->repo->getById($hexId);
        if (!$skill) {
            http_response_code(404);
            echo json_encode(['error' => 'Compétence non trouvée']);
            exit;
        }

        if (empty($label)) {
            http_response_code(400);
            echo json_encode(['error' => 'Le label ne peut pas être vide']);
            exit;
        }

        $skill->label = $label;
        $success = $this->repo->pushSkill($skill);

        if ($success) {
            echo json_encode(['status' => 'updated', 'skill' => $skill]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Échec de la mise à jour']);
        }
        exit;
    }
}