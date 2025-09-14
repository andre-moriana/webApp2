$content = Get-Content app/Views/users/show.php
$content = $content -replace "role.*admin.*isAdmin", "role.*Coach.*primary"
$content = $content -replace "Statut.*isBanned", "Administrateur.*is_admin.*isAdmin"
$content | Set-Content app/Views/users/show.php
