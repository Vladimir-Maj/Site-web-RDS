<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\ApplicationModel;
use App\Models\RoleEnum;
use App\Repository\ApplicationRepository;
use App\Repository\OfferRepository;
use App\Repository\UserRepository;
use Twig\Environment;
use App\Util;

class ApplicationController extends BaseController
{
    private ApplicationRepository $repo;
    private OfferRepository $offerRepository;
    private UserRepository $userRepository;

    public function __construct(ApplicationRepository $repo, OfferRepository $offerRepository, UserRepository $userRepository, Environment $twig)
    {
        parent::__construct($twig);
        $this->offerRepository = $offerRepository;
        $this->repo = $repo;
        $this->userRepository = $userRepository;
    }

    /**
     * Permet à un Admin/Pilote de voir les candidatures d'un étudiant spécifique
     */
    public function viewStudentApplications(string $id): void 
    {
        $student = $this->userRepository->findById($id);
        $currentUser = Util::getUser();
        
        // Sécurité : Vérifier que l'utilisateur est bien un étudiant
        assert($student->role === RoleEnum::Student);
        
        $applications = $this->repo->findByStudent($student->id);

        echo $this->twig->render("dashboard/my_applications.html.twig", [
            "user" => $currentUser,
            "student" => $student,
            "applications" => $applications,
            "sidebar_active" => "applications",
            "is_student" => false 
        ]);
    }

    /**
     * GET /dashboard/applications
     * Affiche les candidatures envoyées par l'étudiant connecté.
     */
    public function myApplications(): void
    {
        $myId = Util::getUserId();
        $myApplications = $this->repo->findByStudent($myId);
        $currentUser = $this->userRepository->findById($myId);

        echo $this->twig->render("dashboard/my_applications.html.twig", [
            "user" => $currentUser,
            "applications" => $myApplications,
            "sidebar_active" => "applications",
            "is_student" => true 
        ]);
    }

    /**
     * GET /app/offers/{id}/apply
     * Affiche le formulaire de candidature
     */
    public function viewApply(string $id): void
    {
        $off = $this->offerRepository->findById($id);
        $usr = Util::getUser();

        // Diagnostic deserialization check
        if (is_object($off) && $off instanceof \__PHP_Incomplete_Class) {
            $className = (array) $off;
            error_log('Incomplete class: ' . ($className['__PHP_Incomplete_Class_Name'] ?? 'unknown'));
        }

        echo $this->twig->render('offers/apply.html.twig', [
            'offer_id' => $id,
            'offer' => is_object($off) ? (array) $off : $off,
            'user' => $usr,
            'title' => 'Candidature à l\'offre',
            'error' => 0,
            'success' => 0,
            'csrf_token' => Util::getCSRFToken()
        ]);
    }

    /**
     * POST /app/offers/{id}/apply
     */
    public function doApply(string $id): void
    {
        $application = ApplicationModel::fromArray([
            'student_id_application' => Util::getUserId(),
            'offer_id_application' => $id,
            'cv_path_application' => $_POST['cv_path'] ?? null,
            'cover_letter_path_application' => $_POST['cover_letter_path'] ?? null,
            'status_application' => 'pending'
        ]);

        if ($this->repo->push($application)) {
            header('Location: /app/offers/my-applications?status=success');
        } else {
            header("Location: /app/offers/{$id}/apply?error=save_failed");
        }
        exit;
    }

    /**
     * POST /api/offers/{id}/apply (AJAX)
     */
    public function applyAjax(string $id): void
    {
        $input = json_decode(file_get_contents('php://input'), true);

        $application = ApplicationModel::fromArray([
            'student_id_application' => Util::getUserId(),
            'offer_id_application' => $id,
            'cv_path_application' => $input['cv_path'] ?? null,
            'cover_letter_path_application' => $input['cover_letter_path'] ?? null,
            'status_application' => 'pending'
        ]);

        if ($this->repo->push($application)) {
            $this->jsonResponse(['status' => 'success', 'data' => $application], 201);
        }

        $this->jsonResponse(['error' => 'Erreur lors de la sauvegarde'], 500);
    }

    /**
     * GET /api/applications/student (AJAX)
     */
    public function listForStudentJson(): void
    {
        $applications = $this->repo->findByStudent(Util::getUserId());
        $this->jsonResponse($applications);
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

        // Security check: Owner OR Admin/Pilot
        if ($app->student_id_application !== Util::getUserId() && !$this->isPrivileged()) {
            $this->jsonResponse(['error' => 'Action non autorisée'], 403);
        }

        $this->repo->delete($id)
            ? $this->jsonResponse(['status' => 'success', 'deleted_id' => $id])
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

        $app->status_application = $status;
        $this->repo->push($app);

        $this->jsonResponse(['status' => 'updated', 'new_status' => $status]);
    }
}