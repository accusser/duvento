#!/usr/bin/env bash
set -euo pipefail

root="$(cd "$(dirname "$0")/.." && pwd)"
dest="${1:-$root/dist/duvento-oss}"

mkdir -p "$(dirname "$dest")"
rm -rf "$dest"

rsync -a \
  --exclude '.git/' \
  --exclude 'vendor/' \
  --exclude 'node_modules/' \
  --exclude 'packages/duvento-cloud/' \
  --exclude 'тз/' \
  --exclude 'dist/' \
  --exclude '.env' \
  --exclude 'database/database.sqlite' \
  --exclude 'storage/logs/*' \
  --exclude 'storage/framework/cache/*' \
  --exclude 'storage/framework/sessions/*' \
  --exclude 'storage/framework/views/*' \
  "$root/" "$dest/"

php -r '
$path = $argv[1];
$composer = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
unset($composer["require"]["duvento/cloud"]);
$composer["repositories"] = array_values(array_filter(
    $composer["repositories"] ?? [],
    fn ($repo) => ($repo["url"] ?? "") !== "packages/duvento-cloud"
));
if ($composer["repositories"] === []) {
    unset($composer["repositories"]);
}
file_put_contents($path, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
' "$dest/composer.json"

rm -f "$dest/composer.lock"
if command -v composer >/dev/null; then
  (cd "$dest" && composer update --no-install --no-scripts --no-interaction)
fi

echo "OSS-копия: $dest"
