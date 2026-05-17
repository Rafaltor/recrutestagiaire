#!/usr/bin/env sh
# Commit + push à la fin d'une tâche Agent Cursor (événement stop, status completed).
# Pas de jq : évite les commits si la tâche est aborted / error.

set -eu

INPUT=$(cat || true)

# Refuser de committer si l'agent a été interrompu ou en erreur
case "$INPUT" in
  *'"status":"aborted"'*|*'"status": "aborted"'*)
    printf '%s\n' '{}'
    exit 0
    ;;
  *'"status":"error"'*|*'"status": "error"'*)
    printf '%s\n' '{}'
    exit 0
    ;;
esac

cd "$(git rev-parse --show-toplevel 2>/dev/null)" || {
  printf '%s\n' '{}'
  exit 0
}

git add -A

if git diff --cached --quiet; then
  printf '%s\n' '{}'
  exit 0
fi

DATE=$(date -u +%Y-%m-%dT%H:%MZ 2>/dev/null || date +%Y-%m-%dT%H:%M%z)
MSG="chore(cursor): sync auto fin tâche agent ($DATE)"

git commit -m "$MSG" || {
  printf '%s\n' '{}'
  exit 0
}

# Push : configuré via .githooks/post-commit (voir scripts/setup-git-hooks.ps1)

printf '%s\n' '{}'
exit 0
