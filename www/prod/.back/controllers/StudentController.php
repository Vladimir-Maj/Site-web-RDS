<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repository\UserRepository;
use App\Util;
use Exception;
use Twig\Environment;

class StudentController extends BaseController
{
    public function __construct(
        private UserRepository $repo,
        protected Environment $twig,
        private \PDO $pdo
    ) {
        parent::__construct($twig);
        if (Util::getCSRFToken() === null) {
            Util::setCSRFToken(bin2hex(random_bytes(32)));
        }
    }

    public function renderList(): void
    {
        $this->abortIfNotPriv();

        $filters = [
            'name' => $_GET['name'] ?? null,
            'status' => $_GET['status'] ?? null,
            'page' => (int) ($_GET['page'] ?? 1),
            'limit' => 10,
        ];

        $students = $this->repo->searchStudents($filters);

        echo $this->twig->render('students/student_list.html.twig', [
            'students' => $students,
            'filters' => $filters,
            'sidebar_active' => 'students',
        ]);
    }

    public function renderEditForm(string $id): void
    {
        $this->abortIfNotPriv();
        $student = $this->repo->findById($id);
        if (!$student) {
            $this->abort(404, "Étudiant introuvable.");
        }

        $currentPromo = $this->repo->getPromoByStudent($id);

        if ($currentPromo && isset($currentPromo['id'])) {
            $stmtCampus = $this->pdo->prepare("SELECT campus_id_promotion FROM promotion WHERE id_promotion = ? LIMIT 1");
            $stmtCampus->execute([(int) $currentPromo['id']]);
            $currentPromo['campus_id'] = $stmtCampus->fetchColumn() ?: null;
        }

        $stmt = $this->pdo->query(
            "SELECT id_campus AS id, name_campus AS label
             FROM campus
             ORDER BY name_campus"
        );
        $allCampuses = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $currentCampusId = $currentPromo['campus_id'] ?? null;

        if ($currentCampusId !== null) {
            $stmt = $this->pdo->prepare(
                "SELECT id_promotion AS id, label_promotion AS label, academic_year_promotion AS academic_year
                 FROM promotion
                 WHERE campus_id_promotion = :campusId
                 ORDER BY academic_year_promotion DESC"
            );
            $stmt->execute(['campusId' => $currentCampusId]);
            $allPromotions = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $allPromotions = [];
        }

        echo $this->twig->render('students/student_editor.html.twig', [
            'id' => $id,
            'student' => $student,
            'current_promo' => $currentPromo,
            'all_promotions' => $allPromotions,
            'all_campuses' => $allCampuses,
            'csrf_token' => Util::getCSRFToken(),
            'error' => $_SESSION['flash_error'] ?? null,
            'success' => $_GET['success'] ?? null,
            'sidebar_active' => 'students',
        ]);
        unset($_SESSION['flash_error']);
    }

    public function handleUpdate(string $id): void
    {
        $this->abortIfNotPriv();
        try {
            $this->repo->updateUser($id, $_POST);

            if (!empty($_POST['password'])) {
                $hashed = password_hash($_POST['password'], PASSWORD_ARGON2ID);
                $this->repo->updatePassword($id, $hashed);
            }

            if (!empty($_POST['promotion_id'])) {
                $this->repo->updateStudentEnrollment($id, $_POST['promotion_id']);
            }

            if (!empty($_POST['status'])) {
                $this->repo->updateStudentStatus($id, $_POST['status']);
            }

            header("Location: /dashboard/etudiants/{$id}?success=1");
            exit;
        } catch (Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}
