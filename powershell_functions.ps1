# Scripts PowerShell optimisés pour l'environnement
# Créer un fichier PHP simple
function New-PHPFile {
    param(
        [string]$Path,
        [string]$Content
    )
    try {
        [System.IO.File]::WriteAllText($Path, $Content, [System.Text.UTF8Encoding]::new($false))
        Write-Host " Fichier créé: $Path" -ForegroundColor Green
        return $true
    } catch {
        Write-Host " Erreur création: $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

# Lire un fichier PHP
function Read-PHPFile {
    param([string]$Path)
    try {
        return [System.IO.File]::ReadAllText($Path, [System.Text.UTF8Encoding]::new($false))
    } catch {
        Write-Host " Erreur lecture: $($_.Exception.Message)" -ForegroundColor Red
        return $null
    }
}

# Modifier un fichier PHP
function Edit-PHPFile {
    param(
        [string]$Path,
        [string]$OldText,
        [string]$NewText
    )
    try {
        $content = Read-PHPFile $Path
        if ($content) {
            $newContent = $content -replace [regex]::Escape($OldText), $NewText
            New-PHPFile $Path $newContent
            Write-Host " Fichier modifié: $Path" -ForegroundColor Green
        }
    } catch {
        Write-Host " Erreur modification: $($_.Exception.Message)" -ForegroundColor Red
    }
}

# Exécuter un script PHP
function Invoke-PHPScript {
    param([string]$ScriptPath)
    try {
        $result = & php $ScriptPath 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Host " Script exécuté: $ScriptPath" -ForegroundColor Green
            return $result
        } else {
            Write-Host " Erreur script: $result" -ForegroundColor Red
            return $null
        }
    } catch {
        Write-Host " Erreur exécution: $($_.Exception.Message)" -ForegroundColor Red
        return $null
    }
}

Write-Host " Fonctions PowerShell optimisées chargées" -ForegroundColor Cyan
Write-Host "Utilisation:" -ForegroundColor Yellow
Write-Host "  New-PHPFile 'fichier.php' 'contenu'" -ForegroundColor White
Write-Host "  Read-PHPFile 'fichier.php'" -ForegroundColor White
Write-Host "  Edit-PHPFile 'fichier.php' 'ancien' 'nouveau'" -ForegroundColor White
Write-Host "  Invoke-PHPScript 'script.php'" -ForegroundColor White
