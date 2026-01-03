<?php
/**
 * Endpoint pour vérifier la validité du token JWT
 * Utilisé pour vérifier l'état de la session après réouverture du navigateur
 */

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Headers JSON
header('Content-Type: application/json');

// Vérifier si la session existe
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Non authentifié'
    ]);
    exit;
}

// Vérifier si le token existe
if (!isset($_SESSION['token'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Token manquant'
    ]);
    exit;
}

// Vérifier l'expiration du token JWT
$token = $_SESSION['token'];

try {
    $tokenParts = explode('.', $token);
    if (count($tokenParts) !== 3) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Token invalide'
        ]);
        exit;
    }
    
    $payload = json_decode(base64_decode($tokenParts[1]), true);
    
    if (!$payload || !isset($payload['exp'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Token invalide'
        ]);
        exit;
    }
    
    // Vérifier si le token est expiré
    if (time() >= $payload['exp']) {
        // Token expiré, nettoyer la session
        session_unset();
        session_destroy();
        
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Token expiré'
        ]);
        exit;
    }
    
    // Token valide
    echo json_encode([
        'success' => true,
        'message' => 'Token valide',
        'expires_in' => $payload['exp'] - time()
    ]);
    
} catch (Exception $e) {
    error_log("Erreur vérification token: " . $e->getMessage());
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la vérification du token'
    ]);
}
