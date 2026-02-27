<?php

require_once 'app/Services/ApiService.php';
require_once 'app/Middleware/SessionGuard.php';

/**
 * Fil Facebook du club : récupération des posts via API Graph.
 * Une fois la page connectée (bouton "Connecter la page Facebook"), les posts s'affichent sur le site.
 */
class ClubFeedController
{
    private $apiService;

    public function __construct()
    {
        $this->apiService = new ApiService();
    }

    /**
     * Page « Actualités du club » : affiche le fil Facebook (ou le bouton pour connecter la page, ou un message d'erreur).
     */
    public function index()
    {
        SessionGuard::check();

        $user = $_SESSION['user'] ?? [];
        $clubId = $user['clubId'] ?? $user['club_id'] ?? null;
        $club = null;
        $facebookUrl = '';
        $clubName = 'votre club';
        $posts = [];
        $facebookConnected = false;
        $feedError = '';
        $fbHref = '';
        $facebookDisabled = false;

        if (!$clubId) {
            try {
                $listResponse = $this->apiService->makeRequest('clubs/list', 'GET');
                $list = $this->apiService->unwrapData($listResponse);
                if (!empty($listResponse['success']) && is_array($list) && count($list) > 0) {
                    $first = $list[0];
                    $clubId = $first['id'] ?? $first['_id'] ?? $first['nameShort'] ?? $first['name_short'] ?? null;
                }
            } catch (Exception $e) {
                error_log('ClubFeedController list: ' . $e->getMessage());
            }
        }

        if ($clubId) {
            try {
                $response = $this->apiService->makeRequest("clubs/{$clubId}", 'GET');
                $payload = $this->apiService->unwrapData($response);
                if ($response['success'] && $payload && is_array($payload)) {
                    $club = $payload;
                    $clubName = $club['name'] ?? 'Club';
                    $facebookUrl = $club['facebookUrl'] ?? $club['facebook_url'] ?? '';
                    $facebookUrl = is_string($facebookUrl) ? trim($facebookUrl) : '';
                    $facebookConnected = !empty($club['facebookConnected']);
                }
                $feedResponse = $this->apiService->makeRequest("clubs/{$clubId}/facebook-feed", 'GET');
                if (!empty($feedResponse['success']) && isset($feedResponse['data'])) {
                    $posts = $feedResponse['data']['posts'] ?? [];
                    $facebookConnected = !empty($feedResponse['data']['connected']);
                    $feedError = $feedResponse['data']['feedError'] ?? '';
                }
            } catch (Exception $e) {
                error_log('ClubFeedController: ' . $e->getMessage());
            }
        }

        if ($facebookUrl !== '') {
            $fbHref = (strpos($facebookUrl, 'http') === 0)
                ? $facebookUrl
                : 'https://www.facebook.com/' . ltrim($facebookUrl, '/');
        }

        $title = 'Actualités du club - Portail Arc Training';
        $pageTitle = $title;
        $dashboardFullPage = false;

        include 'app/Views/layouts/header.php';
        include 'app/Views/club-feed/index.php';
        include 'app/Views/layouts/footer.php';
    }

    /**
     * Redirige vers Facebook OAuth pour connecter la page du club.
     */
    public function connect()
    {
        SessionGuard::check();
        $user = $_SESSION['user'] ?? [];
        $clubId = $user['clubId'] ?? $user['club_id'] ?? null;
        if (!$clubId) {
            header('Location: /club-feed');
            exit;
        }
        $this->loadEnv();
        $appId = $_ENV['FACEBOOK_APP_ID'] ?? '';
        if ($appId === '') {
            $_SESSION['club_feed_error'] = 'Application Facebook non configurée (FACEBOOK_APP_ID manquant).';
            header('Location: /club-feed');
            exit;
        }
        $redirectUri = $this->getBaseUrl() . '/club-feed/facebook-callback';
        $state = base64_encode(json_encode(['clubId' => $clubId]));
        $url = 'https://www.facebook.com/v18.0/dialog/oauth?client_id=' . urlencode($appId)
            . '&redirect_uri=' . urlencode($redirectUri)
            . '&scope=' . urlencode('pages_show_list,pages_read_engagement')
            . '&state=' . urlencode($state)
            . '&auth_type=rerequest';
        header('Location: ' . $url);
        exit;
    }

    /**
     * Déconnecte la page Facebook du club (efface le token) pour permettre une reconnexion propre.
     */
    public function disconnect()
    {
        SessionGuard::check();
        $user = $_SESSION['user'] ?? [];
        $clubId = $user['clubId'] ?? $user['club_id'] ?? null;
        if (!$clubId) {
            try {
                $listResponse = $this->apiService->makeRequest('clubs/list', 'GET');
                $list = $this->apiService->unwrapData($listResponse);
                if (!empty($listResponse['success']) && is_array($list) && count($list) > 0) {
                    $first = $list[0];
                    $clubId = $first['id'] ?? $first['_id'] ?? $first['nameShort'] ?? $first['name_short'] ?? null;
                }
            } catch (Exception $e) {
                // ignore
            }
        }
        if ($clubId) {
            try {
                $response = $this->apiService->makeRequest("clubs/{$clubId}/facebook-disconnect", 'POST');
                $ok = !empty($response['success']) || (isset($response['data']['success']) && !empty($response['data']['success']));
                if (!$ok) {
                    $data = $response['data'] ?? [];
                    $msg = is_array($data) ? ($data['error'] ?? $response['message'] ?? '') : ($response['message'] ?? '');
                    $_SESSION['club_feed_error'] = $msg ?: 'Impossible de déconnecter la page.';
                }
            } catch (Exception $e) {
                error_log('ClubFeedController disconnect: ' . $e->getMessage());
                $_SESSION['club_feed_error'] = 'Erreur lors de la déconnexion.';
            }
        }
        if (!isset($_SESSION['club_feed_error'])) {
            $_SESSION['club_feed_success'] = 'Page Facebook déconnectée. Vérifiez les paramètres de l\'app Facebook puis recliquez sur « Connecter la page Facebook ».';
        }
        header('Location: /club-feed');
        exit;
    }

    /**
     * Callback Facebook OAuth : récupère le token de page et l'enregistre pour le club.
     */
    public function facebookCallback()
    {
        SessionGuard::check();
        $this->loadEnv();
        $appId = $_ENV['FACEBOOK_APP_ID'] ?? '';
        $appSecret = $_ENV['FACEBOOK_APP_SECRET'] ?? '';
        if ($appId === '' || $appSecret === '') {
            $_SESSION['club_feed_error'] = 'Application Facebook non configurée.';
            header('Location: /club-feed');
            exit;
        }
        $code = $_GET['code'] ?? '';
        $state = $_GET['state'] ?? '';
        if ($code === '' || $state === '') {
            $_SESSION['club_feed_error'] = 'Connexion Facebook annulée ou invalide.';
            header('Location: /club-feed');
            exit;
        }
        $decoded = @json_decode(base64_decode($state), true);
        $clubId = $decoded['clubId'] ?? null;
        if (!$clubId) {
            $_SESSION['club_feed_error'] = 'Session invalide.';
            header('Location: /club-feed');
            exit;
        }
        $redirectUri = $this->getBaseUrl() . '/club-feed/facebook-callback';
        $tokenUrl = 'https://graph.facebook.com/v18.0/oauth/access_token?client_id=' . urlencode($appId)
            . '&redirect_uri=' . urlencode($redirectUri)
            . '&client_secret=' . urlencode($appSecret)
            . '&code=' . urlencode($code);
        $tokenJson = @file_get_contents($tokenUrl);
        $tokenData = $tokenJson ? json_decode($tokenJson, true) : null;
        $userToken = $tokenData['access_token'] ?? null;
        if (!$userToken) {
            $_SESSION['club_feed_error'] = 'Impossible d\'obtenir l\'accès Facebook.';
            header('Location: /club-feed');
            exit;
        }
        // Échanger le token court contre un token long (60 j) pour que les permissions soient bien prises en compte
        $exchangeUrl = 'https://graph.facebook.com/v18.0/oauth/access_token?grant_type=fb_exchange_token'
            . '&client_id=' . urlencode($appId)
            . '&client_secret=' . urlencode($appSecret)
            . '&fb_exchange_token=' . urlencode($userToken);
        $exchangeJson = @file_get_contents($exchangeUrl);
        $exchangeData = $exchangeJson ? json_decode($exchangeJson, true) : null;
        if (!empty($exchangeData['access_token'])) {
            $userToken = $exchangeData['access_token'];
        }
        $accountsUrl = 'https://graph.facebook.com/v18.0/me/accounts?fields=id,name,access_token&access_token=' . urlencode($userToken);
        $accountsJson = @file_get_contents($accountsUrl);
        $accountsData = $accountsJson ? json_decode($accountsJson, true) : null;
        $pages = $accountsData['data'] ?? [];
        if (empty($pages)) {
            $_SESSION['club_feed_error'] = 'Aucune page Facebook gérée par ce compte.';
            header('Location: /club-feed');
            exit;
        }
        $clubResponse = $this->apiService->makeRequest("clubs/{$clubId}", 'GET');
        $club = $this->apiService->unwrapData($clubResponse);
        $facebookUrl = $club['facebookUrl'] ?? $club['facebook_url'] ?? '';
        $facebookUrl = is_string($facebookUrl) ? trim($facebookUrl) : '';
        $normalize = function ($u) {
            $u = trim($u);
            if ($u === '') return '';
            if (strpos($u, 'http') !== 0) $u = 'https://www.facebook.com/' . ltrim($u, '/');
            return rtrim(strtolower($u), '/');
        };
        $targetNorm = $normalize($facebookUrl);
        $selected = null;
        foreach ($pages as $p) {
            $pageUrl = 'https://www.facebook.com/' . ($p['id'] ?? '');
            if ($targetNorm !== '' && $normalize($pageUrl) === $targetNorm) {
                $selected = $p;
                break;
            }
        }
        if (!$selected) {
            $selected = $pages[0];
        }
        $pageId = $selected['id'] ?? '';
        $pageToken = $selected['access_token'] ?? '';
        if ($pageId === '' || $pageToken === '') {
            $_SESSION['club_feed_error'] = 'Token de page invalide.';
            header('Location: /club-feed');
            exit;
        }
        // Vérifier si le token a la permission pages_read_engagement (évite le #10 sur le fil)
        $appToken = $appId . '|' . $appSecret;
        $debugUrl = 'https://graph.facebook.com/v18.0/debug_token?input_token=' . urlencode($pageToken) . '&access_token=' . urlencode($appToken);
        $debugJson = @file_get_contents($debugUrl);
        $debug = $debugJson ? json_decode($debugJson, true) : null;
        $grantedScopes = $debug['data']['scopes'] ?? $debug['data']['granted_scopes'] ?? [];
        if (is_array($grantedScopes) && count($grantedScopes) > 0 && !in_array('pages_read_engagement', $grantedScopes, true)) {
            $_SESSION['club_feed_error'] = 'Facebook n\'a pas accordé la permission « pages_read_engagement » (reçu : ' . implode(', ', $grantedScopes) . '). '
                . 'Sur developers.facebook.com → votre app → Cas d\'usage → ajoutez « Gérer tout sur votre Page » puis la permission optionnelle pages_read_engagement. '
                . 'Puis révoquez l\'app (Paramètres Facebook → Applications) et recliquez sur « Connecter la page Facebook ».';
            header('Location: /club-feed');
            exit;
        }
        try {
            $putResponse = $this->apiService->makeRequest("clubs/{$clubId}", 'PUT', [
                'facebookPageId' => $pageId,
                'facebookPageToken' => $pageToken,
            ]);
            if (!empty($putResponse['success'])) {
                $_SESSION['club_feed_success'] = 'Page Facebook connectée. Le fil s\'affichera ci-dessous.';
            } else {
                $data = $putResponse['data'] ?? [];
                $msg = is_array($data) ? ($data['error'] ?? $putResponse['message'] ?? 'Erreur lors de l\'enregistrement.') : 'Erreur lors de l\'enregistrement.';
                $_SESSION['club_feed_error'] = $msg;
            }
        } catch (Exception $e) {
            error_log('ClubFeedController facebookCallback: ' . $e->getMessage());
            $_SESSION['club_feed_error'] = 'Erreur serveur : ' . $e->getMessage();
        }
        header('Location: /club-feed');
        exit;
    }

    private function loadEnv(): void
    {
        if (!empty($_ENV['FACEBOOK_APP_ID'])) return;
        $envPath = __DIR__ . '/../../.env';
        if (!is_file($envPath)) return;
        $lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos(trim($line), '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value);
            }
        }
    }

    private function getBaseUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host;
    }
}
