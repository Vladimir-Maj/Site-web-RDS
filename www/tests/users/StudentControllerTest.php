<?php
namespace App\Tests;

use App\Controllers\StudentController;
use App\Repository\UserRepository;
use PDO;
use Twig\Environment;
use PHPUnit\Framework\TestCase;

// ---  TESTER LES REDIRECTIONS (header)  ---
class HeaderSentException extends \Error {}

class HeaderTrap
{
    public static array $headers = [];

    public static function reset(): void { self::$headers = []; }

    public static function capture(string $header, bool $replace = true, int $responseCode = 0): void
    {
        self::$headers[] = ['header' => $header, 'replace' => $replace, 'response_code' => $responseCode];
    }

    public static function lastHeader(): ?string
    {
        return empty(self::$headers) ? null : self::$headers[count(self::$headers) - 1]['header'];
    }
}

namespace App\Controllers;
function header(string $header, bool $replace = true, int $response_code = 0): void
{
    \App\Tests\HeaderTrap::capture($header, $replace, $response_code);
    throw new \App\Tests\HeaderSentException($header);
}

// --- LES TESTS DU CONTRÔLEUR ---
namespace App\Tests;

class StudentControllerTest extends TestCase
{
    protected function setUp(): void
    {
 
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        session_unset();
        $_SESSION['user_role'] = 'admin';
        $_GET    = [];
        $_POST   = [];
        $_SERVER['REQUEST_URI'] = '/dashboard/etudiants/1/edit';
        HeaderTrap::reset();
    }

    protected function tearDown(): void
    {
        $_GET  = [];
        $_POST = [];
       
        session_unset();
        HeaderTrap::reset();
    }

    public function testRenderList(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $pdo  = $this->createMock(PDO::class);
        $twig = $this->createMock(Environment::class);

        $_GET['name']   = 'Dupont';
        $_GET['status'] = 'searching';
        $_GET['page']   = '1';

        $expectedFilters = [
            'name'   => 'Dupont',
            'status' => 'searching',
            'page'   => 1,
            'limit'  => 10,
        ];

        $fakeStudents = [
            ['id' => '1', 'first_name' => 'Jean', 'last_name' => 'Dupont', 'status' => 'searching']
        ];

        $repo->expects($this->once())
            ->method('searchStudents')
            ->with($expectedFilters)
            ->willReturn($fakeStudents);

        $twig->expects($this->once())
            ->method('render')
            ->with('students/student_list.html.twig', [
                'students'       => $fakeStudents,
                'filters'        => $expectedFilters,
                'sidebar_active' => 'students',
            ])
            ->willReturn('HTML_RENDERED');

        $controller = new \App\Controllers\StudentController($repo, $twig, $pdo);

        ob_start();
        $controller->renderList();
        $output = ob_get_clean();

        $this->assertSame('HTML_RENDERED', $output);
    }

    public function testHandleUpdateSuccessRedirect(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $pdo  = $this->createMock(PDO::class);
        $twig = $this->createMock(Environment::class);


        $_POST = [
            'first_name'   => 'Jean',
            'last_name'    => 'Dupont',
            'is_active'    => '1',
            'password'     => 'nouveau_mot_de_passe',
            'promotion_id' => '99',
            'status'       => 'hired',
        ];

        $studentId = '42';

        $repo->expects($this->once())
            ->method('updateUser')
            ->with($studentId, $_POST)
            ->willReturn(true);

        $repo->expects($this->once())
            ->method('updatePassword')
            ->willReturn(true);

        $repo->expects($this->once())
            ->method('updateStudentEnrollment')
            ->with($studentId, '99')
            ->willReturn(true);

        $repo->expects($this->once())
            ->method('updateStudentStatus')
            ->with($studentId, 'hired')
            ->willReturn(true);

        $controller = new \App\Controllers\StudentController($repo, $twig, $pdo);

        try {
            $controller->handleUpdate($studentId);
            $this->fail("La redirection (header) n'a pas eu lieu.");
        } catch (HeaderSentException $e) {
            $this->assertSame(
                'Location: /dashboard/etudiants/42?success=1',
                HeaderTrap::lastHeader()
            );
            $this->assertArrayNotHasKey('flash_error', $_SESSION);
        }
    }

   
    public function testHandleUpdateOnRepoExceptionStoresFlashAndRedirects(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $pdo  = $this->createMock(PDO::class);
        $twig = $this->createMock(Environment::class);

        $_POST = [
            'first_name'   => 'Jean',
            'last_name'    => 'Dupont',
            'is_active'    => '1',
            'password'     => '',
            'promotion_id' => '',
            'status'       => '',
        ];

        $repo->expects($this->once())
            ->method('updateUser')
            ->willThrowException(new \Exception('Erreur BDD simulée'));

        $controller = new \App\Controllers\StudentController($repo, $twig, $pdo);

        try {
            $controller->handleUpdate('42');
            $this->fail("Une redirection aurait dû avoir lieu.");
        } catch (HeaderSentException $e) {
            // Le contrôleur doit stocker l'erreur en session et rediriger
            $this->assertArrayHasKey('flash_error', $_SESSION);
            $this->assertSame('Erreur BDD simulée', $_SESSION['flash_error']);
        }
    }
}