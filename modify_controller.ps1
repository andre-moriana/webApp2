$content = Get-Content app/Controllers/UserController.php
$content = $content -replace "status", "is_admin"
$content | Set-Content app/Controllers/UserController.php
