# Script PowerShell pour générer les favicons
# Nécessite ImageMagick installé : https://imagemagick.org/script/download.php

$sourcePath = "d:\GEMENOS\WebApp2\public\assets\images\favicon-source.png"
$outputDir = "d:\GEMENOS\WebApp2\public\assets\images\favicon"
$publicDir = "d:\GEMENOS\WebApp2\public"

Write-Host "=== Génération des Favicons ===" -ForegroundColor Cyan
Write-Host ""

# Vérifier si ImageMagick est installé
$magickInstalled = $false
try {
    $magickVersion = magick --version 2>&1
    if ($LASTEXITCODE -eq 0) {
        $magickInstalled = $true
        Write-Host "✓ ImageMagick détecté" -ForegroundColor Green
    }
} catch {
    Write-Host "✗ ImageMagick non trouvé" -ForegroundColor Red
}

if (-not $magickInstalled) {
    Write-Host ""
    Write-Host "ImageMagick n'est pas installé." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Solutions:" -ForegroundColor Cyan
    Write-Host "1. Installer ImageMagick depuis: https://imagemagick.org/script/download.php" -ForegroundColor White
    Write-Host "2. Utiliser un service en ligne: https://realfavicongenerator.net/" -ForegroundColor White
    Write-Host "   - Uploader: $sourcePath" -ForegroundColor Gray
    Write-Host "   - Télécharger le package généré" -ForegroundColor Gray
    Write-Host "   - Extraire dans: $outputDir" -ForegroundColor Gray
    Write-Host ""
    exit 1
}

# Vérifier si le fichier source existe
if (-not (Test-Path $sourcePath)) {
    Write-Host "✗ Fichier source non trouvé: $sourcePath" -ForegroundColor Red
    exit 1
}

Write-Host "✓ Fichier source trouvé: $sourcePath" -ForegroundColor Green
Write-Host ""
Write-Host "Génération des favicons..." -ForegroundColor Cyan

# Créer le dossier de sortie s'il n'existe pas
New-Item -ItemType Directory -Path $outputDir -Force | Out-Null

# Générer les différentes tailles
$sizes = @(
    @{Size=16; Name="favicon-16.png"},
    @{Size=32; Name="favicon-32.png"},
    @{Size=48; Name="favicon-48.png"},
    @{Size=180; Name="apple-touch-icon.png"},
    @{Size=120; Name="apple-touch-icon-120x120.png"},
    @{Size=152; Name="apple-touch-icon-152x152.png"},
    @{Size=192; Name="android-chrome-192x192.png"},
    @{Size=512; Name="android-chrome-512x512.png"},
    @{Size=150; Name="mstile-150x150.png"}
)

foreach ($item in $sizes) {
    $outputPath = Join-Path $outputDir $item.Name
    Write-Host "  Génération: $($item.Name) ($($item.Size)x$($item.Size))..." -NoNewline
    
    try {
        & magick $sourcePath -resize "$($item.Size)x$($item.Size)" $outputPath
        if ($LASTEXITCODE -eq 0) {
            Write-Host " ✓" -ForegroundColor Green
        } else {
            Write-Host " ✗" -ForegroundColor Red
        }
    } catch {
        Write-Host " ✗ Erreur: $($_.Exception.Message)" -ForegroundColor Red
    }
}

# Générer le favicon.ico multi-taille
Write-Host ""
Write-Host "Génération du favicon.ico multi-taille..." -NoNewline
try {
    $ico16 = Join-Path $outputDir "favicon-16.png"
    $ico32 = Join-Path $outputDir "favicon-32.png"
    $ico48 = Join-Path $outputDir "favicon-48.png"
    $icoOutput = Join-Path $outputDir "favicon.ico"
    
    & magick $ico16 $ico32 $ico48 $icoOutput
    if ($LASTEXITCODE -eq 0) {
        Write-Host " ✓" -ForegroundColor Green
    } else {
        Write-Host " ✗" -ForegroundColor Red
    }
} catch {
    Write-Host " ✗ Erreur: $($_.Exception.Message)" -ForegroundColor Red
}

# Copier le favicon.ico à la racine du public
Write-Host "Copie du favicon.ico vers la racine public..." -NoNewline
try {
    $publicFavicon = Join-Path $publicDir "favicon.ico"
    Copy-Item $icoOutput $publicFavicon -Force
    Write-Host " ✓" -ForegroundColor Green
} catch {
    Write-Host " ✗ Erreur: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "=== Génération terminée ===" -ForegroundColor Green
Write-Host ""
Write-Host "Fichiers générés dans: $outputDir" -ForegroundColor Cyan
Write-Host "Favicon principal copié dans: $publicDir\favicon.ico" -ForegroundColor Cyan
Write-Host ""
Write-Host "Prochaines étapes:" -ForegroundColor Yellow
Write-Host "1. Vider le cache du navigateur (Ctrl + Shift + Del)" -ForegroundColor White
Write-Host "2. Recharger la page (Ctrl + F5)" -ForegroundColor White
Write-Host "3. Vérifier que le favicon s'affiche dans l'onglet" -ForegroundColor White
Write-Host ""
