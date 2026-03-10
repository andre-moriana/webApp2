<?php

require_once 'app/Services/ApiService.php';
require_once 'app/Services/FacebookFeedService.php';
require_once 'app/Middleware/SessionGuard.php';

/**
 * Page « Actualités du club » : affiche les posts Facebook du club (via Graph API)
 * et un lien vers la page Facebook si configurée.
 */
class ClubFeedController
{
    private $apiService;

    public function __construct()
    {
        $this->apiService = new ApiService();
    }

    /**
     * Page Actualités du club : infos club + import des posts Facebook si possible.
     */
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
                if (!empty($response['success']) && $payload && is_array($payload)) {
                    $clubName = $payload['name'] ?? 'Club';
                    $facebookUrl = $payload['facebookUrl'] ?? $payload['facebook_url'] ?? '';
                    $facebookUrl = is_string($facebookUrl) ? trim($facebookUrl) : '';
                    $facebookDisabled = !empty($payload['facebookDisabled']);
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

        // Import des posts Facebook si une page est configurée et non désactivée
        if ($fbHref !== '' && !$facebookDisabled) {
            try {
                $fbService = new FacebookFeedService();
                $facebookFeedConfigured = $fbService->isConfigured();
                if ($facebookFeedConfigured) {
                    $facebookPosts = $fbService->getPagePosts($fbHref, 15);
                    // Si l'API renvoie une erreur ou rien, on garde facebookGraphError à true
                    if (empty($facebookPosts)) {
                        $facebookGraphError = true;
                    }
                }
            } catch (Throwable $e) {
                error_log('ClubFeedController FacebookFeed error: ' . $e->getMessage());
                $facebookGraphError = true;
            }
        }

        $title = 'Actualités du club - Portail Arc Training';
        $pageTitle = $title;
        $dashboardFullPage = false;

        include 'app/Views/layouts/header.php';
        include 'app/Views/club-feed/index.php';
        include 'app/Views/layouts/footer.php';
    }
}
