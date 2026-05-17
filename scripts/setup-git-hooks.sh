#!/usr/bin/env sh
# Configure ce dépôt pour utiliser .githooks/ (post-commit = git push auto)
cd "$(dirname "$0")/.." || exit 1
git config core.hooksPath .githooks
echo "OK: core.hooksPath = .githooks — chaque 'git commit' sera suivi d'un 'git push'."
