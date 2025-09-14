$content = Get-Content app/Controllers/UserController.php
$content = $content -replace "is_admin = \$_POST\[.is_admin.\] ?? .active.;", "is_admin = \$_POST[.is_admin.] ?? .0.; is_banned = \$_POST[.is_banned.] ?? .0.;"
$content | Set-Content app/Controllers/UserController.php
