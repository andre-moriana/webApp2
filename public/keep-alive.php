<?php
/**
 * Endpoint pour maintenir la session active
 * Appelé périodiquement depuis les pages de saisie longue
 */

// Inclure le middleware de session
require_once __DIR__ . '/../app/Middleware/SessionGuard.php';

// Démarrer la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Session expirée',
        'redirect' => '/login?expired=1',
        'session_expired' => true
    ]);
    exit;
}

// Vérifier l'inactivité (8 heures max)
if (isset($_SESSION['last_activity'])) {
    $maxInactivity = 8 * 60 * 60; // 8 heures
    $elapsed = time() - $_SESSION['last_activity'];
    
    if ($elapsed > $maxInactivity) {
        // Session expirée
        session_unset();
        session_destroy();
        
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Session expirée par inactivité',
            'redirect' => '/login?expired=1',
            'session_expired' => true
        ]);
        exit;
    }
}

// Mettre à jour le timestamp d'activité
$_SESSION['last_activity'] = time();

// Répondre avec succès pour prolonger la session
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Session maintenue active',
    'user' => [
        'id' => $_SESSION['user']['id'] ?? null,
        'name' => $_SESSION['user']['name'] ?? null,
        'username' => $_SESSION['user']['username'] ?? null
    ],
    'last_activity' => $_SESSION['last_activity']
]);
