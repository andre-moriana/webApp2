<?php

class ClubNewsStorage
{
    private string $baseDir;
    private string $dataFile;
    private string $attachmentsDir;
    private string $attachmentsWebBase;

    public function __construct()
    {
        // Articles: stockage hors public/ (confidentiel pour les posts "club")
        $projectRoot = dirname(__DIR__, 2);
        $this->baseDir = $projectRoot . '/app/Storage/club-news';
        $this->dataFile = $this->baseDir . '/news.json';
        // Pièces jointes: même approche que le chat => stockées sous /public/uploads
        // (rend l'affichage simple via <img src="/uploads/...">)
        $this->attachmentsDir = $projectRoot . '/public/uploads/club-news';
        $this->attachmentsWebBase = '/uploads/club-news';

        $this->ensureDirs();
        $this->ensureDataFile();
    }

    private function ensureDirs(): void
    {
        if (!is_dir($this->baseDir)) {
            if (!mkdir($this->baseDir, 0770, true) && !is_dir($this->baseDir)) {
                throw new \RuntimeException('Impossible de créer le dossier de stockage des actualités.');
            }
        }
        if (!is_dir($this->attachmentsDir)) {
            if (!mkdir($this->attachmentsDir, 0770, true) && !is_dir($this->attachmentsDir)) {
                throw new \RuntimeException('Impossible de créer le dossier des pièces jointes (/public/uploads/club-news).');
            }
        }
    }

    private function ensureDataFile(): void
    {
        if (!is_file($this->dataFile)) {
            $ok = file_put_contents($this->dataFile, json_encode(['articles' => []], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            if ($ok === false) {
                throw new \RuntimeException('Impossible de créer le fichier de stockage des actualités.');
            }
        }
    }

    private function readAllInternal(): array
    {
        $fp = @fopen($this->dataFile, 'c+');
        if (!$fp) {
            return ['articles' => []];
        }

        try {
            flock($fp, LOCK_SH);
            $raw = stream_get_contents($fp);
            $data = json_decode($raw ?: '', true);
            if (!is_array($data) || !isset($data['articles']) || !is_array($data['articles'])) {
                $data = ['articles' => []];
            }
            flock($fp, LOCK_UN);
            fclose($fp);
            return $data;
        } catch (\Throwable $e) {
            try { flock($fp, LOCK_UN); } catch (\Throwable $e2) {}
            @fclose($fp);
            return ['articles' => []];
        }
    }

    private function writeAllInternal(array $data): bool
    {
        $fp = @fopen($this->dataFile, 'c+');
        if (!$fp) {
            return false;
        }
        try {
            flock($fp, LOCK_EX);
            ftruncate($fp, 0);
            rewind($fp);
            $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            fwrite($fp, $json ?: '{"articles":[]}');
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
            return true;
        } catch (\Throwable $e) {
            try { flock($fp, LOCK_UN); } catch (\Throwable $e2) {}
            @fclose($fp);
            return false;
        }
    }

    public function listAll(): array
    {
        $data = $this->readAllInternal();
        $articles = $data['articles'] ?? [];
        if (!is_array($articles)) {
            return [];
        }

        usort($articles, function ($a, $b) {
            $at = $a['created_at'] ?? '';
            $bt = $b['created_at'] ?? '';
            return strcmp((string)$bt, (string)$at);
        });

        return $articles;
    }

    private function generateId(): string
    {
        return bin2hex(random_bytes(8)) . '-' . time();
    }

    public function create(array $payload): array
    {
        $data = $this->readAllInternal();
        $articles = $data['articles'] ?? [];
        if (!is_array($articles)) {
            $articles = [];
        }

        $now = gmdate('c');
        $article = [
            'id' => $this->generateId(),
            'club_id' => (string)($payload['club_id'] ?? ''),
            'club_name' => isset($payload['club_name']) ? (string)$payload['club_name'] : null,
            'audience' => (string)($payload['audience'] ?? 'public'),
            'title' => isset($payload['title']) ? (string)$payload['title'] : '',
            'content' => (string)($payload['content'] ?? ''),
            'created_at' => $now,
            'updated_at' => $now,
            'author_user_id' => isset($payload['author_user_id']) ? (string)$payload['author_user_id'] : '',
            'author_name' => isset($payload['author_name']) ? (string)$payload['author_name'] : '',
            'attachment' => isset($payload['attachment']) ? $payload['attachment'] : null,
        ];

        $articles[] = $article;
        $data['articles'] = $articles;
        $ok = $this->writeAllInternal($data);
        if (!$ok) {
            throw new \RuntimeException('Impossible d\'écrire les actualités.');
        }
        return $article;
    }

    public function update(string $id, array $updates): ?array
    {
        $data = $this->readAllInternal();
        $articles = $data['articles'] ?? [];
        if (!is_array($articles)) {
            $articles = [];
        }

        $found = null;
        $now = gmdate('c');
        foreach ($articles as &$a) {
            if (($a['id'] ?? '') === $id) {
                if (array_key_exists('title', $updates)) {
                    $a['title'] = (string)$updates['title'];
                }
                if (array_key_exists('content', $updates)) {
                    $a['content'] = (string)$updates['content'];
                }
                if (array_key_exists('audience', $updates)) {
                    $a['audience'] = (string)$updates['audience'];
                }
                $a['updated_at'] = $now;
                $found = $a;
                break;
            }
        }
        unset($a);

        if ($found === null) {
            return null;
        }

        $data['articles'] = $articles;
        $ok = $this->writeAllInternal($data);
        if (!$ok) {
            throw new \RuntimeException('Impossible d\'écrire les actualités.');
        }
        return $found;
    }

    public function delete(string $id): ?array
    {
        $data = $this->readAllInternal();
        $articles = $data['articles'] ?? [];
        if (!is_array($articles)) {
            $articles = [];
        }

        $deleted = null;
        $remaining = [];
        foreach ($articles as $a) {
            if (($a['id'] ?? '') === $id) {
                $deleted = $a;
                continue;
            }
            $remaining[] = $a;
        }

        if ($deleted === null) {
            return null;
        }

        $data['articles'] = $remaining;
        $ok = $this->writeAllInternal($data);
        if (!$ok) {
            throw new \RuntimeException('Impossible d\'écrire les actualités.');
        }

        return $deleted;
    }

    public function storeUploadedAttachment(array $file): array
    {
        $originalName = $file['name'] ?? 'piece-jointe';
        $tmpName = $file['tmp_name'] ?? '';
        $mimeType = $file['type'] ?? 'application/octet-stream';
        $size = (int)($file['size'] ?? 0);

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new \RuntimeException('Upload invalide.');
        }

        $ext = pathinfo((string)$originalName, PATHINFO_EXTENSION);
        $storedFilename = bin2hex(random_bytes(16)) . ($ext ? '.' . strtolower($ext) : '');
        $dest = $this->attachmentsDir . '/' . $storedFilename;

        if (!move_uploaded_file($tmpName, $dest)) {
            throw new \RuntimeException('Impossible d\'enregistrer la pièce jointe (droits /public/uploads/club-news).');
        }

        return [
            'storedFilename' => $storedFilename,
            'originalName' => (string)$originalName,
            'mimeType' => (string)$mimeType,
            'size' => $size,
            'url' => $this->attachmentsWebBase . '/' . rawurlencode($storedFilename),
        ];
    }

    public function getAttachmentPath(string $storedFilename): string
    {
        $storedFilename = basename($storedFilename);
        return $this->attachmentsDir . '/' . $storedFilename;
    }
}

