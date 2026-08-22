#!/usr/bin/env bash
# Export this project's SQLite schema as the JSON blob ChartDB asks you to paste
# into its import box (https://chartdb.io -> Import database -> SQLite).
#
# Usage:
#   ./scripts/chartdb-export.sh                  # auto-detects the DB from .env
#   ./scripts/chartdb-export.sh path/to/db.sqlite
#   ./scripts/chartdb-export.sh -o chartdb.json  # also write the JSON to a file
#
# Prints the single-line JSON to stdout, and copies it to the clipboard when
# pbcopy / xclip / wl-copy / clip.exe is available.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SQL_FILE="$ROOT/scripts/chartdb-export.sql"
DB=""
OUT=""

while [ $# -gt 0 ]; do
  case "$1" in
    -o|--out) OUT="${2:?-o needs a file path}"; shift 2 ;;
    -h|--help) sed -n '2,12p' "$0"; exit 0 ;;
    *) DB="$1"; shift ;;
  esac
done

# Resolve the database file: explicit arg > DB_DATABASE in .env > Laravel default.
if [ -z "$DB" ]; then
  if [ -f "$ROOT/.env" ]; then
    DB="$(sed -n 's/^[[:space:]]*DB_DATABASE[[:space:]]*=[[:space:]]*//p' "$ROOT/.env" | tail -n1 | tr -d '"'"'"'' | tr -d '\r')"
  fi
  [ -z "$DB" ] && DB="$ROOT/database/database.sqlite"
fi
case "$DB" in /*) ;; *) DB="$ROOT/$DB" ;; esac

[ -f "$SQL_FILE" ] || { echo "missing $SQL_FILE" >&2; exit 1; }
[ -f "$DB" ] || { echo "SQLite database not found: $DB" >&2; exit 1; }

# sqlite3 if it is installed, otherwise PHP's PDO (always present in this project).
if command -v sqlite3 >/dev/null 2>&1; then
  JSON="$(sqlite3 -readonly -noheader -batch "$DB" < "$SQL_FILE")"
elif command -v php >/dev/null 2>&1; then
  JSON="$(SQL_FILE="$SQL_FILE" DB_FILE="$DB" php -r '
    $pdo = new PDO("sqlite:" . getenv("DB_FILE"), null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $row = $pdo->query(file_get_contents(getenv("SQL_FILE")))->fetch(PDO::FETCH_NUM);
    echo $row[0] ?? "";
  ')"
else
  echo "need either sqlite3 or php on PATH" >&2; exit 1
fi

[ -n "$JSON" ] || { echo "query returned nothing - is $DB an empty database?" >&2; exit 1; }

if [ -n "$OUT" ]; then
  printf '%s' "$JSON" > "$OUT"
  echo "wrote $OUT" >&2
fi

for CP in pbcopy "xclip -selection clipboard" wl-copy clip.exe; do
  if command -v "${CP%% *}" >/dev/null 2>&1; then
    printf '%s' "$JSON" | $CP >/dev/null 2>&1 && echo "(copied to clipboard)" >&2
    break
  fi
done

printf '%s\n' "$JSON"
