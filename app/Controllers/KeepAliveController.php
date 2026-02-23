<?php
/**
 * Contrôleur pour maintenir la session active (keep-alive)
 * Appelé périodiquement depuis les pages de saisie longue (feuille de marque, etc.)
 */
class KeepAliveController {

    public function ping() {
        header('Content-Type: application/json; charset=utf-8');

        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Session expirée',
                    'redirect' => '/login?expired=1',
                    'session_expired' => true
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $maxInactivity = 8 * 60 * 60;
            if (isset($_SESSION['last_activity'])) {
                $elapsed = time() - $_SESSION['last_activity'];
                if ($elapsed > $maxInactivity) {
                    session_unset();
                    session_destroy();
                    http_response_code(401);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Session expirée par inactivité',
                        'redirect' => '/login?expired=1',
                        'session_expired' => true
                    ], JSON_UNESCAPED_UNICODE);
                    return;
                }
            }

            $_SESSION['last_activity'] = time();

            $tokenData = null;
            if (isset($_SESSION['token'])) {
                $token = $_SESSION['token'];
                $tokenParts = explode('.', $token);
                if (count($tokenParts) === 3) {
                    $payload = json_decode(base64_decode(strtr($tokenParts[1], '-_', '+/')), true);
                    if ($payload && isset($payload['exp'])) {
                        $timeLeft = $payload['exp'] - time();
                        if ($timeLeft < 1800) {
                            $this->refreshToken();
                        }
                        $tokenData = [
                            'expires_in' => $timeLeft,
                            'expires_at' => date('Y-m-d H:i:s', $payload['exp'])
                        ];
                    }
                }
            }

            $user = $_SESSION['user'] ?? [];
            echo json_encode([
                'success' => true,
                'message' => 'Session maintenue active',
                'user' => [
                    'id' => $user['id'] ?? null,
                    'name' => $user['name'] ?? null,
                    'username' => $user['username'] ?? null
                ],
                'last_activity' => $_SESSION['last_activity'],
                'token' => $tokenData
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log('KeepAliveController: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur serveur'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    private function refreshToken() {
        if (!isset($_SESSION['token'])) return;
        try {
            require_once __DIR__ . '/../Services/ApiService.php';
            $apiService = new ApiService();
            $refreshResult = $apiService->makeRequest('auth/refresh', 'POST', [
                'token' => $_SESSION['token']
            ]);
            if ($refreshResult && ($refreshResult['success'] ?? false) && isset($refreshResult['data']['token'])) {
                $_SESSION['token'] = $refreshResult['data']['token'];
            }
        } catch (Throwable $e) {
            error_log('KeepAliveController refresh: ' . $e->getMessage());
        }
    }
}
