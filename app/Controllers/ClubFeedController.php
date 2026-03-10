<?php

require_once 'app/Services/ApiService.php';
require_once 'app/Services/FacebookFeedService.php';
require_once 'app/Middleware/SessionGuard.php';

/**
 * Page « Actualités du club » : affiche les posts Facebook du club (API ou lien).
 * Connexion OAuth Facebook pour enregistrer facebook_page_id et facebook_page_token en base.
 */
class ClubFeedController
{
    private $apiService;
    private const FB_GRAPH_VERSION = 'v18.0';

    public function __construct()
    {
        $this->apiService = new ApiService();
    }

    /**
     * Redirige vers Facebook pour demander les autorisations (pages_show_list, pages_read_engagement).
     * Paramètre GET : club_id (obligatoire).
     */
    public function facebookConnect()
    {
        SessionGuard::check();
        $clubId = $_GET['club_id'] ?? '';
        if ($clubId === '') {
            $_SESSION['club_feed_error'] = 'Paramètre club manquant.';
            header('Location: /club-feed');
            exit;
        }
        $this->loadFacebookEnv();
        if (empty($_ENV['FACEBOOK_APP_ID'])) {
            $_SESSION['club_feed_error'] = 'FACEBOOK_APP_ID manquant dans .env.';
            header('Location: /club-feed');
            exit;
        }
        $redirectUri = $this->getBaseUrl() . '/club-feed/facebook-callback';
        $scope = 'pages_show_list,pages_read_engagement';
        $state = $clubId;
        $url = 'https://www.facebook.com/' . self::FB_GRAPH_VERSION . '/dialog/oauth'
            . '?client_id=' . urlencode($_ENV['FACEBOOK_APP_ID'])
            . '&redirect_uri=' . urlencode($redirectUri)
            . '&scope=' . urlencode($scope)
            . '&state=' . urlencode($state);
        header('Location: ' . $url);
        exit;
    }

    /**
     * Callback Facebook : échange le code contre un token, récupère /me/accounts, enregistre la page en base.
     */
    public function facebookCallback()
    {
        SessionGuard::check();
        $code = $_GET['code'] ?? '';
        $state = $_GET['state'] ?? '';
        if ($code === '' || $state === '') {
            $_SESSION['club_feed_error'] = 'Autorisation Facebook annulée ou incomplète.';
            header('Location: /club-feed');
            exit;
        }
        $clubId = $state;
        $this->loadFacebookEnv();
        if (empty($_ENV['FACEBOOK_APP_ID']) || empty($_ENV['FACEBOOK_APP_SECRET'])) {
            $_SESSION['club_feed_error'] = 'Configuration Facebook manquante (.env).';
            header('Location: /club-feed');
            exit;
        }
        $redirectUri = $this->getBaseUrl() . '/club-feed/facebook-callback';
        $tokenUrl = 'https://graph.facebook.com/' . self::FB_GRAPH_VERSION . '/oauth/access_token'
            . '?client_id=' . urlencode($_ENV['FACEBOOK_APP_ID'])
            . '&client_secret=' . urlencode($_ENV['FACEBOOK_APP_SECRET'])
            . '&redirect_uri=' . urlencode($redirectUri)
            . '&code=' . urlencode($code);
        $json = @file_get_contents($tokenUrl);
        if ($json === false) {
            $_SESSION['club_feed_error'] = 'Impossible d\'obtenir le token Facebook.';
            header('Location: /club-feed');
            exit;
        }
        $data = json_decode($json, true);
        $userToken = $data['access_token'] ?? null;
        if (empty($userToken)) {
            $msg = $data['error']['message'] ?? 'Réponse Facebook invalide.';
            $_SESSION['club_feed_error'] = 'Facebook: ' . $msg;
            header('Location: /club-feed');
            exit;
        }
        $accountsUrl = 'https://graph.facebook.com/' . self::FB_GRAPH_VERSION . '/me/accounts'
            . '?fields=id,name,access_token'
            . '&access_token=' . urlencode($userToken);
        $accountsJson = @file_get_contents($accountsUrl);
        if ($accountsJson === false) {
            $_SESSION['club_feed_error'] = 'Impossible de récupérer les pages Facebook.';
            header('Location: /club-feed');
            exit;
        }
        $accountsData = json_decode($accountsJson, true);
        $pages = $accountsData['data'] ?? [];
        if (empty($pages)) {
            $_SESSION['club_feed_error'] = 'Aucune page Facebook gérée par ce compte.';
            header('Location: /club-feed');
            exit;
        }
        $page = $pages[0];
        $pageId = $page['id'] ?? '';
        $pageToken = $page['access_token'] ?? '';
        if ($pageId === '' || $pageToken === '') {
            $_SESSION['club_feed_error'] = 'Token de page invalide.';
            header('Location: /club-feed');
            exit;
        }
        $updatePayload = [
            'facebookPageId' => $pageId,
            'facebookPageToken' => $pageToken,
        ];
        $response = $this->apiService->makeRequest("clubs/{$clubId}", 'PUT', $updatePayload);
        if (empty($response['success']) && (isset($response['message']) || isset($response['error']))) {
            $_SESSION['club_feed_error'] = $response['message'] ?? $response['error'] ?? 'Erreur lors de l\'enregistrement.';
            header('Location: /club-feed');
            exit;
        }
        $_SESSION['club_feed_success'] = 'Page Facebook connectée. Les actualités seront affichées.';
        header('Location: /club-feed');
        exit;
    }

    /**
     * Déconnecte la page Facebook du club (efface token et page_id en base).
     * GET ?club_id=xxx
     */
    public function facebookDisconnect()
    {
        SessionGuard::check();
        $clubId = $_GET['club_id'] ?? '';
        if ($clubId === '') {
            $_SESSION['club_feed_error'] = 'Paramètre club manquant.';
            header('Location: /club-feed');
            exit;
        }
        $response = $this->apiService->makeRequest("clubs/{$clubId}/facebook-disconnect", 'POST');
        if (!empty($response['success']) || (isset($response['data']['success']) && $response['data']['success'])) {
            $_SESSION['club_feed_success'] = 'Page Facebook déconnectée.';
        } else {
            $_SESSION['club_feed_error'] = $response['message'] ?? $response['data']['error'] ?? $response['error'] ?? 'Erreur lors de la déconnexion.';
        }
        header('Location: /club-feed');
        exit;
    }

    private function getBaseUrl(): string
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443;
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return ($https ? 'https' : 'http') . '://' . $host;
    }

    private function loadFacebookEnv(): void
    {
        if (!empty($_ENV['FACEBOOK_APP_ID'])) {
            return;
        }
        $envPath = __DIR__ . '/../../.env';
        if (!is_file($envPath)) {
            return;
        }
        $lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos(trim($line), '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value, " \t\"'");
            }
        }
    }

    public function index()
    {
        SessionGuard::check();

        $user = $_SESSION['user'] ?? [];
        $clubId = $user['clubId'] ?? $user['club_id'] ?? null;
        $clubName = 'votre club';
        $facebookUrl = '';
        $fbHref = '';
        $facebookDisabled = false;
        $facebookPosts = [];
        $facebookFeedConfigured = false;
        $facebookGraphError = false;
        $facebookConnected = false;
        $canManageClub = false;
        $clubPayload = null;

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
                if (is_array($payload)) {
                    $clubPayload = $payload;
                }
                if (!empty($response['success']) && $payload && is_array($payload)) {
                    $clubName = $payload['name'] ?? 'Club';
                    $facebookUrl = $payload['facebookUrl'] ?? $payload['facebook_url'] ?? '';
                    $facebookUrl = is_string($facebookUrl) ? trim($facebookUrl) : '';
                    $facebookDisabled = !empty($payload['facebookDisabled']);
                    $facebookConnected = !empty($payload['facebookConnected']);
                    $userRole = $user['role'] ?? null;
                    $userClubId = $user['clubId'] ?? $user['club_id'] ?? null;
                    $isAdmin = !empty($user['is_admin']);
                    $canManageClub = $isAdmin
                        || ($userClubId !== null && $userClubId !== '' && ((string)$userClubId === (string)($payload['id'] ?? '') || (string)$userClubId === (string)($payload['nameShort'] ?? $payload['name_short'] ?? '')));
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

        if ($fbHref !== '' && !$facebookDisabled) {
            if ($facebookConnected && $clubId) {
                try {
                    $feedResponse = $this->apiService->makeRequest("clubs/{$clubId}/facebook-feed", 'GET');
                    $feedData = $feedResponse['data'] ?? $feedResponse;
                    $facebookPosts = $feedData['posts'] ?? [];
                    if (empty($facebookPosts) && !empty($feedData['feedError'])) {
                        $facebookGraphError = true;
                    }
                    $facebookFeedConfigured = true;
                } catch (Throwable $e) {
                    error_log('ClubFeedController facebook-feed API: ' . $e->getMessage());
                    $facebookGraphError = true;
                }
            } else {
                try {
                    $fbService = new FacebookFeedService();
                    $facebookFeedConfigured = $fbService->isConfigured();
                    if ($facebookFeedConfigured) {
                        $facebookPosts = $fbService->getPagePosts($fbHref, 15);
                        if (empty($facebookPosts)) {
                            $facebookGraphError = true;
                        }
                    }
                } catch (Throwable $e) {
                    error_log('ClubFeedController FacebookFeed error: ' . $e->getMessage());
                    $facebookGraphError = true;
                }
            }
        }

        $clubFeedError = $_SESSION['club_feed_error'] ?? '';
        $clubFeedSuccess = $_SESSION['club_feed_success'] ?? '';
        unset($_SESSION['club_feed_error'], $_SESSION['club_feed_success']);

        $title = 'Actualités du club - Portail Arc Training';
        $pageTitle = $title;
        $dashboardFullPage = false;

        include 'app/Views/layouts/header.php';
        include 'app/Views/club-feed/index.php';
        include 'app/Views/layouts/footer.php';
    }
}
