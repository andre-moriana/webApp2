$content = Get-Content app/Views/users/edit.php
$content = $content -replace "status", "is_admin"
$content | Set-Content app/Views/users/edit.php
