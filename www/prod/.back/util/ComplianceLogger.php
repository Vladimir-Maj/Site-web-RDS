<?php

declare(strict_types=1);

namespace App\Util;

use PDO;

/**
 * ComplianceLogger
 * 
 * Utility class for logging GDPR-related requests (DSARs, deletion, exports, etc.)
 * to the compliance_audit_log table for audit trail and regulatory compliance.
 * 
 * Ensures PII is not stored in audit log; uses hashes instead.
 * 
 * @package App\Util
 */
class ComplianceLogger
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Log a GDPR request to the compliance audit table
     * 
     * @param string $requestType One of: DATA_SUBJECT_ACCESS_REQUEST, DELETION_INITIATED, 
     *                            DELETION_CONFIRMED, DELETION_EXECUTED, EXPORT_GENERATED, etc.
     * @param int|null $userId User ID (will be hashed before storing)
     * @param string|null $userEmail User email (will be hashed before storing)
     * @param string $status PENDING, COMPLETED, EXPIRED, CANCELLED, FAILED
     * @param string|null $notes Additional context or error messages
     * @param string|null $ipAddress Client IP address (will be hashed)
     * @param string|null $userAgent User agent string (will be hashed)
     * @param string|null $exportFilePath Path to generated export file (if applicable)
     * @param int|null $exportFileSize Size of export in bytes
     * @param \DateTime|null $exportExpiration When export file will expire
     * 
     * @return int ID of inserted audit log row
     * @throws \PDOException
     */
    public function log(
        string $requestType,
        ?int $userId = null,
        ?string $userEmail = null,
        string $status = 'PENDING',
        ?string $notes = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $exportFilePath = null,
        ?int $exportFileSize = null,
        ?\DateTime $exportExpiration = null
    ): int {
        // Hash sensitive values (never store raw PII in audit log)
        $userIdHash = $userId !== null ? hash('sha256', (string)$userId) : null;
        $emailHash = !empty($userEmail) ? hash('sha256', $userEmail) : null;
        $ipHash = !empty($ipAddress) ? hash('sha256', $ipAddress) : null;
        $agentHash = !empty($userAgent) ? hash('sha256', $userAgent) : null;

        // Prepare SQL
        $sql = '
            INSERT INTO compliance_audit_log 
            (request_type, user_id_hash, user_email_hash, status, notes, 
             ip_address_hash, user_agent_hash, export_file_path, 
             export_file_size_bytes, export_expiration, request_timestamp)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $requestType,
            $userIdHash,
            $emailHash,
            $status,
            $notes,
            $ipHash,
            $agentHash,
            $exportFilePath,
            $exportFileSize,
            $exportExpiration ? $exportExpiration->format('Y-m-d H:i:s') : null,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Log a data subject access request (DSAR, Article 15)
     * 
     * @param int $userId
     * @param string $userEmail
     * @param string|null $ipAddress
     * @return int Audit log ID
     */
    public function logDSAR(int $userId, string $userEmail, ?string $ipAddress = null): int
    {
        return $this->log(
            'DATA_SUBJECT_ACCESS_REQUEST',
            $userId,
            $userEmail,
            'PENDING',
            'Data subject access request initiated',
            $ipAddress
        );
    }

    /**
     * Log deletion initiation (Article 17, step 1)
     * 
     * @param int $userId
     * @param string $userEmail
     * @param string|null $ipAddress
     * @return int Audit log ID
     */
    public function logDeletionInitiated(int $userId, string $userEmail, ?string $ipAddress = null): int
    {
        return $this->log(
            'DELETION_INITIATED',
            $userId,
            $userEmail,
            'PENDING',
            'User initiated account deletion request',
            $ipAddress
        );
    }

    /**
     * Log deletion confirmation (Article 17, step 2)
     * 
     * @param int $userId
     * @param string $userEmail
     * @param string|null $ipAddress
     * @return int Audit log ID
     */
    public function logDeletionConfirmed(int $userId, string $userEmail, ?string $ipAddress = null): int
    {
        return $this->log(
            'DELETION_CONFIRMED',
            $userId,
            $userEmail,
            'COMPLETED',
            'User confirmed account deletion via email link',
            $ipAddress
        );
    }

    /**
     * Log successful account deletion (Article 17, executed)
     * 
     * @param int $userId
     * @param string $userEmail
     * @param string|null $notes Additional context
     * @return int Audit log ID
     */
    public function logDeletionExecuted(int $userId, string $userEmail, ?string $notes = null): int
    {
        $defaultNotes = 'Account and related data deleted successfully (cascaded).';
        return $this->log(
            'DELETION_EXECUTED',
            $userId,
            $userEmail,
            'COMPLETED',
            $notes ?? $defaultNotes
        );
    }

    /**
     * Log data export generation
     * 
     * @param int $userId
     * @param string $userEmail
     * @param string $filePath Path to generated ZIP file
     * @param int $fileSize Size in bytes
     * @param \DateTime $expiresAt When file will auto-delete
     * @param string|null $ipAddress
     * @return int Audit log ID
     */
    public function logExportGenerated(
        int $userId,
        string $userEmail,
        string $filePath,
        int $fileSize,
        \DateTime $expiresAt,
        ?string $ipAddress = null
    ): int {
        return $this->log(
            'EXPORT_GENERATED',
            $userId,
            $userEmail,
            'COMPLETED',
            'Data export file generated (24-hour expiration)',
            $ipAddress,
            null,
            $filePath,
            $fileSize,
            $expiresAt
        );
    }

    /**
     * Log data export download
     * 
     * @param int $userId
     * @param string $userEmail
     * @param string $filePath Path of downloaded file
     * @param string|null $ipAddress
     * @return int Audit log ID
     */
    public function logExportDownloaded(
        int $userId,
        string $userEmail,
        string $filePath,
        ?string $ipAddress = null
    ): int {
        return $this->log(
            'EXPORT_DOWNLOADED',
            $userId,
            $userEmail,
            'COMPLETED',
            'User downloaded their data export',
            $ipAddress,
            null,
            $filePath
        );
    }

    /**
     * Log a rectification request (Article 16)
     * 
     * @param int $userId
     * @param string $userEmail
     * @param string $fieldsCorrected Comma-separated list of fields corrected
     * @param string|null $ipAddress
     * @return int Audit log ID
     */
    public function logRectification(
        int $userId,
        string $userEmail,
        string $fieldsCorrected,
        ?string $ipAddress = null
    ): int {
        return $this->log(
            'RECTIFICATION',
            $userId,
            $userEmail,
            'COMPLETED',
            "Data corrected. Fields: {$fieldsCorrected}",
            $ipAddress
        );
    }

    /**
     * Log objection to processing (Article 21)
     * 
     * @param int $userId
     * @param string $userEmail
     * @param string $reason Reason for objection
     * @param string|null $ipAddress
     * @return int Audit log ID
     */
    public function logObjection(
        int $userId,
        string $userEmail,
        string $reason,
        ?string $ipAddress = null
    ): int {
        return $this->log(
            'OBJECTION',
            $userId,
            $userEmail,
            'PENDING',
            "User objected to processing. Reason: {$reason}",
            $ipAddress
        );
    }

    /**
     * Mark audit log entry as completed
     * 
     * @param int $auditLogId
     * @param string|null $completionNotes
     * @return bool
     */
    public function markCompleted(int $auditLogId, ?string $completionNotes = null): bool
    {
        $sql = '
            UPDATE compliance_audit_log
            SET status = ?, completion_timestamp = NOW(), notes = COALESCE(?, notes)
            WHERE id = ?
        ';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['COMPLETED', $completionNotes, $auditLogId]);
    }

    /**
     * Mark audit log entry as failed
     * 
     * @param int $auditLogId
     * @param string $errorMessage
     * @return bool
     */
    public function markFailed(int $auditLogId, string $errorMessage): bool
    {
        $sql = '
            UPDATE compliance_audit_log
            SET status = ?, completion_timestamp = NOW(), 
                notes = CONCAT(COALESCE(notes, ""), "\nError: ", ?)
            WHERE id = ?
        ';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['FAILED', $errorMessage, $auditLogId]);
    }

    /**
     * Get recent GDPR requests (admin view)
     * 
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getRecentRequests(int $limit = 50, int $offset = 0): array
    {
        $sql = '
            SELECT id, request_type, status, request_timestamp, 
                   completion_timestamp, SUBSTR(user_id_hash, 1, 8) as user_id_hash_partial,
                   notes
            FROM compliance_audit_log
            ORDER BY request_timestamp DESC
            LIMIT ? OFFSET ?
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get count of pending requests
     * 
     * @return int
     */
    public function getPendingRequestCount(): int
    {
        $sql = 'SELECT COUNT(*) FROM compliance_audit_log WHERE status = "PENDING"';
        return (int)$this->pdo->query($sql)->fetchColumn();
    }

    /**
     * Get stats for compliance dashboard
     * 
     * @return array
     */
    public function getStats(): array
    {
        $sql = '
            SELECT 
                COUNT(*) as total_requests,
                SUM(CASE WHEN status = "PENDING" THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = "COMPLETED" THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN request_type = "DATA_SUBJECT_ACCESS_REQUEST" THEN 1 ELSE 0 END) as dsars,
                SUM(CASE WHEN request_type LIKE "DELETION%" THEN 1 ELSE 0 END) as deletions,
                SUM(CASE WHEN request_type = "EXPORT_GENERATED" THEN 1 ELSE 0 END) as exports
            FROM compliance_audit_log
        ';
        $result = $this->pdo->query($sql)->fetchAssoc();
        return $result ?? [];
    }

    /**
     * Clean up old audit entries (older than 3 years)
     * Callable by admin maintenance job
     * 
     * @return int Number of rows deleted
     */
    public function cleanupOldEntries(int $retentionDays = 1095): int
    {
        $sql = '
            DELETE FROM compliance_audit_log
            WHERE request_timestamp < DATE_SUB(NOW(), INTERVAL ? DAY)
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$retentionDays]);
        return $stmt->rowCount();
    }
}
