<?php
require_once "app/Services/ApiService.php";
$api = new ApiService();
$result = $api->getGroups();
echo "Résultat API groupes:\n";
print_r($result);
?>
