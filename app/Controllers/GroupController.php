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

        try {
            // Vérifier si l'utilisateur est admin et utiliser des données de test
            $isAdmin = isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'] === true;
            $isDemoToken = isset($_SESSION['token']) && strpos($_SESSION['token'], 'demo-token-') === 0;
            
            if ($isAdmin && $isDemoToken) {
                error_log("GroupController: Utilisation des données de test");
                $groups = $this->getTestGroups();
            } else {
                error_log("GroupController: Tentative de récupération des groupes via API");
                $response = $this->apiService->getGroups();
                
                if ($response['success'] && isset($response['data']['groups'])) {
                    $allGroups = $response['data']['groups'];
                    error_log("GroupController: " . count($allGroups) . " groupes reçus de l'API");
                    
                    // Filtrer les groupes selon les autorisations de l'utilisateur
                    $currentUserId = $_SESSION['user']['id'] ?? null;
                    $filteredGroups = [];
                    
                    foreach ($allGroups as $group) {
                        $isGroupPrivate = (bool)($group['is_private'] ?? false);
                        
                        if (!$isGroupPrivate) {
                            // Groupe public : accessible à tous
                            $filteredGroups[] = $group;
                        } else {
                            // Groupe privé : vérifier si l'utilisateur est membre ou admin
                            $isGroupAdmin = ($group['admin_id'] ?? null) == $currentUserId;
                            
                            if ($isGroupAdmin) {
                                // L'utilisateur est l'admin du groupe
                                $filteredGroups[] = $group;
                            } else {
                                // Vérifier si l'utilisateur est membre du groupe privé
                                $isMember = $this->checkUserGroupMembership($currentUserId, $group['id']);
                                if ($isMember) {
                                    $filteredGroups[] = $group;
                                }
                            }
                        }
                    }
                    
                    $groups = $filteredGroups;
                    error_log("GroupController: " . count($groups) . " groupes après filtrage");
                } else {
                    error_log("GroupController: Erreur API ou pas de données");
                    $groups = $this->getTestGroups();
                }
            }
        } catch (Exception $e) {
            error_log("GroupController: Exception - " . $e->getMessage());
            $groups = $this->getTestGroups();
        }

        // Charger les messages de chat pour chaque groupe
        $chatMessages = [];
        foreach ($groups as $group) {
            try {
                $messagesResponse = $this->apiService->getGroupMessages($group['id']);
                if ($messagesResponse['success'] && isset($messagesResponse['data']) && $messagesResponse['data'] !== null) {
                    $chatMessages[$group['id']] = $messagesResponse['data'];
                } else {
                    $chatMessages[$group['id']] = [];
                }
            } catch (Exception $e) {
                error_log("Erreur lors du chargement des messages pour le groupe " . $group['id'] . ": " . $e->getMessage());
                $chatMessages[$group['id']] = [];
            }
        }

        $title = 'Groupes - Portail Archers de Gémenos';
        
        include 'app/Views/layouts/header.php';
        include 'app/Views/groups/index.php';
        include 'app/Views/layouts/footer.php';
    }
    
    // Nouvelle méthode pour vérifier l'appartenance à un groupe
    private function checkUserGroupMembership($userId, $groupId) {
        try {
            $response = $this->apiService->checkGroupAccess($groupId);
            return $response['success'] && ($response['canAccess'] ?? false);
        } catch (Exception $e) {
            error_log("Erreur lors de la vérification d'accès au groupe $groupId: " . $e->getMessage());
            return false;
        }
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
        error_log("=== EDIT GROUP CALLED ===");
        error_log("ID: " . $id);
        error_log("REQUEST_URI: " . $_SERVER['REQUEST_URI']);
        error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
        
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            error_log("Utilisateur non connecté, redirection vers login");
            header('Location: /login');
            exit;
        }
        
        $group = null;
        $error = null;
        
        try {
            // Vérifier si l'utilisateur est admin et utiliser des données de test
            $isAdmin = isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'] === true;
            $isDemoToken = isset($_SESSION['token']) && strpos($_SESSION['token'], 'demo-token-') === 0;
            
            error_log("Is Admin: " . ($isAdmin ? 'oui' : 'non'));
            error_log("Is Demo Token: " . ($isDemoToken ? 'oui' : 'non'));
            
            if ($isAdmin && $isDemoToken) {
                error_log("Utilisation des données de test");
                // Utiliser des données de test pour l'admin
                $testGroups = $this->getTestGroups();
                $group = array_filter($testGroups, function($g) use ($id) {
                    return $g['id'] == $id;
                });
                $group = reset($group);
                
                if (!$group) {
                    $error = 'Groupe non trouvé';
                    error_log("Groupe non trouvé dans les données de test");
                } else {
                    error_log("Groupe trouvé dans les données de test: " . json_encode($group));
                }
            } else {
                error_log("Utilisation de l'API");
                // Utiliser l'API pour récupérer les détails du groupe
                $response = $this->apiService->getGroupDetails($id);
                error_log("Réponse API: " . json_encode($response));
                
                if ($response['success']) {
                    $group = $response['data'];
                    error_log("Groupe récupéré de l'API: " . json_encode($group));
                } else {
                    $error = $response['message'] ?? 'Erreur lors de la récupération du groupe';
                    error_log("Erreur API: " . $error);
                }
            }
        } catch (Exception $e) {
            $error = 'Erreur lors de la récupération du groupe';
            error_log("Exception: " . $e->getMessage());
        }
        
        // Si le groupe n'a pas été trouvé, rediriger vers la liste
        if (!$group) {
            error_log("Groupe non trouvé, redirection vers /groups");
            $_SESSION['error'] = $error ?? 'Groupe non trouvé';
            header('Location: /groups');
            exit;
        }
        
        error_log("Affichage du formulaire d'édition");
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
    
    public function destroy($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

    public function members($id) {
        error_log("=== MEMBERS METHOD CALLED ===");
        error_log("Group ID: " . $id);
        error_log("REQUEST_URI: " . $_SERVER['REQUEST_URI']);
        
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            error_log("User not logged in, redirecting to login");
            header('Location: /login');
            exit;
        }
        
        $group = null;
        $members = [];
        $error = null;
        
        try {
            // Récupérer les détails du groupe
            $isAdmin = isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'] === true;
            $isDemoToken = isset($_SESSION['token']) && strpos($_SESSION['token'], 'demo-token-') === 0;
            
            error_log("Is Admin: " . ($isAdmin ? 'yes' : 'no'));
            error_log("Is Demo Token: " . ($isDemoToken ? 'yes' : 'no'));
            
            if ($isAdmin && $isDemoToken) {
                error_log("Using test data");
                // Utiliser des données de test
                $testGroups = $this->getTestGroups();
                $group = array_filter($testGroups, function($g) use ($id) {
                    return $g['id'] == $id;
                });
                $group = reset($group);
                
                if ($group) {
                    error_log("Group found in test data: " . json_encode($group));
                } else {
                    error_log("Group not found in test data");
                }
                
                // Membres de test seulement pour les groupes privés
                if ($group && (bool)($group['is_private'] ?? false)) {
                    $members = [
                        ['id' => 1, 'name' => 'Admin', 'email' => 'admin@example.com', 'role' => 'admin'],
                        ['id' => 2, 'name' => 'Utilisateur 1', 'email' => 'user1@example.com', 'role' => 'member'],
                        ['id' => 3, 'name' => 'Utilisateur 2', 'email' => 'user2@example.com', 'role' => 'member']
                    ];
                    error_log("Test members loaded: " . count($members));
                } else {
                    error_log("Group is not private, no test members");
                }
            } else {
                error_log("Using API");
                // Utiliser l'API
                $groupResponse = $this->apiService->getGroupDetails($id);
                if ($groupResponse['success']) {
                    $group = $groupResponse['data'];
                    error_log("Group loaded from API: " . json_encode($group));
                }
                
                // Récupérer les membres du groupe seulement s'il est privé
                if ($group && (bool)($group['is_private'] ?? $group['isPrivate'] ?? false)) {
                    error_log("Group is private, loading members...");
                    $membersResponse = $this->apiService->getGroupMembers($id);
                    error_log("Members API response: " . json_encode($membersResponse));
                    if ($membersResponse['success']) {
                        // Mapper les données pour avoir le bon format
                        $members = [];
                        foreach ($membersResponse['data'] as $member) {
                            $members[] = [
                                'id' => $member['user_id'],
                                'name' => $member['name'],
                                'email' => $member['email'],
                                'username' => $member['username']
                            ];
                        }
                        error_log("Members loaded from API: " . count($members));
                    } else {
                        error_log("Failed to load members: " . ($membersResponse['message'] ?? 'Unknown error'));
                    }
                } else {
                    error_log("Group is not private, no members to load");
                }
            }
        } catch (Exception $e) {
            $error = 'Erreur lors de la récupération des données';
            error_log("Exception in members method: " . $e->getMessage());
        }
        
        if (!$group) {
            error_log("Group not found, redirecting to groups");
            $_SESSION['error'] = 'Groupe non trouvé';
            header('Location: /groups');
            exit;
        }
        
        // Vérifier que le groupe est privé
        if (!(bool)($group['is_private'] ?? $group['isPrivate'] ?? false)) {
            error_log("Group is not private, redirecting to groups");
            $_SESSION['error'] = 'La gestion des membres n\'est disponible que pour les groupes privés';
            header('Location: /groups');
            exit;
        }
        
        error_log("Displaying members page");
        // Nettoyer les messages de session après les avoir utilisés
        unset($_SESSION['error']);
        unset($_SESSION['success']);
        
        $title = 'Membres du groupe - ' . $group['name'];
        
        include 'app/Views/layouts/header.php';
        include 'app/Views/groups/members.php';
        include 'app/Views/layouts/footer.php';
    }
}
