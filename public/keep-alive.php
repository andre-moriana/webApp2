<?php
/**
 * Endpoint pour maintenir la session active
 * Appelé périodiquement depuis les pages de saisie longue
 */

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
        'redirect' => '/login'
    ]);
    exit;
}

// Répondre avec succès pour prolonger la session
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Session maintenue active',
    'user' => [
        'id' => $_SESSION['user']['id'] ?? null,
        'name' => $_SESSION['user']['name'] ?? null
    ]
]);
