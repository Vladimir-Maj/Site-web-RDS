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
    public function index(): void
    {
        $this->abortIfNotPriv();

        $skills = $this->repo->findAll();

        echo $this->twig->render('skills/index.html.twig', [
            'skills' => $skills,
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

        $id = $_POST['id_skill'] ?? ($_POST['id'] ?? null);
        $label = trim($_POST['label_skill'] ?? ($_POST['label'] ?? ''));

        if (empty($label)) {
            header('Location: /app/skills?error=label_required');
            exit;
        }

        $skill = new SkillModel();
        $skill->id_skill = ($id !== null && $id !== '') ? (int) $id : null;
        $skill->label_skill = $label;

        $success = $this->repo->pushSkill($skill);

        if ($success) {
            header('Location: /app/skills?status=success');
        } else {
            header('Location: /app/skills?error=save_failed');
        }
        exit;
    }

    /**
     * POST /app/skills/delete/{id}
     */
    public function delete(int $id): void
    {
        $this->abortIfNotPriv();

        $skill = $this->repo->getById($id);
        if (!$skill) {
            $this->abort(404, "Skill not found.");
        }

        $this->repo->deleteById($id);

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

        $input = json_decode(file_get_contents('php://input'), true);
        $label = trim($input['label_skill'] ?? ($input['label'] ?? ''));

        if (empty($label)) {
            http_response_code(400);
            echo json_encode(['error' => 'Le label est requis']);
            exit;
        }

        $skill = new SkillModel();
        $skill->label_skill = $label;

        $success = $this->repo->pushSkill($skill);

        if ($success) {
            http_response_code(201);
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
     * DELETE /api/skills/delete/{id}
     * Supprime une compétence via une requête DELETE
     */
    public function deleteAjax(int $id): void
    {
        header('Content-Type: application/json');

        $skill = $this->repo->getById($id);
        if (!$skill) {
            http_response_code(404);
            echo json_encode(['error' => 'Compétence non trouvée']);
            exit;
        }

        $deleted = $this->repo->deleteById($id);

        if ($deleted) {
            echo json_encode(['status' => 'deleted', 'id' => $id]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Échec de la suppression']);
        }
        exit;
    }

    /**
     * PATCH /api/skills/update/{id}
     * Met à jour partiellement une compétence
     */
    public function updateAjax(int $id): void
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);
        $label = trim($input['label_skill'] ?? ($input['label'] ?? ''));

        $skill = $this->repo->getById($id);
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

        $skill->label_skill = $label;
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
