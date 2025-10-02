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
// Démarrage de la session
session_start();
// Inclusion du routeur principal
require_once 'app/Config/Router.php';
// Initialisation et exécution du routeur
//$router = new Router("/webapp");
$router = new Router("");

$router->run();
