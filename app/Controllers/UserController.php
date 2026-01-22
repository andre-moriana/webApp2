<?php

require_once 'app/Config/PermissionHelper.php';
require_once 'app/Services/PermissionService.php';

class UserController {
    private $apiService;
    
    public function __construct() {
        $this->apiService = new ApiService();
    }
    
    public function index() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }
        
        // Vérifier la permission de voir les utilisateurs
        $clubId = $_SESSION['user']['clubId'] ?? null;
        PermissionHelper::requirePermission(
            PermissionService::RESOURCE_USERS_ALL,
            PermissionService::ACTION_VIEW,
            $clubId
        );
        
        // Nettoyer les messages d'erreur de session
        unset($_SESSION['error']);
        unset($_SESSION['success']);
        
        $users = [];
        $error = null;
        
        try {
            // Récupérer les utilisateurs depuis l'API
            $response = $this->apiService->getUsers();
            
            error_log('UserController::index() - Réponse getUsers(): ' . json_encode($response, JSON_UNESCAPED_UNICODE));
            
            // Vérifier le format de la réponse
            // makeRequest retourne { success: bool, data: {...}, status_code: int }
            // où data contient la réponse JSON de l'API
            $apiResponse = $response['data'] ?? null;
            
            // L'API /users retourne directement un tableau d'utilisateurs, pas { users: [...] }
            if ($response['success'] && isset($apiResponse) && is_array($apiResponse)) {
                // Si apiResponse est directement un tableau, l'utiliser
                if (isset($apiResponse[0]) && is_array($apiResponse[0])) {
                    $users = $apiResponse;
                } elseif (isset($apiResponse['users']) && is_array($apiResponse['users'])) {
                    $users = $apiResponse['users'];
                } else {
                    // Format inattendu
                    error_log('Format de réponse inattendu pour getUsers(): ' . json_encode($apiResponse, JSON_UNESCAPED_UNICODE));
                    $users = [];
                }
                
                // Si on a des utilisateurs, les enrichir avec le nom complet du club
                if (!empty($users)) {
                    // Enrichir les utilisateurs avec le nom complet du club
                    // Utiliser EXACTEMENT la même méthode que dans show() qui fonctionne
                    try {
                        $clubsResponse = $this->apiService->makeRequest('clubs/list', 'GET');
                        if ($clubsResponse['success'] && isset($clubsResponse['data']) && is_array($clubsResponse['data'])) {
                            // Enrichir chaque utilisateur avec le nom complet du club (même logique que show())
                            foreach ($users as &$user) {
                                // Récupérer le clubNameShort de la même manière que dans show()
                                $clubNameShort = $user['club'] ?? $user['clubId'] ?? $user['club_id'] ?? null;
                                
                                if (!empty($clubNameShort)) {
                                    // Chercher le club dans la liste (même logique que show())
                                    foreach ($clubsResponse['data'] as $club) {
                                        $nameShort = $club['nameShort'] ?? $club['name_short'] ?? '';
                                        // Comparer exactement comme dans show() - par nameShort
                                        if ($nameShort === $clubNameShort) {
                                            $clubName = $club['name'] ?? '';
                                            if (!empty($clubName)) {
                                                $user['clubName'] = $clubName;
                                            }
                                            break;
                                        }
                                    }
                                }
                            }
                            unset($user); // Libérer la référence
                        }
                    } catch (Exception $e) {
                        // En cas d'erreur, continuer sans enrichissement
                        error_log('Erreur lors de l\'enrichissement des clubs: ' . $e->getMessage());
                    }
                } else {
                    // Aucun utilisateur trouvé mais l'API a répondu
                    error_log('Aucun utilisateur dans la réponse API');
                }
            } else {
                // L'API n'a pas répondu avec succès
                error_log('API backend non accessible - success=' . ($response['success'] ? 'true' : 'false') . ', status_code=' . ($response['status_code'] ?? 'N/A'));
                error_log('Réponse complète: ' . json_encode($response, JSON_UNESCAPED_UNICODE));
                $error = 'API backend non accessible - Affichage de données simulées';
                $users = $this->getSimulatedUsers();
            }
        } catch (Exception $e) {
            // En cas d'erreur, utiliser des données simulées
            $users = $this->getSimulatedUsers();
            $error = 'Erreur de connexion à l\'API - Affichage de données simulées';
        }
        
        $title = 'Gestion des utilisateurs - Portail Archers de Gémenos';
        
        include 'app/Views/layouts/header.php';
        include 'app/Views/users/index.php';
        include 'app/Views/layouts/footer.php';
    }
    
    /**
     * Retourne des utilisateurs simulés réalistes
     */
    private function getSimulatedUsers() {
        return [
            [
                "id" => 1,
                "first_name" => "Admin",
                "last_name" => "Gémenos",
                "name" => "Gémenos",
                "email" => "admin@archers-gemenos.fr",
                "role" => "admin",
                "status" => "active",
                "created_at" => "2024-01-01 10:00:00"
            ],
            [
                "id" => 2,
                "first_name" => "Jean",
                "last_name" => "Dupont",
                "name" => "Dupont",
                "email" => "jean.dupont@archers-gemenos.fr",
                "role" => "user",
                "status" => "active",
                "created_at" => "2024-01-15 14:30:00"
            ],
            [
                "id" => 3,
                "first_name" => "Marie",
                "last_name" => "Martin",
                "name" => "Martin",
                "email" => "marie.martin@archers-gemenos.fr",
                "role" => "user",
                "status" => "active",
                "created_at" => "2024-02-01 09:15:00"
            ]
        ];
    }
    
    public function show($id) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }
        
        // Vérifier la permission de voir cet utilisateur
        $clubId = $_SESSION['user']['clubId'] ?? null;
        $currentUserId = $_SESSION['user']['id'] ?? null;
        
        // Si c'est son propre profil, pas besoin de permission spéciale
        if ($currentUserId != $id) {
            // Sinon, vérifier la permission de voir les autres utilisateurs
            if (!PermissionHelper::canViewUser($id, $clubId)) {
                $_SESSION['error'] = 'Vous n\'avez pas la permission de voir cet utilisateur.';
                header('Location: /dashboard');
                exit;
            }
        }
        
        $user = null;
        $error = null;
        $clubName = null;
        
        try {
            // Récupérer l'utilisateur depuis l'API
            $response = $this->apiService->getUsers();
            if ($response['success'] && !empty($response['data']['users'])) {
                foreach ($response['data']['users'] as $u) {
                    if ($u['id'] == $id) {
                        $user = $u;
                        break;
                    }
                }
            }
            
            if (!$user) {
                $error = 'Utilisateur non trouvé';
            } else {
                // Récupérer le nom complet du club si l'utilisateur a un club
                $clubNameShort = $user['club'] ?? $user['clubId'] ?? $user['club_id'] ?? null;
                if (!empty($clubNameShort)) {
                    try {
                        $clubsResponse = $this->apiService->makeRequest('clubs/list', 'GET');
                        if ($clubsResponse['success'] && isset($clubsResponse['data']) && is_array($clubsResponse['data'])) {
                            foreach ($clubsResponse['data'] as $club) {
                                $nameShort = $club['nameShort'] ?? $club['name_short'] ?? '';
                                if ($nameShort === $clubNameShort) {
                                    $clubName = $club['name'] ?? '';
                                    break;
                                }
                            }
                        }
                    } catch (Exception $e) {
                        // En cas d'erreur, on garde juste le name_short
                        error_log('Erreur lors de la récupération du nom du club: ' . $e->getMessage());
                    }
                }
            }
        } catch (Exception $e) {
            $error = 'Erreur lors de la récupération de l\'utilisateur';
        }
        
        $title = 'Détails de l\'utilisateur - Portail Archers de Gémenos';
        
        include 'app/Views/layouts/header.php';
        include 'app/Views/users/show.php';
        include 'app/Views/layouts/footer.php';
    }
    
    public function create() {
        
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }
        
        // Vérifier la permission de créer des utilisateurs
        $clubId = $_SESSION['user']['clubId'] ?? null;
        PermissionHelper::requirePermission(
            PermissionService::RESOURCE_USERS_ALL,
            PermissionService::ACTION_CREATE,
            $clubId
        );
        
        $title = 'Créer un utilisateur - Portail Archers de Gémenos';
        
        // Définir les fichiers JS spécifiques
        $additionalJS = ['/public/assets/js/user-create.js'];
        
        include 'app/Views/layouts/header.php';
        include 'app/Views/users/create.php';
        include 'app/Views/layouts/footer.php';
    }

    public function store() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }
        
        // Vérifier la permission de créer des utilisateurs
        $clubId = $_SESSION['user']['clubId'] ?? null;
        PermissionHelper::requirePermission(
            PermissionService::RESOURCE_USERS_ALL,
            PermissionService::ACTION_CREATE,
            $clubId
        );

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /users/create');
            exit;
        }

        // Récupération et validation des données
        $first_name = trim($_POST['first_name'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $licenceNumber = trim($_POST['licenceNumber'] ?? '');

        // Validation basique
        $errors = [];
        if (empty($first_name)) $errors[] = "Le prénom est obligatoire";
        if (empty($name)) $errors[] = "Le nom est obligatoire";
        if (empty($username)) $errors[] = "Le nom d'utilisateur est obligatoire";
        if (empty($email)) $errors[] = "L'email est obligatoire";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "L'email n'est pas valide";
        if (empty($password)) $errors[] = "Le mot de passe est obligatoire";
        if (strlen($password) < 6) $errors[] = "Le mot de passe doit contenir au moins 6 caractères";
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = $_POST;
            header('Location: /users/create');
            exit;
        }

        try {
            // Appel à l'API pour créer l'utilisateur
            $userData = [
                'first_name' => $first_name,
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'password' => $password
            ];
            
            // Ajouter le numéro de licence si fourni
            if (!empty($licenceNumber)) {
                $userData['licenceNumber'] = $licenceNumber;
            }
            
            $response = $this->apiService->createUser($userData);

            if ($response['success']) {
                $_SESSION['success'] = 'Utilisateur créé avec succès';
                header('Location: /users');
                exit;
            } else {
                $_SESSION['error'] = 'Erreur lors de la création de l\'utilisateur';
                $_SESSION['old_input'] = $_POST;
                header('Location: /users/create');
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erreur lors de la création de l\'utilisateur';
            $_SESSION['old_input'] = $_POST;
            header('Location: /users/create');
            exit;
        }
    }
    
    public function edit($id) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }
        
        // Vérifier la permission de modifier cet utilisateur
        $clubId = $_SESSION['user']['clubId'] ?? null;
        $currentUserId = $_SESSION['user']['id'] ?? null;
        
        // Si c'est son propre profil, utiliser la permission users_self_edit
        if ($currentUserId == $id) {
            PermissionHelper::requirePermission(
                PermissionService::RESOURCE_USERS_SELF,
                PermissionService::ACTION_EDIT,
                $clubId
            );
        } else {
            // Sinon, vérifier la permission de modifier les autres utilisateurs
            if (!PermissionHelper::canEditUser($id, $clubId)) {
                $_SESSION['error'] = 'Vous n\'avez pas la permission de modifier cet utilisateur.';
                header('Location: /users');
                exit;
            }
        }
        
        $user = null;
        $error = null;
        $clubs = [];
        
        try {
            // Récupérer l'utilisateur depuis l'API
            $response = $this->apiService->getUsers();
            if ($response['success'] && !empty($response['data']['users'])) {
                foreach ($response['data']['users'] as $u) {
                    if ($u['id'] == $id) {
                        $user = $u;
                        break;
                    }
                }
            }
            
            if (!$user) {
                $error = 'Utilisateur non trouvé';
            }
            
            // Récupérer la liste des clubs (filtrer ceux qui ne finissent pas par "000")
            $clubsResponse = $this->apiService->makeRequest('clubs/list', 'GET');
            if ($clubsResponse['success'] && isset($clubsResponse['data']) && is_array($clubsResponse['data'])) {
                foreach ($clubsResponse['data'] as $club) {
                    $nameShort = $club['nameShort'] ?? $club['name_short'] ?? '';
                    // Filtrer les clubs dont le name_short ne finit pas par "000"
                    if (!empty($nameShort) && substr($nameShort, -3) !== '000') {
                        $clubs[] = [
                            'nameShort' => $nameShort,
                            'name' => $club['name'] ?? ''
                        ];
                    }
                }
                // Trier les clubs par nom
                usort($clubs, function($a, $b) {
                    return strcmp($a['name'], $b['name']);
                });
            }
        } catch (Exception $e) {
            $error = 'Erreur lors de la récupération de l\'utilisateur';
        }
        
        $title = 'Modifier l\'utilisateur - Portail Archers de Gémenos';
        
        // Définir les fichiers JS spécifiques
        $additionalJS = ['/public/assets/js/user-edit.js'];
        
        include 'app/Views/layouts/header.php';
        include 'app/Views/users/edit.php';
        include 'app/Views/layouts/footer.php';
    }
    
    public function update($id) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }
        
        // Vérifier la permission de modifier cet utilisateur
        $clubId = $_SESSION['user']['clubId'] ?? null;
        $currentUserId = $_SESSION['user']['id'] ?? null;
        
        // Si c'est son propre profil, utiliser la permission users_self_edit
        if ($currentUserId == $id) {
            PermissionHelper::requirePermission(
                PermissionService::RESOURCE_USERS_SELF,
                PermissionService::ACTION_EDIT,
                $clubId
            );
        } else {
            // Sinon, vérifier la permission de modifier les autres utilisateurs
            if (!PermissionHelper::canEditUser($id, $clubId)) {
                $_SESSION['error'] = 'Vous n\'avez pas la permission de modifier cet utilisateur.';
                header('Location: /users');
                exit;
            }
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /users/' . $id . '/edit');
            exit;
        }
        
        // Récupération des données du formulaire
        $userData = [
            'firstName' => $_POST['firstName'] ?? '',
            'name' => $_POST['name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'username' => $_POST['username'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'gender' => $_POST['gender'] ?? '',
            'birthDate' => $_POST['birthDate'] ?? '',
            'licenceNumber' => $_POST['licenceNumber'] ?? '',
            'ageCategory' => $_POST['ageCategory'] ?? '',
            'arrivalYear' => $_POST['arrivalYear'] ?? '',
            'bowType' => $_POST['bowType'] ?? '',
            'clubId' => $_POST['clubId'] ?? '',
            'role' => $_POST['role'] ?? '',
            'is_admin' => $_POST['is_admin'] ?? '0',
            'is_banned' => $_POST['is_banned'] ?? '0',
            'status' => $_POST['status'] ?? 'active'
        ];
        
        // Validation basique
        if (empty($userData['email'])) {
            $_SESSION['error'] = 'L\'email est obligatoire';
            header('Location: /users/' . $id . '/edit');
            exit;
        }
        
        try {
            // Appel à l'API pour mettre à jour l'utilisateur
            $response = $this->apiService->updateUser($id, $userData);
            
            if ($response['success']) {
                $_SESSION['success'] = 'Utilisateur mis à jour avec succès';
                header('Location: /users/' . $id);
                exit;
            } else {
                $_SESSION['error'] = $response['message'] ?? 'Erreur lors de la mise à jour de l\'utilisateur';
                header('Location: /users/' . $id . '/edit');
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erreur lors de la mise à jour de l\'utilisateur';
            header('Location: /users/' . $id . '/edit');
            exit;
        }
    }
    
    public function destroy($id) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }
        
        // Vérifier la permission de supprimer des utilisateurs
        $clubId = $_SESSION['user']['clubId'] ?? null;
        PermissionHelper::requirePermission(
            PermissionService::RESOURCE_USERS_ALL,
            PermissionService::ACTION_DELETE,
            $clubId
        );
        
        try {
            // Appel à l'API pour supprimer l'utilisateur
            $response = $this->apiService->deleteUser($id);
            
            if ($response['success']) {
                $_SESSION['success'] = 'Utilisateur supprimé avec succès';
            } else {
                $_SESSION['error'] = $response['message'] ?? 'Erreur lors de la suppression de l\'utilisateur';
            }
            
            header('Location: /users');
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erreur lors de la suppression de l\'utilisateur: ' . $e->getMessage();
            header('Location: /users');
            exit;
        }
    }
}
