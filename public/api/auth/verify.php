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

error_log("verify.php - Vérification de session demandée");

// Vérifier si la session existe
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    error_log("verify.php - Utilisateur non connecté");
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Non authentifié'
    ]);
    exit;
}

// Vérifier si le token existe
if (!isset($_SESSION['token'])) {
    error_log("verify.php - Token manquant en session");
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Token manquant'
    ]);
    exit;
}

// Vérifier l'expiration du token JWT
$token = $_SESSION['token'];

error_log("verify.php - Token présent, vérification de l'expiration...");

try {
    $tokenParts = explode('.', $token);
    if (count($tokenParts) !== 3) {
        error_log("verify.php - Token mal formé (pas 3 parties)");
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Token invalide'
        ]);
        exit;
    }
    
    $payload = json_decode(base64_decode($tokenParts[1]), true);
    
    if (!$payload || !isset($payload['exp'])) {
        error_log("verify.php - Token sans payload exp");
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Token invalide'
        ]);
        exit;
    }
    
    $now = time();
    $exp = $payload['exp'];
    $timeLeft = $exp - $now;
    
    error_log("verify.php - Token exp: $exp, now: $now, reste: $timeLeft secondes");
    
    // Vérifier si le token est expiré
    if ($now >= $exp) {
        // Token expiré, nettoyer la session
        error_log("verify.php - Token EXPIRÉ, nettoyage de la session");
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
    error_log("verify.php - Token VALIDE, expire dans $timeLeft secondes");
    echo json_encode([
        'success' => true,
        'message' => 'Token valide',
        'expires_in' => $timeLeft
    ]);
    
} catch (Exception $e) {
    error_log("Erreur vérification token: " . $e->getMessage());
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la vérification du token'
    ]);
}
