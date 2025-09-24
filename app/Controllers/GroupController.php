<?php

require_once 'app/Services/ApiService.php';

class GroupController {
    private $apiService;
    
    public function __construct() {
        $this->apiService = new ApiService();
    }
    
    public function index() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }
        
        $groups = [];
        $error = null;
        
        // Vérifier si l'utilisateur est admin et utiliser des données de test
        $isAdmin = isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'] === true;
        $isDemoToken = isset($_SESSION['token']) && strpos($_SESSION['token'], 'demo-token-') === 0;
        
        if ($isAdmin && $isDemoToken) {
            // Utiliser des données de test pour l'admin
            error_log("GroupController: Utilisation des données de test pour l'admin");
            $groups = $this->getTestGroups();
        } else {
            try {
                error_log("GroupController: Tentative de récupération des groupes via API");
                // Essayer de récupérer les groupes depuis l'API
                $response = $this->apiService->getGroups();
                error_log("GroupController: Réponse de l'API: " . print_r($response, true));
                
                if ($response['success']) {
                    if (!empty($response['data']['groups'])) {
                        $groups = $response['data']['groups'];
                        error_log("GroupController: Structure complète des données reçues: " . json_encode($response));
                        error_log("GroupController: Premier groupe exemple: " . json_encode($groups[0] ?? null));
                        error_log("GroupController: Clés disponibles dans le premier groupe: " . json_encode(array_keys($groups[0] ?? [])));
                    } else {
                        error_log("GroupController: Aucun groupe dans la réponse");
                        $error = 'Aucun groupe disponible';
                    }
                } else {
                    error_log("GroupController: Erreur API - " . ($response['message'] ?? 'Erreur inconnue'));
                    $error = $response['message'] ?? 'Erreur lors de la récupération des groupes';
                }
            } catch (Exception $e) {
                error_log("GroupController: Exception - " . $e->getMessage());
                $error = 'Erreur lors de la récupération des groupes';
            }
        }
        
        // Charger les messages de chat pour le premier groupe (si disponible)
        $chatMessages = [];
        $chatError = null;
        
        if (!empty($groups)) {
            $firstGroupId = $groups[0]['id'];
            error_log("GroupController: Tentative de chargement des messages pour le groupe ID: " . $firstGroupId);
            
            try {
                $messagesResponse = $this->apiService->makeRequest("messages/{$firstGroupId}/history", "GET");
                error_log("GroupController: Réponse API messages: " . json_encode($messagesResponse));
                
                if ($messagesResponse['success']) {
                    $chatMessages = $messagesResponse['data'];
                    error_log("GroupController: Messages chargés: " . count($chatMessages) . " messages");
                } else {
                    $chatError = $messagesResponse['message'] ?? 'Erreur lors de la récupération des messages du premier groupe';
                    error_log("GroupController: Erreur API messages: " . $chatError);
                }
            } catch (Exception $e) {
                $chatError = 'Erreur lors de la récupération des messages du premier groupe: ' . $e->getMessage();
                error_log("GroupController: Exception lors du chargement des messages: " . $e->getMessage());
            }
        } else {
            error_log("GroupController: Aucun groupe disponible pour charger les messages");
        }
        
        $title = 'Gestion des groupes - Portail Archers de Gémenos';
        
        // DEBUG: Afficher les variables avant d'inclure la vue
        error_log("GroupController: Variables avant inclusion de la vue:");
        error_log("GroupController: groups = " . print_r($groups, true));
        error_log("GroupController: chatMessages = " . print_r($chatMessages, true));
        error_log("GroupController: error = " . ($error ?? 'null'));
        
        include 'app/Views/layouts/header.php';
        include 'app/Views/groups/index.php';
        include 'app/Views/layouts/footer.php';
    }
    
    private function getTestGroups() {
        return [
            [
                'id' => 1,
                'name' => 'Conseil d\'administration',
                'description' => 'Groupe réservé aux membres du conseil d\'administration',
                'level' => 'avancé',
                'memberCount' => 5,
                'status' => 'active',
                'is_private' => true
            ],
            [
                'id' => 2,
                'name' => 'Club',
                'description' => 'Groupe principal du club d\'archers',
                'level' => 'tous niveaux',
                'memberCount' => 25,
                'status' => 'active',
                'is_private' => false
            ],
            [
                'id' => 3,
                'name' => 'Compétiteurs',
                'description' => 'Groupe des archers participant aux compétitions',
                'level' => 'avancé',
                'memberCount' => 12,
                'status' => 'active',
                'is_private' => false
            ],
            [
                'id' => 4,
                'name' => 'Jeunes',
                'description' => 'Groupe des jeunes archers (moins de 18 ans)',
                'level' => 'débutant',
                'memberCount' => 8,
                'status' => 'active',
                'is_private' => false
            ],
            [
                'id' => 5,
                'name' => 'Intermédiaires',
                'description' => 'Groupe des archers de niveau intermédiaire',
                'level' => 'intermédiaire',
                'memberCount' => 15,
                'status' => 'active',
                'is_private' => false
            ]
        ];
    }
    
    public function show($id) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }
        
        $group = null;
        $error = null;
        $chatMessages = [];
        $chatError = null;
        
        try {
            // Vérifier si l'utilisateur est admin et utiliser des données de test
            $isAdmin = isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'] === true;
            $isDemoToken = isset($_SESSION['token']) && strpos($_SESSION['token'], 'demo-token-') === 0;
            
            if ($isAdmin && $isDemoToken) {
                // Utiliser des données de test pour l'admin
                $testGroups = $this->getTestGroups();
                $group = array_filter($testGroups, function($g) use ($id) {
                    return $g['id'] == $id;
                });
                $group = reset($group);
                
                if (!$group) {
                    $error = 'Groupe non trouvé';
                }
            } else {
                // Utiliser l'API pour récupérer les détails du groupe
                $response = $this->apiService->getGroupDetails($id);
                
                if ($response['success']) {
                    $group = $response['data'];
                } else {
                    $error = $response['message'] ?? 'Erreur lors de la récupération du groupe';
                }
            }
            
            // Charger les messages du chat pour ce groupe
            if ($group && !$error) {
                try {
                    $messagesResponse = $this->apiService->makeRequest("messages/{$id}/history", "GET");
                    
                    if ($messagesResponse['success']) {
                        $chatMessages = $messagesResponse['data'];
                    } else {
                        $chatError = $messagesResponse['message'] ?? 'Erreur lors de la récupération des messages';
                    }
                } catch (Exception $e) {
                    $chatError = 'Erreur lors de la récupération des messages: ' . $e->getMessage();
                }
            }
        } catch (Exception $e) {
            $error = 'Erreur lors de la récupération du groupe';
        }
        
        $title = 'Détails du groupe - Portail Archers de Gémenos';
        
        include 'app/Views/layouts/header.php';
        include 'app/Views/groups/show.php';
        include 'app/Views/layouts/footer.php';
    }
    
    public function create() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }
        
        $title = 'Créer un groupe - Portail Archers de Gémenos';
        
        include 'app/Views/layouts/header.php';
        include 'app/Views/groups/create.php';
        include 'app/Views/layouts/footer.php';
    }
    
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /groups');
            exit;
        }
        
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $level = $_POST['level'] ?? 'débutant';
        $isPrivate = isset($_POST['is_private']);
        
        if (empty($name)) {
            $_SESSION['error'] = 'Le nom du groupe est requis';
            header('Location: /groups/create');
            exit;
        }
        
        try {
            // Vérifier si l'utilisateur est admin et utiliser des données de test
            $isAdmin = isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'] === true;
            $isDemoToken = isset($_SESSION['token']) && strpos($_SESSION['token'], 'demo-token-') === 0;
            
            if ($isAdmin && $isDemoToken) {
                // Simulation de création pour l'admin
                $_SESSION['success'] = 'Groupe créé avec succès (mode démonstration)';
                header('Location: /groups');
                exit;
            } else {
                // Utiliser l'API pour créer le groupe
                $response = $this->apiService->createGroup([
                    'name' => $name,
                    'description' => $description,
                    'level' => $level,
                    'is_private' => $isPrivate
                ]);
                
                if ($response['success']) {
                    $_SESSION['success'] = 'Groupe créé avec succès';
                    header('Location: /groups');
                    exit;
                } else {
                    $_SESSION['error'] = $response['message'] ?? 'Erreur lors de la création du groupe';
                    header('Location: /groups/create');
                    exit;
                }
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erreur lors de la création du groupe';
            header('Location: /groups/create');
            exit;
        }
    }
    
    public function edit($id) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }
        
        $group = null;
        $error = null;
        
        try {
            // Vérifier si l'utilisateur est admin et utiliser des données de test
            $isAdmin = isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'] === true;
            $isDemoToken = isset($_SESSION['token']) && strpos($_SESSION['token'], 'demo-token-') === 0;
            
            if ($isAdmin && $isDemoToken) {
                // Utiliser des données de test pour l'admin
                $testGroups = $this->getTestGroups();
                $group = array_filter($testGroups, function($g) use ($id) {
                    return $g['id'] == $id;
                });
                $group = reset($group);
                
                if (!$group) {
                    $error = 'Groupe non trouvé';
                }
            } else {
                // Utiliser l'API pour récupérer les détails du groupe
                $response = $this->apiService->getGroupDetails($id);
                
                if ($response['success']) {
                    $group = $response['data'];
                } else {
                    $error = $response['message'] ?? 'Erreur lors de la récupération du groupe';
                }
            }
        } catch (Exception $e) {
            $error = 'Erreur lors de la récupération du groupe';
        }
        
        $title = 'Modifier le groupe - Portail Archers de Gémenos';
        
        include 'app/Views/layouts/header.php';
        include 'app/Views/groups/edit.php';
        include 'app/Views/layouts/footer.php';
    }
    
    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /groups');
            exit;
        }
        
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $level = $_POST['level'] ?? 'débutant';
        $isPrivate = isset($_POST['is_private']);
        
        if (empty($name)) {
            $_SESSION['error'] = 'Le nom du groupe est requis';
            header('Location: /groups/' . $id . '/edit');
            exit;
        }
        
        try {
            // Vérifier si l'utilisateur est admin et utiliser des données de test
            $isAdmin = isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'] === true;
            $isDemoToken = isset($_SESSION['token']) && strpos($_SESSION['token'], 'demo-token-') === 0;
            
            if ($isAdmin && $isDemoToken) {
                // Simulation de modification pour l'admin
                $_SESSION['success'] = 'Groupe modifié avec succès (mode démonstration)';
                header('Location: /groups');
                exit;
            } else {
                // Utiliser l'API pour modifier le groupe
                $response = $this->apiService->updateGroup($id, [
                    'name' => $name,
                    'description' => $description,
                    'level' => $level,
                    'is_private' => $isPrivate
                ]);
                
                if ($response['success']) {
                    $_SESSION['success'] = 'Groupe modifié avec succès';
                    header('Location: /groups');
                    exit;
                } else {
                    $_SESSION['error'] = $response['message'] ?? 'Erreur lors de la modification du groupe';
                    header('Location: /groups/' . $id . '/edit');
                    exit;
                }
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erreur lors de la modification du groupe';
            header('Location: /groups/' . $id . '/edit');
            exit;
        }
    }
    
    public function delete($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /groups');
            exit;
        }
        
        $confirm = $_POST['confirm'] ?? '';
        
        if ($confirm !== 'yes') {
            $_SESSION['error'] = 'Confirmation requise pour supprimer le groupe';
            header('Location: /groups');
            exit;
        }
        
        try {
            // Vérifier si l'utilisateur est admin et utiliser des données de test
            $isAdmin = isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'] === true;
            $isDemoToken = isset($_SESSION['token']) && strpos($_SESSION['token'], 'demo-token-') === 0;
            
            if ($isAdmin && $isDemoToken) {
                // Simulation de suppression pour l'admin
                $_SESSION['success'] = 'Groupe supprimé avec succès (mode démonstration)';
                header('Location: /groups');
                exit;
            } else {
                // Utiliser l'API pour supprimer le groupe
                $response = $this->apiService->deleteGroup($id);
                
                if ($response['success']) {
                    $_SESSION['success'] = 'Groupe supprimé avec succès';
                    header('Location: /groups');
                    exit;
                } else {
                    $_SESSION['error'] = $response['message'] ?? 'Erreur lors de la suppression du groupe';
                    header('Location: /groups');
                    exit;
                }
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erreur lors de la suppression du groupe';
            header('Location: /groups');
            exit;
        }
    }
}
