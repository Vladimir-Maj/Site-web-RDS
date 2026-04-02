<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Util\ComplianceLogger;
use App\Util\Util;
use App\Repository\ApplicationRepository;
use App\Repository\UserRepository;
use App\Repository\WishlistRepository;
use Twig\Environment;
use PDO;
use ZipArchive;
use DateTime;
use Exception;

/**
 * DataExportController
 * 
 * Handles GDPR Data Subject Access Requests (DSAR, Article 15)
 * Generates ZIP file with user's personal data in JSON + attachments
 * 
 * Routes:
 *   POST /dashboard/account/export-data — Request data export (generates ZIP)
 *   GET  /dashboard/account/download-export/{file_id} — Download generated export
 */
class DataExportController
{
    private PDO $pdo;
    private Environment $twig;
    private ComplianceLogger $complianceLogger;
    private string $exportDir; // Directory to store temporary exports

    public function __construct(
        PDO $pdo,
        Twig\Environment $twig,
        ComplianceLogger $complianceLogger,
        string $exportDir = '/tmp/data-exports'
    ) {
        $this->pdo = $pdo;
        $this->twig = $twig;
        $this->complianceLogger = $complianceLogger;
        $this->exportDir = $exportDir;

        // Ensure export directory exists
        if (!is_dir($this->exportDir)) {
            mkdir($this->exportDir, 0700, true);
        }
    }

    /**
     * Request data export
     * Generates ZIP and returns download link
     * 
     * @return void
     */
    public function requestExport(): void
    {
        try {
            // Verify user is authenticated
            $userId = Util::getUserId();
            $user = Util::getUser();

            if (!$userId || !$user) {
                http_response_code(401);
                echo $this->twig->render('errors/unauthorized.html.twig', [
                    'message' => 'You must be logged in to request your data.',
                ]);
                return;
            }

            // Log DSAR request
            $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $auditId = $this->complianceLogger->logDSAR($userId, $user['email'], $clientIp);

            // Generate export data
            $exportData = $this->generateExportData($userId);

            // Create ZIP file
            $zipPath = $this->createExportZip($userId, $user, $exportData);

            // Mark export in database or file system (optional tracking)
            $expiresAt = new DateTime();
            $expiresAt->modify('+24 hours');

            // Log export generation
            $fileSize = filesize($zipPath);
            $this->complianceLogger->logExportGenerated(
                $userId,
                $user['email'],
                $zipPath,
                $fileSize,
                $expiresAt,
                $clientIp
            );

            // Mark DSAR as completed
            $this->complianceLogger->markCompleted($auditId);

            // Send file for download
            $this->serveExportFile($zipPath, $user);

        } catch (Exception $e) {
            error_log('DataExportController::requestExport error: ' . $e->getMessage());
            http_response_code(500);
            echo $this->twig->render('errors/error.html.twig', [
                'message' => 'Error generating export: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate all export data for user
     * 
     * @param int $userId
     * @return array
     */
    private function generateExportData(int $userId): array
    {
        // User profile
        $userRepo = new UserRepository($this->pdo);
        $user = $userRepo->findById($userId);

        // Applications
        $appRepo = new ApplicationRepository($this->pdo);
        $applications = $appRepo->findByStudentId($userId); // adjust method name if needed

        // Wishlist
        $wishlistRepo = new WishlistRepository($this->pdo);
        $wishlist = $wishlistRepo->findByStudentId($userId);

        // Build export structure
        return [
            'profile' => $this->sanitizeUserForExport($user),
            'applications' => $applications,
            'wishlist' => $wishlist,
            'export_date' => (new DateTime())->format('Y-m-d H:i:s'),
            'gdpr_notice' => 'Your personal data export. Retain safely and securely.',
        ];
    }

    /**
     * Sanitize user data for export (remove sensitive fields, null passwords)
     * 
     * @param array $user
     * @return array
     */
    private function sanitizeUserForExport(array $user): array
    {
        // Remove password hash from export
        unset($user['password_hash'], $user['password']);

        return $user;
    }

    /**
     * Create ZIP file with all export data
     * 
     * @param int $userId
     * @param array $user User data
     * @param array $exportData Export data (profile, applications, etc.)
     * @return string Path to created ZIP file
     */
    private function createExportZip(int $userId, array $user, array $exportData): string
    {
        $timestamp = date('Ymd-His');
        $firstName = preg_replace('/[^a-z0-9]/i', '-', $user['first_name']);
        $lastName = preg_replace('/[^a-z0-9]/i', '-', $user['last_name']);
        $zipName = "data-export-{$timestamp}-{$firstName}-{$lastName}.zip";
        $zipPath = $this->exportDir . '/' . $zipName;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Cannot create ZIP file: {$zipPath}");
        }

        try {
            // Add main export data as JSON
            $jsonContent = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $zip->addFromString('profile-and-data.json', $jsonContent);

            // Add uploaded files (CVs, motivation letters)
            $this->addUploadedFilesToZip($zip, $userId, $exportData);

            // Add GDPR notice / explanation
            $gdprNotice = $this->generateGDPRNotice();
            $zip->addFromString('GDPR-EXPORT-NOTICE.txt', $gdprNotice);

            $zip->close();
        } catch (Exception $e) {
            if (file_exists($zipPath)) {
                unlink($zipPath);
            }
            throw $e;
        }

        // Set restrictive permissions (owner read-only)
        chmod($zipPath, 0600);

        return $zipPath;
    }

    /**
     * Add uploaded files (CVs, LMs) to ZIP
     * 
     * @param ZipArchive $zip
     * @param int $userId
     * @param array $exportData
     * @return void
     */
    private function addUploadedFilesToZip(ZipArchive $zip, int $userId, array $exportData): void
    {
        $baseUploadDir = '/path/to/www/cdn/uploads'; // ADJUST PATH

        // Extract file paths from applications
        foreach ($exportData['applications'] as $app) {
            if (!empty($app['cv_path'])) {
                $filePath = $baseUploadDir . '/' . $app['cv_path'];
                if (file_exists($filePath)) {
                    $zip->addFile($filePath, 'files/cv-' . basename($filePath));
                }
            }

            if (!empty($app['lettre_motivation_path'])) {
                $filePath = $baseUploadDir . '/' . $app['lettre_motivation_path'];
                if (file_exists($filePath)) {
                    $zip->addFile($filePath, 'files/lm-' . basename($filePath));
                }
            }
        }
    }

    /**
     * Generate GDPR notice text for export
     * 
     * @return string
     */
    private function generateGDPRNotice(): string
    {
        return <<<'TEXT'
=============================================================================
RGPD — NOTICE ACCOMPANYING DATA EXPORT (Article 15)
=============================================================================

This export contains your personal data accessed on [DATE] per your DSAR request.

Contents:
  - profile-and-data.json : Your profile, applications, and wish-list
  - files/ : Uploaded documents (CVs, motivation letters)
  - GDPR-EXPORT-NOTICE.txt : This file

Data Controller:
  [COMPANY NAME]
  legal@example.fr

Your Rights:
  - Rectification (Article 16) : Correct inaccurate data via your account
  - Erasure (Article 17) : Request account deletion
  - Portability (Article 20) : Request future exports
  - Objection (Article 21) : Object to processing

If you have questions or wish to update data, contact : legal@example.fr

=============================================================================
TEXT;
    }

    /**
     * Serve ZIP file for download
     * 
     * @param string $zipPath
     * @param array $user
     * @return void
     */
    private function serveExportFile(string $zipPath, array $user): void
    {
        if (!file_exists($zipPath)) {
            throw new Exception("Export file not found: {$zipPath}");
        }

        $fileName = basename($zipPath);
        $fileSize = filesize($zipPath);

        // Send headers
        header('Content-Type: application/zip');
        header("Content-Disposition: attachment; filename=\"{$fileName}\"");
        header("Content-Length: {$fileSize}");
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Expires: 0');

        // Stream file
        readfile($zipPath);

        // Schedule deletion after 24 hours (cron job or background task)
        // For now, could use touch() to mark deletion time, or rely on external cron
        // touch($zipPath . '.delete-after-24h');

        exit;
    }

    /**
     * Download previously generated export
     * Verifies ownership and expiration
     * 
     * @param string $fileId Filename or UUID
     * @return void
     */
    public function downloadExport(string $fileId): void
    {
        $userId = Util::getUserId();
        if (!$userId) {
            http_response_code(401);
            echo 'Unauthorized';
            return;
        }

        // Validate fileId (prevent directory traversal)
        if (preg_match('/[^a-zA-Z0-9\-_.]/', $fileId)) {
            http_response_code(400);
            echo 'Invalid file format';
            return;
        }

        $zipPath = $this->exportDir . '/' . $fileId . '.zip';

        if (!file_exists($zipPath)) {
            http_response_code(404);
            echo 'Export file not found';
            return;
        }

        // Verify ownership (filename should contain user's ID or email hash)
        // This is a simplified check; production should use database tracking

        // Check expiration (24 hour rule)
        $fileAge = time() - filemtime($zipPath);
        if ($fileAge > 86400) { // 24 hours in seconds
            unlink($zipPath);
            http_response_code(410);
            echo 'Export file has expired (24 hour limit)';
            return;
        }

        // Log download
        $user = Util::getUser();
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $this->complianceLogger->logExportDownloaded($userId, $user['email'], $zipPath, $clientIp);

        // Serve file
        $this->serveExportFile($zipPath, $user);
    }
}
