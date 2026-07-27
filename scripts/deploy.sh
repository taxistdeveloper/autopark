#!/usr/bin/env bash
# Деплой: git pull + пересборка changelog
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

echo "==> git pull"
git pull --ff-only

PHP_BIN="${PHP_BIN:-php}"
if ! command -v "$PHP_BIN" >/dev/null 2>&1; then
  echo "PHP не найден (задайте PHP_BIN). Changelog соберётся при первом заходе на главную."
  exit 0
fi

echo "==> rebuild changelog"
"$PHP_BIN" "$ROOT/scripts/rebuild_changelog.php"
echo "==> готово"
