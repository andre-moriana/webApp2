<?php

/**
 * Récupère les publications d'une page Facebook via la Graph API.
 * Nécessite FACEBOOK_APP_ID et FACEBOOK_APP_SECRET dans .env.
 * L'app Facebook doit avoir la permission "Page Public Content Access" (App Review pour pages tierces).
 */
class FacebookFeedService
{
    private $appId;
    private $appSecret;
    private $graphVersion = 'v18.0';

    public function __construct()
    {
        $this->loadEnv();
        $this->appId = $_ENV['FACEBOOK_APP_ID'] ?? '';
        $this->appSecret = $_ENV['FACEBOOK_APP_SECRET'] ?? '';
    }

    private function loadEnv(): void
    {
        if (!empty($_ENV['FACEBOOK_APP_ID']) || !empty($_ENV['FACEBOOK_APP_SECRET'])) {
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
                $_ENV[trim($key)] = trim($value);
            }
        }
    }

    public function isConfigured(): bool
    {
        return $this->appId !== '' && $this->appSecret !== '';
    }

    /**
     * Récupère les derniers posts publiés d'une page.
     * @param string $facebookUrl URL de la page (ex: https://www.facebook.com/ArchersDeGemenos ou ArchersDeGemenos)
     * @param int $limit Nombre max de posts (1-100)
     * @return array Liste de posts [ ['id', 'message', 'created_time', 'full_picture', 'permalink_url'], ... ] ou [] en cas d'erreur
     */
    public function getPagePosts(string $facebookUrl, int $limit = 15): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        $pageId = $this->resolvePageId($facebookUrl);
        if ($pageId === null) {
            return [];
        }

        $token = $this->getAppAccessToken();
        if ($token === null) {
            return [];
        }

        $url = 'https://graph.facebook.com/' . $this->graphVersion . '/' . urlencode($pageId)
            . '/published_posts?fields=id,message,created_time,full_picture,permalink_url'
            . '&limit=' . max(1, min(100, $limit))
            . '&access_token=' . urlencode($token);

        $json = $this->httpGet($url);
        if ($json === null) {
            return [];
        }

        $data = json_decode($json, true);
        if (!isset($data['data']) || !is_array($data['data'])) {
            return [];
        }

        $posts = [];
        foreach ($data['data'] as $item) {
            $posts[] = [
                'id' => $item['id'] ?? '',
                'message' => $item['message'] ?? '',
                'created_time' => $item['created_time'] ?? '',
                'full_picture' => $item['full_picture'] ?? null,
                'permalink_url' => $item['permalink_url'] ?? '',
            ];
        }
        return $posts;
    }

    /**
     * Résout l'URL ou le username Facebook en Page ID.
     */
    private function resolvePageId(string $facebookUrl): ?string
    {
        $url = trim($facebookUrl);
        if ($url === '') {
            return null;
        }
        if (strpos($url, 'http') !== 0) {
            $url = 'https://www.facebook.com/' . ltrim($url, '/');
        }

        // Si c'est déjà un ID numérique (que des chiffres)
        $path = parse_url($url, PHP_URL_PATH);
        $path = trim($path, '/');
        $parts = explode('/', $path);
        $last = end($parts);
        if (preg_match('/^\d+$/', $last)) {
            return $last;
        }

        // Sinon considérer comme username et demander l'id à l'API
        $username = $last;
        $token = $this->getAppAccessToken();
        if ($token === null) {
            return null;
        }
        $apiUrl = 'https://graph.facebook.com/' . $this->graphVersion . '/' . urlencode($username)
            . '?fields=id&access_token=' . urlencode($token);
        $json = $this->httpGet($apiUrl);
        if ($json === null) {
            return null;
        }
        $data = json_decode($json, true);
        if (isset($data['id'])) {
            return $data['id'];
        }
        return null;
    }

    private function getAppAccessToken(): ?string
    {
        $url = 'https://graph.facebook.com/oauth/access_token'
            . '?client_id=' . urlencode($this->appId)
            . '&client_secret=' . urlencode($this->appSecret)
            . '&grant_type=client_credentials';
        $json = $this->httpGet($url);
        if ($json === null) {
            return null;
        }
        $data = json_decode($json, true);
        return $data['access_token'] ?? null;
    }

    private function httpGet(string $url): ?string
    {
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 10,
                'ignore_errors' => true,
                'header' => 'User-Agent: PortailArcTraining/1.0',
            ],
        ]);
        $result = @file_get_contents($url, false, $ctx);
        return $result !== false ? $result : null;
    }
}
