<?php
// Test simple de la page de login
session_start();

echo "=== TEST SIMPLE PAGE LOGIN ===\n";

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

// Vérifier les éléments
$hasNavbar = strpos($output, '<nav class="navbar') !== false;
$hasFooter = strpos($output, '<footer class="bg-dark') !== false;
$hasLoginForm = strpos($output, 'login-form') !== false;

echo "Résultats:\n";
echo "- Navbar: " . ($hasNavbar ? 'PRÉSENTE' : 'ABSENTE') . "\n";
echo "- Footer: " . ($hasFooter ? 'PRÉSENT' : 'ABSENT') . "\n";
echo "- Formulaire login: " . ($hasLoginForm ? 'PRÉSENT' : 'ABSENT') . "\n";

if ($hasNavbar || $hasFooter) {
    echo "\n PROBLÈME: La page contient des éléments indésirables\n";
    echo "La page de login ne devrait contenir que le formulaire de connexion\n";
} else {
    echo "\n SUCCÈS: La page de login est propre\n";
}

echo "\n=== FIN TEST ===\n";
?>
