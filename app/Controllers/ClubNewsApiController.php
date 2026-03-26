<?php

require_once 'app/Middleware/SessionGuard.php';
require_once 'app/Services/PermissionService.php';
require_once 'app/Services/ClubNewsStorage.php';

class ClubNewsApiController
{
    private PermissionService $permissionService;
    private ClubNewsStorage $storage;

    public function __construct()
    {
        $this->permissionService = new PermissionService();
        $this->storage = new ClubNewsStorage();
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
        if (!empty($a['attachment']) && is_array($a['attachment']) && !empty($a['attachment']['storedFilename'])) {
            $stored = (string)$a['attachment']['storedFilename'];
            // URL servie par la route applicative (évite les conflits de répertoires statiques)
            $a['attachment']['url'] = '/club-news/attachment/' . rawurlencode($stored);
        }
        return $a;
    }

    public function index(): void
    {
        SessionGuard::checkAjax();
        $user = $this->getUser();

        $all = $this->storage->listAll();
        $public = [];
        $club = [];

        foreach ($all as $a) {
            $aud = $a['audience'] ?? 'public';
            if ($aud === 'club') {
                if ($this->canSeeClubArticle($user, $a)) {
                    $club[] = $this->normalizeArticleForClient($a);
                }
            } else {
                $public[] = $this->normalizeArticleForClient($a);
            }
        }

        $this->sendJson([
            'success' => true,
            'public' => $public,
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
        if ($clubId === null && !$this->permissionService->isAdmin($user)) {
            $this->sendJson(['success' => false, 'message' => 'Club manquant en session.'], 400);
        }

        $audience = $_POST['audience'] ?? 'public';
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';

        $audience = in_array($audience, ['public', 'club'], true) ? $audience : 'public';
        $content = is_string($content) ? trim($content) : '';

        $attachment = null;
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
        try {
            if ($hasFile) {
                $attachment = $this->storage->storeUploadedAttachment($_FILES['attachment']);
            }

            $article = $this->storage->create([
                'club_id' => $clubId ?? '',
                'club_name' => $user['clubName'] ?? $user['club_name'] ?? null,
                'audience' => $audience,
                'title' => is_string($title) ? trim($title) : '',
                'content' => $content,
                'author_user_id' => $user['id'] ?? $user['_id'] ?? '',
                'author_name' => $user['name'] ?? $user['fullName'] ?? $user['fullname'] ?? $user['email'] ?? '',
                'attachment' => $attachment,
            ]);
        } catch (\Throwable $e) {
            error_log('ClubNewsApiController store error: ' . $e->getMessage());
            $this->sendJson([
                'success' => false,
                'message' => 'Impossible d\'enregistrer le post. Vérifier les droits d\'écriture serveur.',
                'error' => $e->getMessage(),
            ], 500);
        }

        $this->sendJson([
            'success' => true,
            'article' => $this->normalizeArticleForClient($article),
        ], 201);
    }

    public function update(string $id): void
    {
        SessionGuard::checkAjax();
        $user = $this->getUser();
        if (!$this->canManage($user)) {
            $this->sendJson(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        $all = $this->storage->listAll();
        $existing = null;
        foreach ($all as $a) {
            if (($a['id'] ?? '') === $id) {
                $existing = $a;
                break;
            }
        }
        if ($existing === null) {
            $this->sendJson(['success' => false, 'message' => 'Actualité introuvable.'], 404);
        }
        if (!$this->canMutateArticle($user, $existing)) {
            $this->sendJson(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        $raw = file_get_contents('php://input') ?: '';
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            $this->sendJson(['success' => false, 'message' => 'JSON invalide.'], 400);
        }

        $audience = $payload['audience'] ?? null;
        if ($audience !== null && !in_array($audience, ['public', 'club'], true)) {
            $this->sendJson(['success' => false, 'message' => 'Audience invalide.'], 422);
        }

        $updated = $this->storage->update($id, [
            'title' => $payload['title'] ?? '',
            'content' => $payload['content'] ?? '',
            'audience' => $audience ?? ($existing['audience'] ?? 'public'),
        ]);

        if ($updated === null) {
            $this->sendJson(['success' => false, 'message' => 'Actualité introuvable.'], 404);
        }

        $this->sendJson([
            'success' => true,
            'article' => $this->normalizeArticleForClient($updated),
        ]);
    }

    public function destroy(string $id): void
    {
        SessionGuard::checkAjax();
        $user = $this->getUser();
        if (!$this->canManage($user)) {
            $this->sendJson(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        $all = $this->storage->listAll();
        $existing = null;
        foreach ($all as $a) {
            if (($a['id'] ?? '') === $id) {
                $existing = $a;
                break;
            }
        }
        if ($existing === null) {
            $this->sendJson(['success' => false, 'message' => 'Actualité introuvable.'], 404);
        }
        if (!$this->canMutateArticle($user, $existing)) {
            $this->sendJson(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        $deleted = $this->storage->delete($id);
        if ($deleted === null) {
            $this->sendJson(['success' => false, 'message' => 'Actualité introuvable.'], 404);
        }

        // Supprimer la pièce jointe associée (si existante)
        if (!empty($deleted['attachment']['storedFilename'])) {
            $path = $this->storage->getAttachmentPath((string)$deleted['attachment']['storedFilename']);
            if (is_file($path)) {
                @unlink($path);
            }
        }

        $this->sendJson(['success' => true]);
    }

    public function downloadAttachment(string $storedFilename): void
    {
        SessionGuard::check();
        $user = $this->getUser();

        $storedFilename = basename((string)$storedFilename);
        if ($storedFilename === '') {
            http_response_code(404);
            echo 'Not found';
            exit;
        }

        // Vérifier que l'utilisateur a le droit de télécharger (PJ attachée à un article visible)
        $all = $this->storage->listAll();
        $found = null;
        foreach ($all as $a) {
            if (!empty($a['attachment']['storedFilename']) && (string)$a['attachment']['storedFilename'] === $storedFilename) {
                $found = $a;
                break;
            }
        }
        if ($found === null) {
            http_response_code(404);
            echo 'Not found';
            exit;
        }

        $aud = $found['audience'] ?? 'public';
        if ($aud === 'club' && !$this->canSeeClubArticle($user, $found)) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }

        $path = $this->storage->getAttachmentPath($storedFilename);
        if (!is_file($path)) {
            http_response_code(404);
            echo 'Not found';
            exit;
        }

        $mime = $found['attachment']['mimeType'] ?? 'application/octet-stream';
        $original = $found['attachment']['originalName'] ?? $storedFilename;

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: public, max-age=3600');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
        header('Content-Disposition: inline; filename="' . str_replace('"', '', (string)$original) . '"');
        readfile($path);
        exit;
    }
}

