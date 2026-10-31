#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
BASE_COMMIT="654eb3e23e95626c112328277de63ded66239e19"
WORK="/tmp/evruni-creator-legacy-$$"

cleanup(){ git worktree remove --force "$WORK" 2>/dev/null || true; }
trap cleanup EXIT

git fetch origin
git worktree add --detach "$WORK" origin/main
cd "$WORK"
git show "$BASE_COMMIT:creator-tools.php" > creator-tools.php
/opt/alt/php84/usr/bin/php -l creator-tools.php

git add creator-tools.php
git commit -m "Restore legacy Creator Tools UI with Film Guard extension"
git push origin HEAD:main

echo "Creator Tools restored from $BASE_COMMIT"
