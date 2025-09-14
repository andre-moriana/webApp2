# GUIDE D'UTILISATION OPTIMISÉ
# =============================

## 1. CHARGER LA CONFIGURATION
# Dans PowerShell, exécuter :
. .\profile_dev.ps1

## 2. COMMANDES RAPIDES
# Créer un fichier PHP :
Quick-PHP "mon_fichier.php" "<?php echo 'Hello World'; ?>"

# Lire un fichier :
rphp "app/Controllers/UserController.php"

# Modifier un fichier :
ephp "fichier.php" "ancien texte" "nouveau texte"

# Exécuter un script :
exphp "test.php"

# Tester l'environnement :
Test-Environment

## 3. MÉTHODES ALTERNATIVES
# Utiliser l'éditeur de code (VS Code/Cursor) pour :
# - Créer des fichiers
# - Modifier du code
# - Naviguer dans le projet

# Utiliser des commandes PowerShell simples :
# - echo "contenu" > fichier.php
# - Get-Content fichier.php
# - php script.php

## 4. DÉPANNAGE
# Si les scripts ne s'exécutent pas :
# - Vérifier que PHP est dans le PATH
# - Utiliser le chemin complet : C:\wamp64\bin\php\php8.3.14\php.exe
# - Vérifier les permissions d'écriture

## 5. CONFIGURATION RECOMMANDÉE
# - Utiliser VS Code/Cursor comme éditeur principal
# - Utiliser PowerShell pour les tâches simples
# - Tester les modifications avec des scripts simples
# - Sauvegarder régulièrement le travail
