<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\ApplicationModel;
use App\Repository\ApplicationRepository;
use Twig\Environment;
use App\Util;

class ApplicationController extends BaseController {
    private ApplicationRepository $repo;

    public function __construct(ApplicationRepository $repo, Environment $twig) {
        parent::__construct($twig);
        $this->repo = $repo;
    }

    /**
     * @throws \RuntimeException Si l'application n'existe pas
     */
    public function showJson(string $applicationId): void {
        $application = $this->repo->findById($applicationId);

        if (!$application) {
            $this->renderJsonError("Application not found", 404);
        }

        header('Content-Type: application/json');
        echo json_encode($application); 
        exit;
    }

    /**
     * @return array Liste des candidatures pour l'étudiant connecté
     */
    public function listForStudent(): array {
        // On utilise l'ID en session, injecté par le middleware d'auth
        return $this->repo->findByStudent(Util::getUserId());
    }

    public function listForStudentJson(): void
    {
        // Le middleware auth a déjà vérifié la session
        $applications = $this->repo->findByStudent(Util::getUserId());
        $this->jsonResponse($applications);
    }

    public function apply(ApplicationModel $application): void {
        $this->repo->push($application);
    }

    /**
     * @throws \RuntimeException
     */
    public function delete(string $applicationId): void {
        // On vérifie juste l'existence, le middleware a déjà validé le droit de supprimer
        $application = $this->repo->findById($applicationId);

        if (!$application) {
            $this->abort(404, "Application not found.");
        }

        $this->repo->delete($applicationId);
        
        // Redirection avec un paramètre de succès pour le feedback UI
        header('Location: /index.php?page=my-applications&status=deleted');
        exit;
    }

    public function deleteJson(string $applicationId): void
    {
        $application = $this->repo->findById($applicationId);
    
        if (!$application) {
            $this->jsonError("Application not found", 404);
        }
    
        // Vérification d'appartenance : seul l'étudiant propriétaire peut supprimer
        if ($application->student_id !== Util::getUserId() && !$this->isPrivileged()) {
            $this->jsonError("Forbidden", 403);
        }
    
        $this->repo->delete($applicationId);
        $this->jsonResponse(['success' => true, 'deleted_id' => $applicationId]);
    }

    /**
     * Helper privé pour uniformiser les erreurs JSON
     */
    private function renderJsonError(string $message, int $code): void {
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode(["error" => $message]);
        exit;
    }
}
