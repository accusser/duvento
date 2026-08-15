#!/usr/bin/env bash
set -euo pipefail

root="$(cd "$(dirname "$0")/.." && pwd)"
dest="${1:-$root/dist/duvento-oss}"

mkdir -p "$(dirname "$dest")"
rm -rf "$dest"

rsync -a \
  --exclude '/.git/' \
  --exclude '/vendor/' \
  --exclude '/node_modules/' \
  --exclude '/packages/duvento-cloud/' \
  --exclude '/тз/' \
  --exclude '/1fila/' \
  --exclude '/1Nyvora/' \
  --exclude '/dist/' \
  --exclude '/releases/' \
  --exclude '/.env' \
  --exclude '/.cursor/' \
  --exclude '/database/database.sqlite' \
  --exclude '/storage/logs/*.log' \
  --exclude '/storage/framework/cache/data/' \
  --exclude '/storage/framework/lsp-*.php' \
  --exclude '/storage/framework/sessions/' \
  --exclude '/storage/framework/views/' \
  --exclude '/storage/app/private/' \
  --exclude '/storage/app/installed' \
  --exclude '/storage/app/*.backup' \
  "$root/" "$dest/"

mkdir -p "$dest/storage/app/private" \
  "$dest/storage/framework/cache/data" \
  "$dest/storage/framework/sessions" \
  "$dest/storage/framework/views" \
  "$dest/storage/logs" \
  "$dest/bootstrap/cache"
cp "$root/storage/app/private/.gitignore" "$dest/storage/app/private/.gitignore"
cp "$root/storage/framework/views/.gitignore" "$dest/storage/framework/views/.gitignore"

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

leaks=(packages/duvento-cloud тз 1fila 1Nyvora)
for leak in "${leaks[@]}"; do
  if [ -e "$dest/$leak" ]; then
    echo "OSS leak: $leak" >&2
    exit 1
  fi
done

if grep -q 'duvento/cloud' "$dest/composer.json"; then
  echo "OSS leak: duvento/cloud in composer.json" >&2
  exit 1
fi

echo "OSS-копия: $dest"
