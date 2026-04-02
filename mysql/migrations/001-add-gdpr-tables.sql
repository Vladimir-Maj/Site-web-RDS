-- Migration: Add GDPR compliance tables
-- Date: 2026-04-02
-- Desc: Create tables for tracking data subject access requests (DSAR), deletion requests, and audit logs

-- ============================================================================
-- TABLE: compliance_audit_log
-- PURPOSE: Track all GDPR-related requests (DSARs, deletion requests, exports)
-- for audit trail and compliance demonstration to CNIL
-- ============================================================================

CREATE TABLE IF NOT EXISTS compliance_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Request Classification
    request_type ENUM(
        'DATA_SUBJECT_ACCESS_REQUEST',  -- DSAR (Article 15)
        'DELETION_INITIATED',            -- User clicked delete (Article 17, step 1)
        'DELETION_CONFIRMED',            -- User confirmed via email (Article 17, step 2)
        'DELETION_EXECUTED',             -- Account/files actually deleted
        'RECTIFICATION',                 -- Correction request (Article 16)
        'PORTABILITY',                   -- Data portability (Article 20)
        'OBJECTION',                     -- Objection to processing (Article 21)
        'EXPORT_GENERATED',              -- Export ZIP file created
        'EXPORT_DOWNLOADED',             -- User downloaded their export
        'OTHER'
    ) NOT NULL,
    
    -- User Identification (hashed for privacy)
    -- Never store real user ID/email in audit log to keep log itself private
    user_id_hash VARCHAR(64),            -- SHA256(user_id), can link to user without exposing PII
    user_email_hash VARCHAR(64),         -- SHA256(email), optional alternative identifier
    
    -- Timestamps
    request_timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    completion_timestamp DATETIME NULL,
    
    -- Status Tracking
    status ENUM(
        'PENDING',                       -- Awaiting action (e.g., email confirmation)
        'COMPLETED',                     -- Successfully completed
        'EXPIRED',                       -- Confirmation window expired (7 days)
        'CANCELLED',                     -- User cancelled request
        'FAILED'                         -- Error during processing
    ) DEFAULT 'PENDING',
    
    -- Context & Notes
    notes TEXT,                          -- Admin notes or error messages
    ip_address_hash VARCHAR(64),         -- SHA256 of request IP (not full IP)
    user_agent_hash VARCHAR(64),         -- SHA256 of User-Agent header
    
    -- File References (if applicable)
    export_file_path VARCHAR(255),       -- e.g., 'exports/2026-04-02-user-123-export.zip'
    export_file_size_bytes INT,          -- Size of generated export
    export_expiration DATETIME NULL,     -- File auto-delete time
    
    -- Indexes for efficient queries
    INDEX idx_request_type (request_type),
    INDEX idx_status (status),
    INDEX idx_request_timestamp (request_timestamp),
    INDEX idx_user_id_hash (user_id_hash),
    INDEX idx_completion_timestamp (completion_timestamp)
);

-- ============================================================================
-- TABLE: data_deletion_requests
-- PURPOSE: Track user-initiated account deletion with 7-day confirmation window
-- 
-- WORKFLOW:
--   1. User clicks "Delete Account" → INSERT into data_deletion_requests (status=PENDING_CONFIRMATION)
--   2. System sends email with confirmation_token link (valid 7 days)
--   3. User clicks link → UPDATE status=CONFIRMED_BY_USER
--   4. Cron job runs, finds CONFIRMED_BY_USER rows, executes DELETE on users table
--   5. ON DELETE CASCADE triggers deletion of candidatures, wishlist, evaluations
--   6. Separate cron job cleans orphaned files
--   7. UPDATE status=EXECUTED
-- ============================================================================

CREATE TABLE IF NOT EXISTS data_deletion_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- User Information
    user_id INT NOT NULL UNIQUE,
    user_email VARCHAR(255),             -- Snapshot of email at deletion time (for logging)
    user_name VARCHAR(255),              -- Snapshot of name (optional, for admin reference)
    
    -- Request Lifecycle
    requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,  -- When user clicked delete
    confirmation_email_sent_at DATETIME NULL,         -- When confirmation email dispatched
    confirmation_token VARCHAR(64) UNIQUE,             -- SHA256 or random token for confirmation link
    confirmation_token_expires_at DATETIME,            -- 7 days from requested_at
    confirmed_by_user_at DATETIME NULL,               -- When user clicked confirmation link
    deleted_at DATETIME NULL,                         -- When actual deletion executed
    
    -- Status Tracking
    status ENUM(
        'PENDING_CONFIRMATION',          -- Awaiting email confirmation from user
        'CONFIRMATION_EXPIRED',          -- 7-day window passed, request cancelled
        'CONFIRMED_BY_USER',             -- User confirmed via email link
        'EXECUTED',                      -- Account deleted successfully
        'CANCELLED_BY_USER',             -- User cancelled before confirmation
        'FAILED'                         -- Error during deletion execution
    ) DEFAULT 'PENDING_CONFIRMATION',
    
    -- Error Handling
    error_message TEXT,                  -- If status=FAILED, error details
    retry_count INT DEFAULT 0,           -- Number of deletion attempts
    
    -- Foreign Key (important for cascading)
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_confirmation_token (confirmation_token),
    INDEX idx_requested_at (requested_at),
    INDEX idx_confirmation_token_expires_at (confirmation_token_expires_at)
);

-- ============================================================================
-- VERIFY: Check that existing foreign keys have ON DELETE CASCADE
-- ============================================================================
-- Run this check manually after migration to ensure cascade deletion:
--
-- SELECT 
--     TABLE_NAME,
--     CONSTRAINT_NAME,
--     DELETE_RULE
-- FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
-- WHERE CONSTRAINT_SCHEMA = DATABASE()
-- ORDER BY TABLE_NAME;
--
-- Expected result: All DELETE_RULE should be 'CASCADE'
-- ============================================================================

-- ============================================================================
-- SAMPLE QUERIES for testing
-- ============================================================================

/*
-- View recent GDPR requests
SELECT 
    id,
    request_type,
    status,
    request_timestamp,
    completion_timestamp,
    CAST(SUBSTR(user_id_hash, 1, 8) AS CHAR) as user_id_hash_partial
FROM compliance_audit_log
ORDER BY request_timestamp DESC
LIMIT 20;

-- View pending deletion requests
SELECT 
    id,
    user_email,
    requested_at,
    confirmation_token_expires_at,
    status
FROM data_deletion_requests
WHERE status IN ('PENDING_CONFIRMATION', 'CONFIRMED_BY_USER')
ORDER BY requested_at ASC;

-- Check deletion window expiry
SELECT 
    id,
    user_email,
    confirmation_token_expires_at,
    NOW() as current_time,
    TIMESTAMPDIFF(HOUR, NOW(), confirmation_token_expires_at) as hours_remaining
FROM data_deletion_requests
WHERE status = 'PENDING_CONFIRMATION'
    AND confirmation_token_expires_at < NOW();
*/
