<?php

namespace App\Controllers;

use App\Repository\UserRepository;
use Twig\Environment;
use PDO;

class CVFast extends BaseController
{
// CVFast.php
public function __construct(
    protected UserRepository $userRepo,
    protected Environment $twig,      // Positional Argument #3
    protected PDO $con              // Positional Argument #2
)    

{
    }

    /**
     * AJAX GET ALL: Fetch all CVs for a student
     * Usage: $this->ajaxGetAll('student-uuid-here');
     */
    public function ajaxGetAll(string $uid): void
    {
        $sql = "SELECT HEX(id) as id, file_name, file_path, is_primary, uploaded_at 
                FROM cv 
                WHERE student_id = UNHEX(:uid) 
                ORDER BY uploaded_at DESC";

        $stmt = $this->con->prepare($sql);
        $stmt->execute(['uid' => $uid]);

        $resp = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->jsonResponse($resp);
    }

    /**
     * AJAX GET MAIN: Fetch only the primary CV
     * Usage: $this->ajaxGetMain('student-uuid-here');
     */
    public function ajaxGetMain(string $uid): void
    {
        $sql = "SELECT HEX(id) as id, file_name, file_path, uploaded_at 
                FROM cv 
                WHERE student_id = UNHEX(:uid) AND is_primary = 1 
                LIMIT 1";

        $stmt = $this->con->prepare($sql);
        $stmt->execute(['uid' => $uid]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            $this->jsonResponse(['error' => 'No primary CV found'], 404);
        }

        $this->jsonResponse($result);
    }

    /**
     * CREATE: Insert a new CV record
     */
    /**
     * INTERNAL: Insert record + optionally reset primary. Returns bool.
     * Used by controllers that render views (non-AJAX).
     */
    public function store(string $uid, string $name, string $path, bool $primary = false): bool
    {
        try {
            if ($primary) {
                $this->resetPrimary($uid);
            }
            $sql = "INSERT INTO cv (id, student_id, file_name, file_path, is_primary)
                VALUES (UUID_TO_BIN(UUID()), UNHEX(:uid), :name, :path, :pri)";
            return $this->con->prepare($sql)->execute([
                'uid' => $uid,
                'name' => $name,
                'path' => $path,
                'pri' => $primary ? 1 : 0,
            ]);
        } catch (\Exception $e) {
            error_log("CVFast::store error: " . $e->getMessage());
            return false;
        }
    }

    // Update create() to delegate to store()
    public function create(string $uid, string $name, string $path, bool $primary = false): void
    {
        $success = $this->store($uid, $name, $path, $primary);
        $this->jsonResponse(['success' => $success], $success ? 201 : 500);
    }

    /**
     * DELETE: Remove by CV ID
     */
    public function delete(string $cvId): void
    {
        $sql = "DELETE FROM cv WHERE id = UNHEX(:cvid)";
        $success = $this->con->prepare($sql)->execute(['cvid' => $cvId]);

        $this->jsonResponse(['success' => $success]);
    }

    /**
     * UPDATE: Set a specific CV as primary and unset others
     */
    public function setPrimary(string $uid, string $cvId): void
    {
        $this->resetPrimary($uid);

        $sql = "UPDATE cv SET is_primary = 1 WHERE id = UNHEX(:cvid)";
        $success = $this->con->prepare($sql)->execute(['cvid' => $cvId]);

        $this->jsonResponse(['success' => $success]);
    }

    /**
     * REPO HELPER: Internal method to clear primary status for a user
     */
    private function resetPrimary(string $uid): void
    {
        $sql = "UPDATE cv SET is_primary = 0 WHERE student_id = UNHEX(:uid)";
        $this->con->prepare($sql)->execute(['uid' => $uid]);
    }
}