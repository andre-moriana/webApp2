<?php
/**
 * Middleware pour vérifier la session sur les pages protégées
 * À inclure au début de chaque contrôleur qui nécessite une authentification
 */

class SessionGuard {
    /**
     * Vérifie si la session est valide et redirige vers login si nécessaire
     * @return bool True si la session est valide, False sinon
     */
    public static function check() {
        // Démarrer la session si ce n'est pas déjà fait
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            // Détruire la session invalide
            session_unset();
            session_destroy();
            
            // Rediriger vers la page de login
            header('Location: /login?expired=1');
            exit;
        }
        
        // Vérifier si la session n'est pas trop ancienne (8 heures max)
        if (isset($_SESSION['last_activity'])) {
            $maxInactivity = 8 * 60 * 60; // 8 heures en secondes
            $elapsed = time() - $_SESSION['last_activity'];
            
            if ($elapsed > $maxInactivity) {
                // Session expirée par inactivité
                session_unset();
                session_destroy();
                
                header('Location: /login?expired=1');
                exit;
            }
        }
        
        // Mettre à jour le timestamp de dernière activité
        $_SESSION['last_activity'] = time();
        
        // Vérifier que l'utilisateur existe toujours et est actif
        if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
            session_unset();
            session_destroy();
            
            header('Location: /login?expired=1');
            exit;
        }
        
        // VÉRIFICATION CRITIQUE: Vérifier le token JWT
        if (!isset($_SESSION['token']) || empty($_SESSION['token'])) {
            error_log("SessionGuard: Token manquant, redirection vers login");
            session_unset();
            session_destroy();
            
            header('Location: /login?expired=1');
            exit;
        }
        
        // Vérifier si le token JWT est expiré
        $token = $_SESSION['token'];
        try {
            $tokenParts = explode('.', $token);
            if (count($tokenParts) === 3) {
                $payload = json_decode(base64_decode($tokenParts[1]), true);
                
                if ($payload && isset($payload['exp'])) {
                    // Vérifier si le token est expiré
                    if (time() >= $payload['exp']) {
                        error_log("SessionGuard: Token JWT expiré (exp: " . $payload['exp'] . ", now: " . time() . "), redirection vers login");
                        session_unset();
                        session_destroy();
                        
                        header('Location: /login?expired=1');
                        exit;
                    }
                } else {
                    error_log("SessionGuard: Token JWT invalide (pas de payload exp), redirection vers login");
                    session_unset();
                    session_destroy();
                    
                    header('Location: /login?expired=1');
                    exit;
                }
            } else {
                error_log("SessionGuard: Token JWT mal formé, redirection vers login");
                session_unset();
                session_destroy();
                
                header('Location: /login?expired=1');
                exit;
            }
        } catch (Exception $e) {
            error_log("SessionGuard: Erreur lors de la vérification du token: " . $e->getMessage());
            session_unset();
            session_destroy();
            
            header('Location: /login?expired=1');
            exit;
        }
        
        return true;
    }
    
    /**
     * Vérifie la session et retourne une réponse JSON (pour les requêtes AJAX)
     */
    public static function checkAjax() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Session expirée',
                'session_expired' => true
            ]);
            exit;
        }
        
        // Vérifier l'inactivité
        if (isset($_SESSION['last_activity'])) {
            $maxInactivity = 8 * 60 * 60;
            $elapsed = time() - $_SESSION['last_activity'];
            
            if ($elapsed > $maxInactivity) {
                session_unset();
                session_destroy();
                
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Session expirée par inactivité',
                    'session_expired' => true
                ]);
                exit;
            }
        }
        
        $_SESSION['last_activity'] = time();
        
        return true;
    }
}
