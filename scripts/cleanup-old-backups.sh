#!/bin/bash

################################################################################
# cleanup-old-backups.sh
# 
# PURPOSE: Delete database backup files older than 30 days
# 
# USAGE: ./scripts/cleanup-old-backups.sh
#        (typically run via cron daily, e.g., 0 3 * * * /path/to/cleanup-old-backups.sh)
#
# DEPENDENCIES:
#   - find command
#   - Write access to backup directory
#   - Logging directory writable
#
# OUTPUT:
#   - Logs to /var/log/app/cleanup-backups.log (or ${LOG_FILE} var)
#   - Creates log file if not exists
################################################################################

set -e  # Exit on error

# Configuration
BACKUP_DIR="/path/to/backups"  # CHANGE THIS to your backup directory
LOG_FILE="/var/log/app/cleanup-backups.log"
RETENTION_DAYS=30
MAX_LOG_SIZE=10485760  # 10 MB, rotate if exceeded
SCRIPT_NAME="cleanup-old-backups.sh"

# Initialize or create log file
if [ ! -f "${LOG_FILE}" ]; then
    mkdir -p "$(dirname "${LOG_FILE}")"
    touch "${LOG_FILE}"
fi

# Rotate log if too large
if [ -f "${LOG_FILE}" ] && [ $(stat -f%z "${LOG_FILE}" 2>/dev/null || stat -c%s "${LOG_FILE}") -gt ${MAX_LOG_SIZE} ]; then
    mv "${LOG_FILE}" "${LOG_FILE}.$(date +%s).old"
    gzip "${LOG_FILE}".*.old 2>/dev/null || true
    touch "${LOG_FILE}"
fi

# Function: log message with timestamp
log_msg() {
    local level=$1
    shift
    local msg="$@"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] [${level}] ${msg}" >> "${LOG_FILE}"
}

# Function: send alert email (optional)
send_alert() {
    local subject=$1
    local body=$2
    echo "${body}" | mail -s "${subject}" tech-team@example.fr 2>/dev/null || true
}

# Check backup directory exists
if [ ! -d "${BACKUP_DIR}" ]; then
    log_msg "ERROR" "Backup directory not found: ${BACKUP_DIR}"
    send_alert "Backup Cleanup Failed" "Backup directory not found: ${BACKUP_DIR}"
    exit 1
fi

log_msg "INFO" "Starting backup cleanup (retention: ${RETENTION_DAYS} days)"
log_msg "INFO" "Backup directory: ${BACKUP_DIR}"

# Find and delete old backups
# Pattern: *.sql.gz (adjust pattern if your backups use different names)
DELETED_COUNT=0
DELETED_SIZE=0

while IFS= read -r backup_file; do
    if [ -f "${backup_file}" ]; then
        file_size=$(stat -f%z "${backup_file}" 2>/dev/null || stat -c%s "${backup_file}")
        rm -f "${backup_file}"
        DELETED_COUNT=$((DELETED_COUNT + 1))
        DELETED_SIZE=$((DELETED_SIZE + file_size))
        log_msg "INFO" "Deleted: $(basename "${backup_file}") ($(numfmt --to=iec ${file_size} 2>/dev/null || echo "${file_size} bytes"))"
    fi
done < <(find "${BACKUP_DIR}" \
    -maxdepth 1 \
    -name "*.sql.gz" \
    -o -name "*.sql" \
    -o -name "*.tar.gz" \
    | while read f; do
        [ $(find "${BACKUP_DIR}" -name "$(basename "$f")" -mtime +${RETENTION_DAYS} 2>/dev/null) ] && echo "$f"
    done)

# Alternative (simpler, POSIX-compliant):
# find "${BACKUP_DIR}" \
#     -maxdepth 1 \
#     -type f \
#     \( -name "*.sql.gz" -o -name "*.sql" -o -name "*.tar.gz" \) \
#     -mtime +${RETENTION_DAYS} \
#     -delete

log_msg "INFO" "Cleanup completed: ${DELETED_COUNT} file(s) deleted ($(numfmt --to=iec ${DELETED_SIZE} 2>/dev/null || echo "${DELETED_SIZE} bytes"))"

# Check if backup directory now has any files
REMAINING=$(find "${BACKUP_DIR}" -maxdepth 1 -type f | wc -l)
log_msg "INFO" "Remaining backup files: ${REMAINING}"

# Optional: Verify no errors
if [ $? -eq 0 ]; then
    log_msg "INFO" "Backup cleanup completed successfully"
else
    log_msg "ERROR" "Backup cleanup encountered errors"
    send_alert "Backup Cleanup Errors" "backup cleanup script encountered errors. Check ${LOG_FILE}"
    exit 1
fi

exit 0
