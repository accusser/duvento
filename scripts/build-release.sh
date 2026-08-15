#!/usr/bin/env bash
set -euo pipefail

root="$(cd "$(dirname "$0")/.." && pwd)"
version="${1:-$(date +%Y%m%d-%H%M%S)}"
staging="$root/dist/duvento-$version"
releases="$root/releases"
archive="$releases/$version.zip"

mkdir -p "$releases"
"$root/scripts/export-oss.sh" "$staging"

(
  cd "$staging"
  composer install --no-dev --optimize-autoloader --no-interaction
  npm install --ignore-scripts --no-audit --no-fund
  npm run build
  rm -rf node_modules
  mkdir -p storage/app storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
  rm -f .env database/database.sqlite
)

rm -f "$archive"
(
  cd "$staging"
  zip -qr "$archive" .
)

rm -rf "$staging"

echo "Release: $archive"
