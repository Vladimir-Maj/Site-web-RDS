<?php

declare(strict_types=1);

namespace App\Tests\Users;

use App\Controllers\StudentController;
use App\Repository\UserRepository;
use PDO;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

/**
 * Exception personnalis�e pour intercepter les redirections via header()
 */
class HeaderSentException extends \Error
{
}

/**
 * Utilitaire pour capturer et v�rifier les headers envoy�s pendant les tests
 */
class HeaderTrap
{
    /** @var array<int, array{header: string, replace: bool, response_code: int}> */
    public static array $headers = [];

    public static function reset(): void
    {
        self::$headers = [];
    }

    public static function capture(string $header, bool $replace = true, int $responseCode = 0): void
    {
        self::$headers[] = [
            'header'        => $header,
            'replace'       => $replace,
            'response_code' => $responseCode,
        ];
    }

    public static function lastHeader(): ?string
    {
        if (empty(self::$headers)) {
            return null;
        }

        return self::$headers[array_key_last(self::$headers)]['header'];
    }
}

/**
 * Surcharge de la fonction globale header() dans le namespace du Controller
 */
namespace App\Controllers;

function header(string $header, bool $replace = true, int $response_code = 0): void
{
    \App\Tests\Users\HeaderTrap::capture($header, $replace, $response_code);
    throw new \App\Tests\Users\HeaderSentException($header);
}

namespace App\Tests\Users;

class StudentControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Initialisation de la session pour les tests
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        $_SESSION               = [];
        $_SESSION['user_role']  = 'admin';
        $_SESSION['csrf_token'] = 'test-token';

        $_GET                   = [];
        $_POST                  = [];
        $_SERVER['REQUEST_URI'] = '/dashboard/etudiants/1';

        HeaderTrap::reset();
    }

    protected function tearDown(): void
    {
        $_GET     = [];
        $_POST    = [];
        $_SESSION = [];

        HeaderTrap::reset();

        parent::tearDown();
    }

    public function testRenderList(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $pdo  = $this->createMock(PDO::class);
        $twig = $this->createMock(Environment::class);

        $_GET['name']   = 'Dupont';
        $_GET['status'] = 'Searching';
        $_GET['page']   = '1';

        $expectedFilters = [
            'name'   => 'Dupont',
            'status' => 'Searching',
            'page'   => 1,
            'limit'  => 10,
        ];

        $fakeStudents = [
            [
                'id'         => '1',
                'first_name' => 'Jean',
                'last_name'  => 'Dupont',
                'status'     => 'Searching',
            ],
        ];

        $repo->expects($this->once())
            ->method('searchStudents')
            ->with($expectedFilters)
            ->willReturn($fakeStudents);

        $twig->expects($this->once())
            ->method('render')
            ->with(
                'students/student_list.html.twig',
                [
                    'students'       => $fakeStudents,
                    'filters'        => $expectedFilters,
                    'sidebar_active' => 'students',
                ]
            )
            ->willReturn('HTML_RENDERED');

        $controller = new StudentController($repo, $twig, $pdo);

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
            'csrf_token'   => 'test-token',
            'first_name'   => 'Jean',
            'last_name'    => 'Dupont',
            'is_active'    => '1',
            'password'     => 'nouveau_mot_de_passe',
            'promotion_id' => '99',
            'status'       => 'Hired',
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
            ->with($studentId, 'Hired')
            ->willReturn(true);

        $controller = new StudentController($repo, $twig, $pdo);

        try {
            $controller->handleUpdate($studentId);
            $this->fail('La redirection aurait d� avoir lieu.');
        } catch (HeaderSentException) {
            $this->assertSame(
                'Location: /dashboard/etudiants/42?success=1',
                HeaderTrap::lastHeader()
            );
        }
    }
}
