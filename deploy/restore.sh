#!/usr/bin/env bash
#
# Restore a backup made by `php artisan backup:run`.
#
#   deploy/restore.sh /path/to/backup.zip            # unpack into ./restore-tmp and show what is inside
#   deploy/restore.sh /path/to/backup.zip --apply    # also put files back and load the database
#
# Needs the archive password (BACKUP_ARCHIVE_PASSWORD) in the environment or typed when asked, and the
# database credentials in .env. The zip is AES encrypted, which plain `unzip` cannot read, so PHP is used.
#
# Always restore into an empty database on a machine you are testing on first. A backup you have never
# restored is only a hope.

set -euo pipefail

ZIP="${1:-}"
APPLY="${2:-}"
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TMP="$APP_DIR/restore-tmp"

if [[ -z "$ZIP" || ! -f "$ZIP" ]]; then
    echo "Usage: deploy/restore.sh /path/to/backup.zip [--apply]" >&2
    exit 1
fi

if [[ -z "${BACKUP_ARCHIVE_PASSWORD:-}" ]]; then
    read -r -s -p "Archive password: " BACKUP_ARCHIVE_PASSWORD
    echo
fi
export BACKUP_ARCHIVE_PASSWORD

rm -rf "$TMP"
mkdir -p "$TMP"

ZIP_PATH="$ZIP" TMP_DIR="$TMP" php -r '
$zip = new ZipArchive();
if ($zip->open(getenv("ZIP_PATH")) !== true) { fwrite(STDERR, "Cannot open the archive.\n"); exit(1); }
$zip->setPassword(getenv("BACKUP_ARCHIVE_PASSWORD"));
if (!$zip->extractTo(getenv("TMP_DIR"))) { fwrite(STDERR, "Could not extract: wrong password or damaged archive.\n"); exit(1); }
echo "Extracted " . $zip->numFiles . " entries.\n";
'

echo "Unpacked into $TMP:"
(cd "$TMP" && find . -type f | sort | sed 's/^/  /')

if [[ "$APPLY" != "--apply" ]]; then
    echo
    echo "Nothing has been changed. Run again with --apply to restore the files and the database."
    exit 0
fi

DUMP="$(find "$TMP/db-dumps" -type f -name '*.sql' | head -n 1)"
if [[ -z "$DUMP" ]]; then
    echo "No database dump found in the archive." >&2
    exit 1
fi

# Read the database settings from the .env that is already on this machine.
get_env() { grep -E "^$1=" "$APP_DIR/.env" | head -n 1 | cut -d= -f2- | tr -d '"'; }
DB_HOST="$(get_env DB_HOST)"; DB_PORT="$(get_env DB_PORT)"; DB_NAME="$(get_env DB_DATABASE)"
DB_USER="$(get_env DB_USERNAME)"; DB_PASS="$(get_env DB_PASSWORD)"

echo "Loading $DUMP into database '$DB_NAME' on ${DB_HOST:-127.0.0.1}. Existing tables with the same names will be replaced."
read -r -p "Type the database name to continue: " CONFIRM
[[ "$CONFIRM" == "$DB_NAME" ]] || { echo "Cancelled."; exit 1; }

MYSQL_PWD="$DB_PASS" mysql -h "${DB_HOST:-127.0.0.1}" -P "${DB_PORT:-3306}" -u "$DB_USER" "$DB_NAME" < "$DUMP"

# Uploads and private files go back where they were. The saved .env is left in the folder: compare it with the live one by hand.
[[ -d "$TMP/storage/app/public" ]] && cp -R "$TMP/storage/app/public/." "$APP_DIR/storage/app/public/"
[[ -d "$TMP/storage/app/private" ]] && cp -R "$TMP/storage/app/private/." "$APP_DIR/storage/app/private/"

cd "$APP_DIR"
php artisan optimize:clear
php artisan responsecache:clear || true

echo
echo "Restored. The saved .env is at $TMP/.env (APP_KEY must match it, or the encrypted 2FA secrets cannot be read)."
echo "Delete $TMP when you are done: it contains secrets."
