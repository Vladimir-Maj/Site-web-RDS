<?php

namespace App\Controllers;

use App\Repository\UserRepository;
use Twig\Environment;
use PDO;

class CVFast extends BaseController
{
    public function __construct(
        protected UserRepository $userRepo,
        protected Environment    $twig,
        protected PDO            $con
    ) {}

    /**
     * AJAX GET ALL: Fetch distinct CV paths used by a student across their applications.
     * GET /api/profile/get-cvs
     */
    public function ajaxGetAll(string $uid): void
    {
        $sql = "SELECT DISTINCT cv_path_application AS file_path
                FROM application
                WHERE student_id_application = :uid
                  AND cv_path_application IS NOT NULL
                ORDER BY file_path";

        $stmt = $this->con->prepare($sql);
        $stmt->execute(['uid' => $uid]);

        $this->jsonResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function ajaxGetAllLms(string $uid): void
    {
        $sql = "SELECT DISTINCT cover_letter_path_application AS file_path
                FROM application
                WHERE student_id_application = :uid
                  AND cover_letter_path_application IS NOT NULL
                ORDER BY file_path";

        $stmt = $this->con->prepare($sql);
        $stmt->execute(['uid' => $uid]);

        $this->jsonResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * AJAX GET LATEST: Fetch the CV path from the student's most recent application.
     */
    public function ajaxGetLatest(string $uid): void
    {
        $sql = "SELECT cv_path_application AS file_path
                FROM application
                WHERE student_id_application = :uid
                  AND cv_path_application IS NOT NULL
                ORDER BY applied_at_application DESC
                LIMIT 1";

        $stmt = $this->con->prepare($sql);
        $stmt->execute(['uid' => $uid]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            $this->jsonResponse(['error' => 'No CV found'], 404);
            return;
        }

        $this->jsonResponse($result);
    }
}