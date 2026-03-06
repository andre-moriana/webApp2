<?php

$code = $_GET['code'] ?? 'inconnu';

echo "<h2>Demande de suppression reçue</h2>";
echo "<p>Code de confirmation : <strong>$code</strong></p>";
echo "<p>Vos données seront supprimées sous 48 heures.</p>";

?>