<?php

require_once 'app/Middleware/SessionGuard.php';
require_once 'app/Services/ApiService.php';

class ClubNewsApiController
{
    private ApiService $apiService;

    public function __construct()
    {
        $this->apiService = new ApiService();
    }

    private function sendJson($data, int $statusCode = 200): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function startsWith(string $haystack, string $needle): bool
    {
        if ($needle === '') return true;
        return substr($haystack, 0, strlen($needle)) === $needle;
    }

    private function endsWith(string $haystack, string $needle): bool
    {
        if ($needle === '') return true;
        $len = strlen($needle);
        if ($len > strlen($haystack)) return false;
        return substr($haystack, -$len) === $needle;
    }

    private function getApiErrorMessage(array $response, string $fallback): string
    {
        if (!empty($response['message']) && is_string($response['message'])) {
            return $response['message'];
        }
        if (!empty($response['error']) && is_string($response['error'])) {
            return $response['error'];
        }
        $data = $response['data'] ?? null;
        if (is_array($data)) {
            if (!empty($data['message']) && is_string($data['message'])) {
                return $data['message'];
            }
            if (!empty($data['error']) && is_string($data['error'])) {
                return $data['error'];
            }
        }
        return $fallback;
    }

    private function normalizeArticleForClient(array $a): array
    {
        if (!empty($a['attachment']) && is_array($a['attachment']) && !empty($a['id'])) {
            $att = $a['attachment'];
            $originalUrl = $att['url'] ?? $att['path'] ?? null;
            if (!is_string($originalUrl) || $originalUrl === '') {
                $originalUrl = '';
            }

            $mime = (string)($att['mimeType'] ?? '');
            $name = strtolower((string)($att['originalName'] ?? $att['filename'] ?? ''));
            $isImage = $this->startsWith($mime, 'image/')
                || $this->endsWith($name, '.jpg')
                || $this->endsWith($name, '.jpeg')
                || $this->endsWith($name, '.png')
                || $this->endsWith($name, '.gif')
                || $this->endsWith($name, '.webp')
                || $this->endsWith($name, '.bmp')
                || $this->endsWith($name, '.svg');
            $isPdf = $mime === 'application/pdf' || $this->endsWith($name, '.pdf');

            if ($originalUrl !== '') {
                if ($isImage) {
                    $att['url'] = '/messages/image/' . rawurlencode((string)$a['id']) . '?url=' . urlencode($originalUrl);
                } elseif ($isPdf) {
                    $att['url'] = '/messages/attachment/' . rawurlencode((string)$a['id']) . '?inline=1&url=' . urlencode($originalUrl);
                } else {
                    $att['url'] = '/messages/attachment/' . rawurlencode((string)$a['id']) . '?url=' . urlencode($originalUrl);
                }
            }
            $a['attachment'] = $att;
        }
        return $a;
    }

    public function index(): void
    {
        SessionGuard::checkAjax();
        $response = $this->apiService->makeRequest('club-news', 'GET');
        if (empty($response['success'])) {
            $this->sendJson([
                'success' => false,
                'message' => $this->getApiErrorMessage($response, 'Erreur API club-news')
            ], $response['status_code'] ?? 500);
        }

        $payload = $this->apiService->unwrapData($response);
        $public = is_array($payload['public'] ?? null) ? $payload['public'] : [];
        $club = is_array($payload['club'] ?? null) ? $payload['club'] : [];
        $public = array_map([$this, 'normalizeArticleForClient'], $public);
        $club = array_map([$this, 'normalizeArticleForClient'], $club);

        $this->sendJson(['success' => true, 'public' => $public, 'club' => $club]);
    }

    public function store(): void
    {
        SessionGuard::checkAjax();
        try {
            $data = [
                'title' => $_POST['title'] ?? '',
                'content' => $_POST['content'] ?? '',
                'audience' => $_POST['audience'] ?? 'club',
            ];
            $file = $_FILES['attachment'] ?? null;
            $resp = $this->apiService->makeRequestWithFile('club-news', 'POST', $data, $file);
            if (empty($resp['success'])) {
                $this->sendJson([
                    'success' => false,
                    'message' => $this->getApiErrorMessage($resp, 'Erreur API club-news')
                ], $resp['status_code'] ?? 500);
            }
            $payload = $this->apiService->unwrapData($resp);
            if (is_array($payload) && isset($payload['article']) && is_array($payload['article'])) {
                $payload['article'] = $this->normalizeArticleForClient($payload['article']);
            }
            $this->sendJson(is_array($payload) ? $payload : ['success' => true], 201);
        } catch (\Throwable $e) {
            $this->sendJson(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(string $id): void
    {
        SessionGuard::checkAjax();
        $payload = json_decode(file_get_contents('php://input') ?: '', true);
        if (!is_array($payload)) {
            $this->sendJson(['success' => false, 'message' => 'JSON invalide'], 400);
        }
        $resp = $this->apiService->makeRequest("club-news/{$id}", 'PUT', $payload);
        if (empty($resp['success'])) {
            $this->sendJson([
                'success' => false,
                'message' => $this->getApiErrorMessage($resp, 'Erreur API club-news')
            ], $resp['status_code'] ?? 500);
        }
        $data = $this->apiService->unwrapData($resp);
        if (is_array($data) && isset($data['article']) && is_array($data['article'])) {
            $data['article'] = $this->normalizeArticleForClient($data['article']);
        }
        $this->sendJson(is_array($data) ? $data : ['success' => true]);
    }

    public function destroy(string $id): void
    {
        SessionGuard::checkAjax();
        $resp = $this->apiService->makeRequest("club-news/{$id}", 'DELETE');
        if (empty($resp['success'])) {
            $this->sendJson([
                'success' => false,
                'message' => $this->getApiErrorMessage($resp, 'Erreur API club-news')
            ], $resp['status_code'] ?? 500);
        }
        $this->sendJson(['success' => true]);
    }

    public function comment(string $id): void
    {
        SessionGuard::checkAjax();
        $payload = json_decode(file_get_contents('php://input') ?: '', true);
        if (!is_array($payload)) {
            $this->sendJson(['success' => false, 'message' => 'JSON invalide'], 400);
        }
        $resp = $this->apiService->makeRequest("club-news/{$id}/comments", 'POST', $payload);
        if (empty($resp['success'])) {
            $this->sendJson([
                'success' => false,
                'message' => $this->getApiErrorMessage($resp, 'Erreur API club-news')
            ], $resp['status_code'] ?? 500);
        }
        $data = $this->apiService->unwrapData($resp);
        if (is_array($data) && isset($data['article']) && is_array($data['article'])) {
            $data['article'] = $this->normalizeArticleForClient($data['article']);
        }
        $this->sendJson(is_array($data) ? $data : ['success' => true]);
    }

    public function like(string $id): void
    {
        SessionGuard::checkAjax();
        $resp = $this->apiService->makeRequest("club-news/{$id}/likes", 'POST', []);
        if (empty($resp['success'])) {
            $this->sendJson([
                'success' => false,
                'message' => $this->getApiErrorMessage($resp, 'Erreur API club-news')
            ], $resp['status_code'] ?? 500);
        }
        $data = $this->apiService->unwrapData($resp);
        if (is_array($data) && isset($data['article']) && is_array($data['article'])) {
            $data['article'] = $this->normalizeArticleForClient($data['article']);
        }
        $this->sendJson(is_array($data) ? $data : ['success' => true]);
    }
}

