# Configure ce dépôt pour utiliser .githooks/ (post-commit = git push auto)
Set-Location $PSScriptRoot\..
git config core.hooksPath .githooks
Write-Host "OK: core.hooksPath = .githooks — chaque 'git commit' sera suivi d'un 'git push'." -ForegroundColor Green
