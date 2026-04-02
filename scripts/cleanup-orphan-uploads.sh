#!/bin/bash

################################################################################
# cleanup-orphan-uploads.sh
#
# PURPOSE: Delete uploaded files (CVs, letters) that are no longer referenced
#          in the database (orphaned files from deleted applications/users)
#
# WORKFLOW:
#   1. Query database for all current file references (from candidatures table)
#   2. List all files in /cdn/uploads/cvs/ and /cdn/uploads/lm/
#   3. Delete files not in database
#   4. Log deleted files and sizes
#
# USAGE: ./scripts/cleanup-orphan-uploads.sh
#        (typically run via cron weekly, e.g., 0 2 * * 0 /path/to/cleanup-orphan-uploads.sh)
#
# DEPENDENCIES:
#   - MySQL/MariaDB client
#   - find, rm, stat commands
#   - Write access to upload directory
#   - DB credentials in environment or config file
#
# OUTPUT:
#   - Logs to /var/log/app/cleanup-uploads.log
################################################################################

set -e

# Configuration
UPLOAD_BASE_DIR="/path/to/www/cdn/uploads"  # CHANGE THIS
CV_DIR="${UPLOAD_BASE_DIR}/cvs"
LM_DIR="${UPLOAD_BASE_DIR}/lm"
LOG_FILE="/var/log/app/cleanup-uploads.log"
MAX_LOG_SIZE=10485760
SCRIPT_NAME="cleanup-orphan-uploads.sh"

# Database Configuration (load from config or env)
# Option 1: Source from config file
if [ -f "/etc/app/db-config.sh" ]; then
    source /etc/app/db-config.sh
fi

# Option 2: Use environment variables (set by deployment)
DB_HOST="${DB_HOST:-localhost}"
DB_USER="${DB_USER:-app_user}"
DB_PASS="${DB_PASS:-}"
DB_NAME="${DB_NAME:-app_db}"
DB_PORT="${DB_PORT:-3306}"

# Temp directory for file lists
TEMP_DIR="/tmp/cleanup-uploads-$$"
DB_FILES_LIST="${TEMP_DIR}/db-files.txt"
FS_FILES_LIST="${TEMP_DIR}/fs-files.txt"
ORPHAN_FILES="${TEMP_DIR}/orphans.txt"

# Cleanup on exit
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

log_msg "INFO" "Starting orphan upload cleanup"

# Verify upload directories exist
if [ ! -d "${CV_DIR}" ] || [ ! -d "${LM_DIR}" ]; then
    log_msg "ERROR" "Upload directories not found: ${CV_DIR} or ${LM_DIR}"
    send_alert "Orphan Upload Cleanup Failed" "Upload directories missing"
    exit 1
fi

log_msg "INFO" "CV directory: ${CV_DIR}"
log_msg "INFO" "LM directory: ${LM_DIR}"

# ============================================================================
# STEP 1: Get list of files referenced in database
# ============================================================================

log_msg "INFO" "Querying database for referenced files..."

# Query: Get all CV and LM file paths from candidatures table
mysql -h "${DB_HOST}" -u "${DB_USER}" -p"${DB_PASS}" -D "${DB_NAME}" -N -B \
    -e "SELECT cv_path FROM candidatures WHERE cv_path IS NOT NULL AND cv_path != ''
        UNION
        SELECT lettre_motivation_path FROM candidatures WHERE lettre_motivation_path IS NOT NULL AND lettre_motivation_path != '';" \
    2>/dev/null | awk '{print $NF}' > "${DB_FILES_LIST}" || true

DB_FILE_COUNT=$(wc -l < "${DB_FILES_LIST}")
log_msg "INFO" "Files referenced in database: ${DB_FILE_COUNT}"

# ============================================================================
# STEP 2: List all files in filesystem
# ============================================================================

log_msg "INFO" "Scanning filesystem for upload files..."

find "${CV_DIR}" -maxdepth 1 -type f -printf '%f\n' > "${FS_FILES_LIST}" || true
find "${LM_DIR}" -maxdepth 1 -type f -printf '%f\n' >> "${FS_FILES_LIST}" || true

FS_FILE_COUNT=$(wc -l < "${FS_FILES_LIST}")
log_msg "INFO" "Files found in filesystem: ${FS_FILE_COUNT}"

# ============================================================================
# STEP 3: Find orphaned files (in FS but not in DB)
# ============================================================================

log_msg "INFO" "Identifying orphaned files..."

# Create associative array of DB files (for quick lookup)
declare -A DB_FILES
while IFS= read -r file; do
    # Extract filename only (in case DB has full paths)
    filename=$(basename "${file}")
    DB_FILES["${filename}"]=1
done < "${DB_FILES_LIST}"

# Find orphans
> "${ORPHAN_FILES}"  # Clear file
while IFS= read -r file; do
    if [ -z "${DB_FILES["${file}"]}" ]; then
        echo "${file}" >> "${ORPHAN_FILES}"
    fi
done < "${FS_FILES_LIST}"

ORPHAN_COUNT=$(wc -l < "${ORPHAN_FILES}")
log_msg "INFO" "Orphaned files identified: ${ORPHAN_COUNT}"

# ============================================================================
# STEP 4: Delete orphaned files
# ============================================================================

if [ ${ORPHAN_COUNT} -gt 0 ]; then
    log_msg "INFO" "Deleting orphaned files..."
    
    DELETED_COUNT=0
    DELETED_SIZE=0
    
    while IFS= read -r orphan_file; do
        # Try to find file in either CV or LM directory
        if [ -f "${CV_DIR}/${orphan_file}" ]; then
            file_path="${CV_DIR}/${orphan_file}"
        elif [ -f "${LM_DIR}/${orphan_file}" ]; then
            file_path="${LM_DIR}/${orphan_file}"
        else
            log_msg "WARN" "Orphan file not found in FS (may have been deleted): ${orphan_file}"
            continue
        fi
        
        file_size=$(stat -c%s "${file_path}" 2>/dev/null || echo 0)
        
        # Delete file
        rm -f "${file_path}"
        
        DELETED_COUNT=$((DELETED_COUNT + 1))
        DELETED_SIZE=$((DELETED_SIZE + file_size))
        
        log_msg "INFO" "Deleted orphan: ${orphan_file} ($(numfmt --to=iec ${file_size} 2>/dev/null || echo "${file_size} bytes"))"
    done < "${ORPHAN_FILES}"
    
    log_msg "INFO" "Deletion complete: ${DELETED_COUNT} file(s) deleted ($(numfmt --to=iec ${DELETED_SIZE} 2>/dev/null || echo "${DELETED_SIZE} bytes") freed)"
else
    log_msg "INFO" "No orphaned files found"
fi

# ============================================================================
# STEP 5: Summary
# ============================================================================

REMAINING_CV=$(find "${CV_DIR}" -maxdepth 1 -type f | wc -l)
REMAINING_LM=$(find "${LM_DIR}" -maxdepth 1 -type f | wc -l)

log_msg "INFO" "Cleanup summary:"
log_msg "INFO" "  CVs remaining: ${REMAINING_CV}"
log_msg "INFO" "  LMs remaining: ${REMAINING_LM}"
log_msg "INFO" "Orphan upload cleanup completed successfully"

exit 0
