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

// Vérifier le token JWT et le rafraîchir si nécessaire
$tokenNeedsRefresh = false;
$tokenData = null;

if (isset($_SESSION['token'])) {
    $token = $_SESSION['token'];
    $tokenParts = explode('.', $token);
    
    if (count($tokenParts) === 3) {
        try {
            $payload = json_decode(base64_decode($tokenParts[1]), true);
            
            if ($payload && isset($payload['exp'])) {
                $now = time();
                $exp = $payload['exp'];
                $timeLeft = $exp - $now;
                
                // Si le token expire dans moins de 30 minutes, le rafraîchir
                if ($timeLeft < 1800) { // 1800 secondes = 30 minutes
                    $tokenNeedsRefresh = true;
                    error_log("keep-alive.php: Token expire dans {$timeLeft}s, rafraîchissement nécessaire");
                }
                
                $tokenData = [
                    'expires_in' => $timeLeft,
                    'expires_at' => date('Y-m-d H:i:s', $exp)
                ];
            }
        } catch (Exception $e) {
            error_log("keep-alive.php: Erreur décodage token: " . $e->getMessage());
        }
    }
}

// Si le token doit être rafraîchi, appeler l'API backend
if ($tokenNeedsRefresh && isset($_SESSION['token'])) {
    require_once __DIR__ . '/../app/Services/ApiService.php';
    
    try {
        $apiService = new ApiService();
        
        // Essayer de rafraîchir le token via l'API backend
        $refreshResult = $apiService->makeRequest('auth/refresh', 'POST', [
            'token' => $_SESSION['token']
        ]);
        
        if ($refreshResult['success'] && isset($refreshResult['data']['token'])) {
            // Token rafraîchi avec succès
            $_SESSION['token'] = $refreshResult['data']['token'];
            
            // Décoder le nouveau token pour obtenir les infos
            $newTokenParts = explode('.', $_SESSION['token']);
            if (count($newTokenParts) === 3) {
                $newPayload = json_decode(base64_decode($newTokenParts[1]), true);
                $tokenData = [
                    'expires_in' => $newPayload['exp'] - time(),
                    'expires_at' => date('Y-m-d H:i:s', $newPayload['exp']),
                    'refreshed' => true
                ];
            }
            
            error_log("keep-alive.php: Token rafraîchi avec succès, nouveau exp: " . $tokenData['expires_at']);
        } else {
            error_log("keep-alive.php: Échec du rafraîchissement du token");
        }
    } catch (Exception $e) {
        error_log("keep-alive.php: Erreur lors du rafraîchissement: " . $e->getMessage());
    }
}

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
    'last_activity' => $_SESSION['last_activity'],
    'token' => $tokenData
]);
