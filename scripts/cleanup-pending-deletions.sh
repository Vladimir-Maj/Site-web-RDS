#!/bin/bash

################################################################################
# cleanup-pending-deletions.sh
#
# PURPOSE: Process data deletion requests that have been confirmed by users
#          (or have expired waiting for confirmation)
#
# WORKFLOW:
#   1. Find rows in data_deletion_requests with status='CONFIRMED_BY_USER'
#   2. Execute DELETE on matching user IDs (cascades via ON DELETE CASCADE)
#   3. Update status to 'EXECUTED'
#   4. Log deletion to compliance_audit_log
#   5. Clean up orphaned files
#   6. Find rows with status='PENDING_CONFIRMATION' and 7+ days old
#   7. Set those to 'CONFIRMATION_EXPIRED'
#
# USAGE: ./scripts/cleanup-pending-deletions.sh
#        (typically run via cron daily, e.g., 0 1 * * * /path/to/cleanup-pending-deletions.sh)
#
# DEPENDENCIES:
#   - MySQL/MariaDB client
#   - DB credentials (env or config file)
#   - PHP CLI (optional: for triggering file cleanup)
#
# OUTPUT:
#   - Logs to /var/log/app/cleanup-deletions.log
################################################################################

set -e

# Configuration
LOG_FILE="/var/log/app/cleanup-deletions.log"
MAX_LOG_SIZE=10485760
SCRIPT_NAME="cleanup-pending-deletions.sh"

# Database Configuration
DB_HOST="${DB_HOST:-localhost}"
DB_USER="${DB_USER:-app_user}"
DB_PASS="${DB_PASS:-}"
DB_NAME="${DB_NAME:-app_db}"
DB_PORT="${DB_PORT:-3306}"

# Temp file for user IDs to delete
TEMP_DIR="/tmp/cleanup-deletions-$$"
USERS_TO_DELETE="${TEMP_DIR}/users.txt"
trap "rm -rf ${TEMP_DIR}" EXIT
mkdir -p "${TEMP_DIR}"

# Initialize or rotate log
if [ ! -f "${LOG_FILE}" ]; then
    mkdir -p "$(dirname "${LOG_FILE}")"
    touch "${LOG_FILE}"
fi

if [ -f "${LOG_FILE}" ] && [ $(stat -c%s "${LOG_FILE}" 2>/dev/null) -gt ${MAX_LOG_SIZE} ]; then
    mv "${LOG_FILE}" "${LOG_FILE}.$(date +%s).old"
    gzip "${LOG_FILE}".*.old 2>/dev/null || true
    touch "${LOG_FILE}"
fi

# Function: log message
log_msg() {
    local level=$1
    shift
    local msg="$@"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] [${level}] ${msg}" >> "${LOG_FILE}"
}

# Function: send alert
send_alert() {
    local subject=$1
    local body=$2
    echo "${body}" | mail -s "${subject}" tech-team@example.fr 2>/dev/null || true
}

log_msg "INFO" "Starting deletion request processing"

# ============================================================================
# STEP 1: Mark expired confirmation requests
# ============================================================================

log_msg "INFO" "Marking expired deletion requests..."

EXPIRED_QUERY="
UPDATE data_deletion_requests
SET status = 'CONFIRMATION_EXPIRED'
WHERE status = 'PENDING_CONFIRMATION'
  AND confirmation_token_expires_at < NOW();
"

EXPIRED_COUNT=$(mysql -h "${DB_HOST}" -u "${DB_USER}" -p"${DB_PASS}" -D "${DB_NAME}" -N -B \
    -e "SELECT COUNT(*) FROM data_deletion_requests 
        WHERE status = 'PENDING_CONFIRMATION' 
        AND confirmation_token_expires_at < NOW();" 2>/dev/null || echo 0)

mysql -h "${DB_HOST}" -u "${DB_USER}" -p"${DB_PASS}" -D "${DB_NAME}" \
    -e "${EXPIRED_QUERY}" 2>/dev/null || {
    log_msg "ERROR" "Failed to update expired requests"
    send_alert "Deletion Cleanup Error" "Failed to mark expired deletion requests"
    exit 1
}

log_msg "INFO" "Marked ${EXPIRED_COUNT} expired deletion requests"

# ============================================================================
# STEP 2: Get confirmed deletion requests
# ============================================================================

log_msg "INFO" "Finding confirmed deletion requests..."

mysql -h "${DB_HOST}" -u "${DB_USER}" -p"${DB_PASS}" -D "${DB_NAME}" -N -B \
    -e "SELECT user_id FROM data_deletion_requests 
        WHERE status = 'CONFIRMED_BY_USER' 
        ORDER BY confirmed_by_user_at ASC;" \
    2>/dev/null > "${USERS_TO_DELETE}" || {
    log_msg "ERROR" "Failed to query deletion requests"
    send_alert "Deletion Cleanup Error" "Failed to query deletion requests from database"
    exit 1
}

USER_COUNT=$(wc -l < "${USERS_TO_DELETE}")
log_msg "INFO" "Found ${USER_COUNT} confirmed deletion request(s)"

# ============================================================================
# STEP 3: Delete each user (cascades to all related tables)
# ============================================================================

if [ ${USER_COUNT} -gt 0 ]; then
    log_msg "INFO" "Processing confirmed deletions..."
    
    DELETED_COUNT=0
    FAILED_COUNT=0
    
    while IFS= read -r user_id; do
        if [ -z "${user_id}" ]; then
            continue
        fi
        
        log_msg "INFO" "Deleting user_id=${user_id}..."
        
        # Get user email before deletion (for logging)
        USER_EMAIL=$(mysql -h "${DB_HOST}" -u "${DB_USER}" -p"${DB_PASS}" -D "${DB_NAME}" -N -B \
            -e "SELECT email FROM users WHERE id = ${user_id};" 2>/dev/null || echo "unknown")
        
        # Delete user (cascades to candidatures, wishlist, evaluations, etc.)
        DELETE_QUERY="DELETE FROM users WHERE id = ${user_id} LIMIT 1;"
        
        if mysql -h "${DB_HOST}" -u "${DB_USER}" -p"${DB_PASS}" -D "${DB_NAME}" \
            -e "${DELETE_QUERY}" 2>/dev/null; then
            
            log_msg "INFO" "Successfully deleted user ${user_id} (${USER_EMAIL})"
            DELETED_COUNT=$((DELETED_COUNT + 1))
            
            # Update deletion request status to EXECUTED
            UPDATE_QUERY="
                UPDATE data_deletion_requests 
                SET status = 'EXECUTED', 
                    deleted_at = NOW() 
                WHERE user_id = ${user_id};
            "
            mysql -h "${DB_HOST}" -u "${DB_USER}" -p"${DB_PASS}" -D "${DB_NAME}" \
                -e "${UPDATE_QUERY}" 2>/dev/null || {
                log_msg "WARN" "Failed to update status to EXECUTED for user ${user_id}"
            }
            
            # Log to compliance audit
            AUDIT_QUERY="
                INSERT INTO compliance_audit_log 
                (request_type, user_email_hash, status, notes)
                VALUES (
                    'DELETION_EXECUTED',
                    SHA2('${USER_EMAIL}', 256),
                    'COMPLETED',
                    CONCAT('User ID: ', ${user_id}, ' deleted successfully')
                );
            "
            mysql -h "${DB_HOST}" -u "${DB_USER}" -p"${DB_PASS}" -D "${DB_NAME}" \
                -e "${AUDIT_QUERY}" 2>/dev/null || {
                log_msg "WARN" "Failed to log deletion to audit table"
            }
        else
            log_msg "ERROR" "Failed to delete user ${user_id}"
            FAILED_COUNT=$((FAILED_COUNT + 1))
            
            # Update status to FAILED
            UPDATE_QUERY="
                UPDATE data_deletion_requests 
                SET status = 'FAILED', 
                    error_message = 'Deletion query failed',
                    retry_count = retry_count + 1
                WHERE user_id = ${user_id};
            "
            mysql -h "${DB_HOST}" -u "${DB_USER}" -p"${DB_PASS}" -D "${DB_NAME}" \
                -e "${UPDATE_QUERY}" 2>/dev/null || true
        fi
    done < "${USERS_TO_DELETE}"
    
    log_msg "INFO" "Deletion processing complete: ${DELETED_COUNT} succeeded, ${FAILED_COUNT} failed"
    
    if [ ${FAILED_COUNT} -gt 0 ]; then
        send_alert "Deletion Cleanup Partial Failure" \
            "Deletion cleanup: ${DELETED_COUNT} succeeded, ${FAILED_COUNT} failed. Check ${LOG_FILE}"
    fi
else
    log_msg "INFO" "No confirmed deletions to process"
fi

# ============================================================================
# STEP 4: Trigger orphan file cleanup (optional)
# ============================================================================

# If deletion occurred, trigger orphan file cleanup
if [ ${DELETED_COUNT} -gt 0 ]; then
    log_msg "INFO" "Triggering orphan file cleanup after deletions..."
    if [ -x "/path/to/scripts/cleanup-orphan-uploads.sh" ]; then
        /path/to/scripts/cleanup-orphan-uploads.sh >> "${LOG_FILE}" 2>&1 || {
            log_msg "WARN" "Orphan file cleanup script failed or not found"
        }
    fi
fi

# ============================================================================
# STEP 5: Summary
# ============================================================================

log_msg "INFO" "Deletion request processing completed successfully"

exit 0
