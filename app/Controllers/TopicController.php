<?php

require_once 'app/Services/ApiService.php';

class TopicController {
    private $apiService;
    
    public function __construct() {
        $this->apiService = new ApiService();
    }

    private function sendJson(array $payload, int $status = 200): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($status);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    public function show($groupId, $topicId) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }

        try {
            // Récupérer les détails du sujet
            $result = $this->apiService->makeRequest("topics/{$topicId}", "GET");
            
            if (!$result['success'] || !isset($result['data'])) {
                $_SESSION['error'] = 'Sujet non trouvé';
                header("Location: /groups");
                exit;
            }
            
            $topic = $result['data'];
            
            // Récupérer les messages du sujet
            $messagesResult = $this->apiService->makeRequest("topics/{$topicId}/messages", "GET");
            $messages = [];
            if ($messagesResult['success'] && isset($messagesResult['data']) && is_array($messagesResult['data'])) {
                $messages = $messagesResult['data'];
                
                // Corriger les URLs des pièces jointes
                $correctedMessages = [];
                foreach ($messages as $message) {
                    if (isset($message['attachment']) && is_array($message['attachment'])) {
                        $attachment = $message['attachment'];
                        
                        // Forcer la correction pour TOUS les fichiers
                        if (isset($attachment['url']) && strpos($attachment['url'], 'url=') !== false) {
                            $urlParts = parse_url($attachment['url']);
                            if (isset($urlParts['query'])) {
                                parse_str($urlParts['query'], $queryParams);
                                if (isset($queryParams['url'])) {
                                    $decodedPath = urldecode($queryParams['url']);
                                    // Construire URL complète
                                    if (str_starts_with($decodedPath, 'http')) {
                                        $attachment['url'] = $decodedPath;
                                    } else {
                                        $attachment['url'] = 'https://api.arctraining.fr' . $decodedPath;
                                    }
                                }
                            }
                        }
                        elseif (isset($attachment['storedFilename'])) {
                            $attachment['url'] = 'https://api.arctraining.fr/uploads/messages/' . $attachment['storedFilename'];
                        }
                        
                        $message['attachment'] = $attachment;
                    }
                    $correctedMessages[] = $message;
                }
                $messages = $correctedMessages;
            }
            
            // Récupérer les détails du groupe
            $groupResponse = $this->apiService->getGroupDetails($groupId);
            $group = null;
            if ($groupResponse['success'] && isset($groupResponse['data'])) {
                $group = $groupResponse['data'];
            }
            
            $title = htmlspecialchars($topic['title'] ?? 'Sujet') . ' - Portail Arc Training';
            
            include 'app/Views/layouts/header.php';
            include 'app/Views/topics/show.php';
            include 'app/Views/layouts/footer.php';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erreur lors de la récupération du sujet';
            header("Location: /groups");
            exit;
        }
    }
    
    public function create($groupId) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }

        // Récupérer les détails du groupe
        $groupResponse = $this->apiService->getGroupDetails($groupId);
        $group = null;
        if ($groupResponse['success'] && isset($groupResponse['data'])) {
            $group = $groupResponse['data'];
        } else {
            $_SESSION['error'] = 'Groupe non trouvé';
            header("Location: /groups");
            exit;
        }
        
        $title = 'Nouveau sujet - Portail  Arc Training';
        
        include 'app/Views/layouts/header.php';
        include 'app/Views/topics/create.php';
        include 'app/Views/layouts/footer.php';
    }
    
    public function store($groupId) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /groups/{$groupId}/topics/create");
            exit;
        }

        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';

        if (empty($title)) {
            $_SESSION['error'] = 'Le titre du sujet est requis';
            header("Location: /groups/{$groupId}/topics/create");
            exit;
        }

        try {
            $data = [
                'group_id' => $groupId,
                'title' => $title,
                'description' => $description
            ];
            
            $result = $this->apiService->makeRequest("topics/create", "POST", $data);
            
            if ($result['success'] && isset($result['data']['id'])) {
                $_SESSION['success'] = 'Sujet créé avec succès';
                header("Location: /groups/{$groupId}/topics/{$result['data']['id']}");
                exit;
            } else {
                $_SESSION['error'] = $result['message'] ?? 'Erreur lors de la création du sujet';
                header("Location: /groups/{$groupId}/topics/create");
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erreur lors de la création du sujet';
            header("Location: /groups/{$groupId}/topics/create");
            exit;
        }
    }

    public function update($topicId) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->sendJson(['success' => false, 'message' => 'Non authentifié'], 401);
        }

        $payload = json_decode(file_get_contents('php://input') ?: '', true);
        if (!is_array($payload)) {
            $this->sendJson(['success' => false, 'message' => 'JSON invalide'], 400);
        }

        $response = $this->apiService->makeRequest("topics/{$topicId}", 'PUT', [
            'title' => (string)($payload['title'] ?? ''),
            'description' => (string)($payload['description'] ?? ''),
        ]);

        if (empty($response['success'])) {
            $status = (int)($response['status_code'] ?? 500);
            $message = $response['message'] ?? $response['error'] ?? 'Erreur lors de la modification du sujet';
            $this->sendJson(['success' => false, 'message' => $message], $status);
        }

        $this->sendJson(['success' => true, 'data' => $response['data'] ?? null]);
    }

    public function destroy($topicId) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            $this->sendJson(['success' => false, 'message' => 'Non authentifié'], 401);
        }

        $response = $this->apiService->makeRequest("topics/{$topicId}", 'DELETE');
        if (empty($response['success'])) {
            $status = (int)($response['status_code'] ?? 500);
            $message = $response['message'] ?? $response['error'] ?? 'Erreur lors de la suppression du sujet';
            $this->sendJson(['success' => false, 'message' => $message], $status);
        }

        $this->sendJson(['success' => true]);
    }
}

