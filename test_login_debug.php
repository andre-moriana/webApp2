<?php
// Test pour sauvegarder la sortie exacte
session_start();

// Simuler une requête GET vers /login
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/login';

// Charger le routeur
require_once 'app/Config/Router.php';

// Capturer la sortie
ob_start();
$router = new Router();
$router->run();
$output = ob_get_clean();

// Sauvegarder dans un fichier
file_put_contents('login_debug.html', $output);

echo "Sortie sauvegardée dans login_debug.html\n";
echo "Taille: " . strlen($output) . " caractères\n";

// Chercher la navbar
$navbarStart = strpos($output, '<nav class="navbar');
if ($navbarStart !== false) {
    echo " Navbar trouvée à la position: " . $navbarStart . "\n";
} else {
    echo " Aucune navbar trouvée\n";
}

// Chercher le footer
$footerStart = strpos($output, '<footer class="bg-dark');
if ($footerStart !== false) {
    echo " Footer trouvé à la position: " . $footerStart . "\n";
} else {
    echo " Aucun footer trouvé\n";
}

echo "\n=== FIN TEST ===\n";
?>
