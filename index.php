<?php
/**
 * Portail Web - Archers de Gémenos
 * Point d'entrée principal de l'application
 */
// Chargement de l'autoloader personnalisé
require_once 'app/Config/Autoloader.php';
// Chargement des variables d'environnement
if (file_exists('.env')) {
    $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}
// Configuration des erreurs
error_reporting(E_ALL);
ini_set('display_errors', $_ENV['APP_DEBUG'] ?? '1');

// Configuration de la session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');

// Démarrage de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_log("[index.php] Session démarrée - ID: " . session_id());
error_log("[index.php] Session logged_in: " . (isset($_SESSION['logged_in']) ? ($_SESSION['logged_in'] ? 'true' : 'false') : 'not set'));

// Inclusion du routeur principal
require_once 'app/Config/Router.php';
// Initialisation et exécution du routeur
//$router = new Router("/webapp");
$router = new Router("");
$router->run();