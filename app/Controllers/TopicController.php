<?php

require_once 'app/Services/ApiService.php';

class TopicController {
    private $apiService;
    
    public function __construct() {
        $this->apiService = new ApiService();
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
            
            // Récupérer les messages du sujet (utiliser le même endpoint que l'app mobile)
            $messagesResult = $this->apiService->makeRequest("messages/topic/{$topicId}/history", "GET");
            $messages = [];
            if ($messagesResult['success'] && isset($messagesResult['data']) && is_array($messagesResult['data'])) {
                $messages = $messagesResult['data'];
            }
            
            // Récupérer les détails du groupe
            $groupResponse = $this->apiService->getGroupDetails($groupId);
            $group = null;
            if ($groupResponse['success'] && isset($groupResponse['data'])) {
                $group = $groupResponse['data'];
            }
            
            $title = htmlspecialchars($topic['title'] ?? 'Sujet') . ' - Portail Archers de Gémenos';
            
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
        
        $title = 'Nouveau sujet - Portail Archers de Gémenos';
        
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
}

