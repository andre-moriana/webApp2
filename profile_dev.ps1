# Configuration optimisée pour l'environnement de développement
# Charger les fonctions personnalisées
. .\powershell_functions.ps1

# Configuration de l'encodage
$PSDefaultParameterValues['Out-File:Encoding'] = 'utf8'
$PSDefaultParameterValues['*:Encoding'] = 'utf8'

# Alias utiles
Set-Alias -Name "nphp" -Value "New-PHPFile"
Set-Alias -Name "rphp" -Value "Read-PHPFile"
Set-Alias -Name "ephp" -Value "Edit-PHPFile"
Set-Alias -Name "exphp" -Value "Invoke-PHPScript"

# Fonction pour créer des fichiers PHP rapidement
function Quick-PHP {
    param([string]$Name, [string]$Content)
    New-PHPFile $Name $Content
}

# Fonction pour tester l'environnement
function Test-Environment {
    Write-Host "=== Test de l'environnement ===" -ForegroundColor Cyan
    Write-Host "Répertoire actuel: $(Get-Location)" -ForegroundColor Yellow
    Write-Host "Version PHP: $(php --version | Select-Object -First 1)" -ForegroundColor Yellow
    Write-Host "Fichiers PHP: $(Get-ChildItem -Filter '*.php' | Measure-Object).Count" -ForegroundColor Yellow
    Write-Host "=== Test terminé ===" -ForegroundColor Cyan
}

Write-Host " Environnement de développement configuré" -ForegroundColor Green
Write-Host "Commandes disponibles:" -ForegroundColor Yellow
Write-Host "  Quick-PHP 'fichier.php' 'contenu'" -ForegroundColor White
Write-Host "  Test-Environment" -ForegroundColor White
Write-Host "  nphp, rphp, ephp, exphp" -ForegroundColor White
