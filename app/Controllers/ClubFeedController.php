<?php

require_once 'app/Services/ApiService.php';
require_once 'app/Services/FacebookFeedService.php';
require_once 'app/Middleware/SessionGuard.php';

/**
 * Page d'accueil des Archers : actualités Facebook du club (posts affichés sur la page).
 */
class ClubFeedController
{
    private $apiService;

    public function __construct()
    {
        $this->apiService = new ApiService();
    }

    public function index()
    {
        SessionGuard::check();

        $user = $_SESSION['user'] ?? [];
        $clubId = $user['clubId'] ?? $user['club_id'] ?? null;
        $club = null;
        $facebookUrl = '';
        $clubName = 'votre club';
        $posts = [];
        $fbHref = '';

        if ($clubId) {
            try {
                $response = $this->apiService->makeRequest("clubs/{$clubId}", 'GET');
                $payload = $this->apiService->unwrapData($response);
                if ($response['success'] && $payload && is_array($payload)) {
                    $club = $payload;
                    $clubName = $club['name'] ?? 'Club';
                    $facebookUrl = $club['facebookUrl'] ?? $club['facebook_url'] ?? '';
                    $facebookUrl = is_string($facebookUrl) ? trim($facebookUrl) : '';
                }
            } catch (Exception $e) {
                error_log('ClubFeedController: ' . $e->getMessage());
            }
        }

        if ($facebookUrl !== '') {
            $fbHref = (strpos($facebookUrl, 'http') === 0)
                ? $facebookUrl
                : 'https://www.facebook.com/' . ltrim($facebookUrl, '/');
            try {
                $fbService = new FacebookFeedService();
                if ($fbService->isConfigured()) {
                    $posts = $fbService->getPagePosts($facebookUrl, 15);
                }
            } catch (Exception $e) {
                error_log('ClubFeedController Facebook: ' . $e->getMessage());
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
