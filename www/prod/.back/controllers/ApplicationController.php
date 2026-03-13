<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repository\ApplicationRepository;
use Twig\Environment;

class ApplicationController extends BaseController {
    private ApplicationRepository $repo;

    public function __construct(ApplicationRepository $repo, Environment $twig) {
        // Run the BaseController constructor (starts session, sets twig)
        parent::__construct($twig);
        $this->repo = $repo;
    }

    public function listForStudent(): array {
        $this->checkAuth(); // Inherited from BaseController
        
        $applications = $this->repo->findByStudent($_SESSION['user_id']);
        return $applications;
        ;
    }

    public function delete(string $applicationId): void {
        $this->checkAuth();
        $application = $this->repo->findById($applicationId);

        if (!$application) {
            $this->abort(404, "Application not found.");
        }

        // Ownership check
        if ($application->getStudentId() !== $_SESSION['user_id'] || !$this->isSuperUser()) {
            $this->abort(403, "Unauthorized deletion attempt.");
        }

        $this->repo->delete($applicationId);
        header('Location: /index.php?page=my-applications');
        exit;
    }
}
