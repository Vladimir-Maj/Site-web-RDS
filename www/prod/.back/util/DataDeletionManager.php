<?php

declare(strict_types=1);

namespace App\Util;

use PDO;
use DateTime;
use Exception;

/**
 * DataDeletionManager
 * 
 * Manages the user account deletion workflow:
 * 1. User initiates deletion request
 * 2. System sends confirmation email with 7-day token
 * 3. User clicks confirmation link in email
 * 4. Cron job processes confirmed deletions (cascades all data)
 * 
 * @package App\Util
 */
class DataDeletionManager
{
    private PDO $pdo;
    private ComplianceLogger $complianceLogger;
    private string $appBaseUrl; // e.g., https://example.fr
    private string $contactEmail; // legal@example.fr

    public function __construct(
        PDO $pdo,
        ComplianceLogger $complianceLogger,
        string $appBaseUrl,
        string $contactEmail
    ) {
        $this->pdo = $pdo;
        $this->complianceLogger = $complianceLogger;
        $this->appBaseUrl = rtrim($appBaseUrl, '/');
        $this->contactEmail = $contactEmail;
    }

    /**
     * Initiate account deletion request
     * Creates deletion request record and sends email
     * 
     * @param int $userId
     * @param string $userEmail
     * @param string $userName
     * @param string|null $clientIp
     * @return array ['success' => bool, 'message' => string, 'confirmation_token' => ?string]
     */
    public function initiateRequest(
        int $userId,
        string $userEmail,
        string $userName,
        ?string $clientIp = null
    ): array {
        try {
            // Generate secure token
            $token = bin2hex(random_bytes(32));
            $expiresAt = new DateTime();
            $expiresAt->modify('+7 days');

            // Insert deletion request
            $sql = '
                INSERT INTO data_deletion_requests 
                (user_id, user_email, user_name, confirmation_token, 
                 confirmation_token_expires_at, status)
                VALUES (?, ?, ?, SHA2(?, 256), ?, "PENDING_CONFIRMATION")
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $userId,
                $userEmail,
                $userName,
                $token,
                $expiresAt->format('Y-m-d H:i:s'),
            ]);

            // Log to compliance audit
            $this->complianceLogger->logDeletionInitiated($userId, $userEmail, $clientIp);

            // Send confirmation email
            $sentEmail = $this->sendDeletionConfirmationEmail($userEmail, $userName, $token);

            if (!$sentEmail) {
                return [
                    'success' => false,
                    'message' => 'Deletion request created but email failed to send. Please contact ' . $this->contactEmail,
                ];
            }

            return [
                'success' => true,
                'message' => 'Deletion request initiated. Check your email for confirmation link (valid 7 days).',
                'confirmation_token' => null, // Don't expose token in response
            ];
        } catch (Exception $e) {
            error_log('DataDeletionManager::initiateRequest error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error initiating deletion request: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Confirm deletion via token (user clicks email link)
     * 
     * @param string $token Raw token from email link
     * @param string|null $clientIp
     * @return array ['success' => bool, 'message' => string, 'user_id' => ?int]
     */
    public function confirmRequest(string $token, ?string $clientIp = null): array
    {
        try {
            $tokenHash = hash('sha256', $token);

            // Find valid token
            $sql = '
                SELECT id, user_id, user_email, confirmation_token_expires_at, status
                FROM data_deletion_requests
                WHERE confirmation_token = ?
                LIMIT 1
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$tokenHash]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                return ['success' => false, 'message' => 'Invalid or expired confirmation token.'];
            }

            // Check if token expired
            if (new DateTime($request['confirmation_token_expires_at']) < new DateTime()) {
                // Mark as expired
                $this->markExpired($request['id']);
                return ['success' => false, 'message' => 'Confirmation link has expired. Please initiate deletion again.'];
            }

            // Check if already confirmed
            if ($request['status'] === 'CONFIRMED_BY_USER' || $request['status'] === 'EXECUTED') {
                return ['success' => false, 'message' => 'This deletion request has already been confirmed.'];
            }

            // Mark as confirmed
            $sql = '
                UPDATE data_deletion_requests
                SET status = "CONFIRMED_BY_USER", 
                    confirmed_by_user_at = NOW()
                WHERE id = ?
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$request['id']]);

            // Log confirmation
            $this->complianceLogger->logDeletionConfirmed(
                $request['user_id'],
                $request['user_email'],
                $clientIp
            );

            return [
                'success' => true,
                'message' => 'Deletion confirmed. Your account will be deleted shortly (within 24 hours).',
                'user_id' => (int)$request['user_id'],
            ];
        } catch (Exception $e) {
            error_log('DataDeletionManager::confirmRequest error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error confirming deletion: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get pending deletion request for user (if exists and not expired)
     * 
     * @param int $userId
     * @return array|null
     */
    public function getPendingRequest(int $userId): ?array
    {
        $sql = '
            SELECT id, status, requested_at, confirmation_token_expires_at
            FROM data_deletion_requests
            WHERE user_id = ?
              AND status IN ("PENDING_CONFIRMATION", "CONFIRMED_BY_USER")
            LIMIT 1
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Cancel deletion request (user changes mind before confirmation)
     * 
     * @param int $userId
     * @return bool
     */
    public function cancelRequest(int $userId): bool
    {
        $sql = '
            UPDATE data_deletion_requests
            SET status = "CANCELLED_BY_USER"
            WHERE user_id = ? AND status = "PENDING_CONFIRMATION"
        ';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$userId]) && $stmt->rowCount() > 0;
    }

    /**
     * Mark deletion request as expired
     * 
     * @param int $requestId
     * @return bool
     */
    private function markExpired(int $requestId): bool
    {
        $sql = '
            UPDATE data_deletion_requests
            SET status = "CONFIRMATION_EXPIRED"
            WHERE id = ? AND status = "PENDING_CONFIRMATION"
        ';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$requestId]) && $stmt->rowCount() > 0;
    }

    /**
     * Send deletion confirmation email
     * 
     * @param string $email
     * @param string $name
     * @param string $token
     * @return bool
     */
    private function sendDeletionConfirmationEmail(string $email, string $name, string $token): bool
    {
        $confirmationUrl = $this->appBaseUrl . '/dashboard/account/confirm-deletion?token=' . urlencode($token);
        $expiresHours = 7 * 24;

        $subject = 'Confirmation de suppression de compte';
        $message = "
Bonjour {$name},

Vous avez demandé la suppression de votre compte. Cette action est irréversible.

Pour confirmer la suppression, cliquez sur le lien ci-dessous (valide {$expiresHours} heures) :
{$confirmationUrl}

Si vous ne souhaitez pas supprimer votre compte, ignorez cet email.

Lien d'assistance : {$this->contactEmail}

---
Plateforme de Gestion de Stages
        ";

        // Use PHP mail() or your email library
        // For production, use PHP_EOL and proper headers
        $headers = "From: {$this->contactEmail}\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        return mail($email, $subject, $message, $headers);
    }

    /**
     * Resend confirmation email (user lost original email)
     * 
     * @param int $userId
     * @param string $email
     * @param string $name
     * @return bool
     */
    public function resendConfirmationEmail(int $userId, string $email, string $name): bool
    {
        // Get existing token
        $sql = '
            SELECT confirmation_token 
            FROM data_deletion_requests
            WHERE user_id = ? AND status = "PENDING_CONFIRMATION"
            LIMIT 1
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return false;
        }

        // Can't resend—token is hashed in DB. Need to restart process or generate new token
        // This is a security feature. For now, return false and let user restart.
        return false;
    }
}
