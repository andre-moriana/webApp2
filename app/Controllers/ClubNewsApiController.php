<?php

require_once 'app/Middleware/SessionGuard.php';
require_once 'app/Services/PermissionService.php';
require_once 'app/Services/ApiService.php';

class ClubNewsApiController
{
    private PermissionService $permissionService;
    private ApiService $apiService;

    public function __construct()
    {
        $this->permissionService = new PermissionService();
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

    private function getUser(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['user'] ?? [];
    }

    private function getUserClubId(array $user): ?string
    {
        $clubId = $user['clubId'] ?? $user['club_id'] ?? null;
        return $clubId !== null && $clubId !== '' ? (string)$clubId : null;
    }

    private function canManage(array $user): bool
    {
        return $this->permissionService->isAdmin($user) || $this->permissionService->hasRoleLevel($user, 'Dirigeant');
    }

    private function canSeeClubArticle(array $user, array $article): bool
    {
        if ($this->permissionService->isAdmin($user)) {
            return true;
        }
        $userClubId = $this->getUserClubId($user);
        $articleClubId = (string)($article['club_id'] ?? '');
        return $userClubId !== null && $articleClubId !== '' && $userClubId === $articleClubId;
    }

    private function canMutateArticle(array $user, array $article): bool
    {
        if (!$this->canManage($user)) {
            return false;
        }
        if ($this->permissionService->isAdmin($user)) {
            return true;
        }
        $userClubId = $this->getUserClubId($user);
        $articleClubId = (string)($article['club_id'] ?? '');
        return $userClubId !== null && $articleClubId !== '' && $userClubId === $articleClubId;
    }

    private function normalizeArticleForClient(array $a): array
    {
        // On applique exactement la logique du chat: on garde l'URL originale (API)
        // puis on construit une URL proxy WebApp2 /messages/image|attachment/... avec ?url=...
        if (!empty($a['attachment']) && is_array($a['attachment']) && !empty($a['id'])) {
            $att = $a['attachment'];
            $originalUrl = $att['url'] ?? $att['path'] ?? null;
            if (!is_string($originalUrl) || $originalUrl === '') {
                if (!empty($att['storedFilename'])) {
                    $originalUrl = '/uploads/messages/' . $att['storedFilename'];
                } elseif (!empty($att['filename'])) {
                    $originalUrl = '/uploads/messages/' . $att['filename'];
                } else {
                    $originalUrl = '';
                }
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

    private function getClubGroupId(string $clubId): ?string
    {
        try {
            $groupsResponse = $this->apiService->makeRequest('groups/list', 'GET');
            if (empty($groupsResponse['success']) || !is_array($groupsResponse['data'] ?? null)) {
                return null;
            }
            foreach ($groupsResponse['data'] as $g) {
                if (!is_array($g)) {
                    continue;
                }
                $gid = $g['id'] ?? $g['_id'] ?? null;
                $gClubId = $g['club_id'] ?? $g['clubId'] ?? null;
                if ($gid !== null && $gClubId !== null && (string)$gClubId === (string)$clubId) {
                    return (string)$gid;
                }
            }
        } catch (\Throwable $e) {
            error_log('ClubNewsApiController getClubGroupId error: ' . $e->getMessage());
        }
        return null;
    }

    private function startsWith(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }
        return substr($haystack, 0, strlen($needle)) === $needle;
    }

    private function endsWith(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }
        $len = strlen($needle);
        if ($len > strlen($haystack)) {
            return false;
        }
        return substr($haystack, -$len) === $needle;
    }

    public function index(): void
    {
        SessionGuard::checkAjax();
        $user = $this->getUser();
        $clubId = $this->getUserClubId($user);
        if ($clubId === null) {
            $this->sendJson(['success' => true, 'public' => [], 'club' => []]);
        }

        // Même méthode que le chat: on lit l'historique du groupe du club côté API backend.
        $groupId = $this->getClubGroupId($clubId);
        if ($groupId === null) {
            $this->sendJson(['success' => true, 'public' => [], 'club' => []]);
        }

        $response = $this->apiService->makeRequest('messages/' . $groupId . '/history', 'GET');
        $messages = [];
        if (!empty($response['success']) && is_array($response['data'] ?? null)) {
            $messages = $response['data'];
        }

        $club = [];
        foreach ($messages as $m) {
            if (!is_array($m)) {
                continue;
            }
            $id = (string)($m['_id'] ?? $m['id'] ?? '');
            if ($id === '') {
                continue;
            }
            $attachment = null;
            if (isset($m['attachment']) && is_array($m['attachment'])) {
                $attachment = $m['attachment'];
            }
            $article = [
                'id' => $id,
                'club_id' => $clubId,
                'audience' => 'club',
                'title' => '',
                'content' => (string)($m['content'] ?? ''),
                'created_at' => $m['createdAt'] ?? $m['created_at'] ?? null,
                'updated_at' => $m['updatedAt'] ?? $m['updated_at'] ?? null,
                'author_user_id' => (string)($m['user_id'] ?? $m['userId'] ?? ''),
                'author_name' => (string)($m['author_name'] ?? $m['authorName'] ?? $m['user_name'] ?? $m['userName'] ?? ''),
                'attachment' => $attachment,
            ];
            $club[] = $this->normalizeArticleForClient($article);
        }

        $this->sendJson([
            'success' => true,
            'public' => [],
            'club' => $club,
        ]);
    }

    public function store(): void
    {
        SessionGuard::checkAjax();
        $user = $this->getUser();
        if (!$this->canManage($user)) {
            $this->sendJson(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        $clubId = $this->getUserClubId($user);
        if ($clubId === null) {
            $this->sendJson(['success' => false, 'message' => 'Club manquant en session.'], 400);
        }

        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';

        $content = is_string($content) ? trim($content) : '';

        $hasFile = !empty($_FILES['attachment']) && is_array($_FILES['attachment']) && ($_FILES['attachment']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        if ($content === '' && !$hasFile) {
            $this->sendJson(['success' => false, 'message' => 'Le post doit contenir du texte ou une pièce jointe.'], 422);
        }

        if (!empty($_FILES['attachment']) && is_array($_FILES['attachment']) && ($_FILES['attachment']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if (($_FILES['attachment']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                $this->sendJson(['success' => false, 'message' => 'Erreur upload PJ (code ' . (int)($_FILES['attachment']['error'] ?? -1) . ').'], 422);
            }
            $max = 10 * 1024 * 1024;
            if ((int)($_FILES['attachment']['size'] ?? 0) > $max) {
                $this->sendJson(['success' => false, 'message' => 'Pièce jointe trop volumineuse (max 10 Mo).'], 422);
            }
        }
        $groupId = $this->getClubGroupId($clubId);
        if ($groupId === null) {
            $this->sendJson(['success' => false, 'message' => 'Groupe du club introuvable.'], 500);
        }

        $finalContent = '';
        $t = is_string($title) ? trim($title) : '';
        if ($t !== '') {
            $finalContent .= $t . "\n";
        }
        $finalContent .= $content;

        try {
            $postData = [
                'content' => $finalContent,
                'group_id' => (int)$groupId,
            ];
            if ($hasFile) {
                $file = $_FILES['attachment'];
                $postData['attachment'] = new CURLFile($file['tmp_name'], $file['type'], $file['name']);
            }
            $resp = $this->apiService->makeRequestWithFile("messages/{$groupId}/send", 'POST', $postData);
            if (empty($resp['success'])) {
                $this->sendJson(['success' => false, 'message' => $resp['message'] ?? 'Erreur envoi vers API'], $resp['status_code'] ?? 500);
            }
            // Recharger l'historique et renvoyer le dernier comme "article"
            $this->sendJson(['success' => true], 201);
        } catch (\Throwable $e) {
            error_log('ClubNewsApiController store (proxy) error: ' . $e->getMessage());
            $this->sendJson(['success' => false, 'message' => 'Erreur lors de l’envoi vers l’API.', 'error' => $e->getMessage()], 500);
        }

    }

    public function update(string $id): void
    {
        SessionGuard::checkAjax();
        $user = $this->getUser();
        if (!$this->canManage($user)) {
            $this->sendJson(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        $raw = file_get_contents('php://input') ?: '';
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            $this->sendJson(['success' => false, 'message' => 'JSON invalide.'], 400);
        }

        $newContent = (string)($payload['content'] ?? '');
        if (trim($newContent) === '') {
            $this->sendJson(['success' => false, 'message' => 'Le contenu est requis.'], 422);
        }

        $resp = $this->apiService->makeRequest("messages/{$id}", "PUT", ['content' => $newContent]);
        if (empty($resp['success'])) {
            $this->sendJson(['success' => false, 'message' => $resp['message'] ?? 'Erreur'], $resp['status_code'] ?? 500);
        }

        $this->sendJson([
            'success' => true,
        ]);
    }

    public function destroy(string $id): void
    {
        SessionGuard::checkAjax();
        $user = $this->getUser();
        if (!$this->canManage($user)) {
            $this->sendJson(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        $resp = $this->apiService->makeRequest("messages/{$id}", "DELETE");
        if (empty($resp['success'])) {
            $this->sendJson(['success' => false, 'message' => $resp['message'] ?? 'Erreur'], $resp['status_code'] ?? 500);
        }

        $this->sendJson(['success' => true]);
    }
}

