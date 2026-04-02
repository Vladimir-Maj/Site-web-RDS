<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Util\ComplianceLogger;
use App\Util\DataDeletionManager;
use App\Util;
use Twig\Environment;
use PDO;

/**
 * AccountDeletionController
 * 
 * Handles GDPR Right to Erasure (Article 17)
 * 
 * Routes:
 *   GET  /dashboard/account/delete-account — Show deletion confirmation page
 *   POST /dashboard/account/request-deletion — Initiate deletion with email confirmation
 *   GET  /dashboard/account/confirm-deletion — Confirm deletion via email link
 */
class AccountDeletionController
{
    private PDO $pdo;
    private Environment $twig;
    private ComplianceLogger $complianceLogger;
    private DataDeletionManager $deletionManager;

    public function __construct(
        PDO $pdo,
        Environment $twig,
        ComplianceLogger $complianceLogger,
        DataDeletionManager $deletionManager
    ) {
        $this->pdo = $pdo;
        $this->twig = $twig;
        $this->complianceLogger = $complianceLogger;
        $this->deletionManager = $deletionManager;
    }

    /**
     * Show account deletion confirmation page
     * 
     * @return void
     */
    public function showDeletePage(): void
    {
        // Verify authentication
        $userId = Util::getUserId();
        if (!$userId) {
            header('Location: /login');
            exit;
        }

        $user = Util::getUser();

        // Check if deletion request already pending
        $pendingRequest = $this->deletionManager->getPendingRequest($userId);

        // Display confirmation page with warnings
        echo $this->twig->render('account/delete-account.html.twig', [
            'isLoggedIn' => Util::isLoggedIn(),
            'currentUser' => $user,
            'pendingRequest' => $pendingRequest,
            'warningMessage' => 'This action is IRREVERSIBLE. All your data will be permanently deleted.',
        ]);
    }

    /**
     * Request account deletion (POST)
     * Sends confirmation email to user
     * 
     * @return void
     */
    public function requestDeletion(): void
    {
        // Verify authentication
        $userId = Util::getUserId();
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $user = Util::getUser();
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        // CSRF protection - verify token if needed
        // (If using session tokens, validate $_POST['csrf_token'] here)

        // Check for existing pending request
        $pending = $this->deletionManager->getPendingRequest($userId);
        if ($pending && $pending['status'] === 'PENDING_CONFIRMATION') {
            echo json_encode([
                'success' => false,
                'message' => 'Deletion request already pending. Check your email for confirmation link.',
            ]);
            return;
        }

        // Initiate deletion
        $result = $this->deletionManager->initiateRequest(
            $userId,
            $user['email'],
            $user['first_name'] . ' ' . $user['last_name'],
            $clientIp
        );

        if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
            // AJAX response
            header('Content-Type: application/json');
            echo json_encode($result);
        } else {
            // Redirect to confirmation page
            $_SESSION['flash_message'] = $result['message'];
            $_SESSION['flash_type'] = $result['success'] ? 'info' : 'error';
            header('Location: /dashboard/account/delete-account');
        }
        exit;
    }

    /**
     * Confirm deletion via email link (GET)
     * User clicks confirmation link sent to their email
     * 
     * @return void
     */
    public function confirmDeletion(): void
    {
        $token = $_GET['token'] ?? '';
        if (empty($token)) {
            http_response_code(400);
            echo $this->twig->render('errors/error.html.twig', [
                'message' => 'Missing confirmation token.',
            ]);
            return;
        }

        // Verify token
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $result = $this->deletionManager->confirmRequest($token, $clientIp);

        if (!$result['success']) {
            http_response_code(400);
            echo $this->twig->render('errors/error.html.twig', [
                'message' => $result['message'],
            ]);
            return;
        }

        // Display success page
        echo $this->twig->render('account/deletion-confirmed.html.twig', [
            'message' => $result['message'],
            'contactEmail' => 'legal@example.fr',
        ]);

        // Optionally log user out
        session_destroy();
        exit;
    }

    /**
     * Cancel deletion request (user changes mind)
     * 
     * @return void
     */
    public function cancelDeletion(): void
    {
        $userId = Util::getUserId();
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $cancelled = $this->deletionManager->cancelRequest($userId);

        if ($cancelled) {
            $_SESSION['flash_message'] = 'Deletion request cancelled.';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'No pending deletion request found.';
            $_SESSION['flash_type'] = 'info';
        }

        header('Location: /dashboard/account/delete-account');
        exit;
    }
}
