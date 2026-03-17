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

    // In App\Controllers\ApplicationController

public function showJson(string $applicationId): void {
    // Optional: add $this->checkAuth(); if you want this private
    $application = $this->repo->findById($applicationId);

    if (!$application) {
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(["error" => "Application not found"]);
        exit;
    }

    header('Content-Type: application/json');
    // Assuming your model has a toArray method or public properties
    echo json_encode($application); 
    exit;
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
