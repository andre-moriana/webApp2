$content = Get-Content app/Services/ApiService.php
$content = $content -replace "role = \$userData\[.role.\] ?? .", "role = \$userData[.role.] ?? ., is_admin = \$userData[.is_admin.] ?? .0., is_banned = \$userData[.is_banned.] ?? .0."
$content | Set-Content app/Services/ApiService.php
