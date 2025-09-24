Write-Host "=== Démarrage du serveur de test ===" -ForegroundColor Green

# Démarrer le serveur PHP intégré
Write-Host "Démarrage du serveur PHP sur http://localhost:8000" -ForegroundColor Yellow
Write-Host "Fichiers de test disponibles:" -ForegroundColor Cyan
Write-Host "- http://localhost:8000/test_buttons.html (Test HTML pur)" -ForegroundColor White
Write-Host "- http://localhost:8000/test_chat_php.php (Test PHP avec session)" -ForegroundColor White
Write-Host "- http://localhost:8000/test_chat.html (Test complet)" -ForegroundColor White
Write-Host ""
Write-Host "Appuyez sur Ctrl+C pour arrêter le serveur" -ForegroundColor Red
Write-Host ""

# Démarrer le serveur
php -S localhost:8000
