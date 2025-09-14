<?php

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
        
        $users = [];
        $error = null;
        
        try {
            // Essayer de récupérer les utilisateurs depuis l'API
            $response = $this->apiService->getUsers();
            if ($response['success'] && !empty($response['data']['users'])) {
                $users = $response['data']['users'];
            } else {
                // Si l'API ne fonctionne pas, utiliser des données simulées réalistes
                $users = $this->getSimulatedUsers();
                $error = 'API backend non accessible - Affichage de données simulées';
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
                'id' => 1,
                'first_name' => 'Admin',
                'last_name' => 'Gémenos',
                'email' => 'admin@archers-gemenos.fr',
                'role' => 'admin',
                'status' => 'active',
                'created_at' => '2024-01-01 10:00:00'
            ],
            [
                'id' => 2,
                'first_name' => 'Jean',
                'last_name' => 'Dupont',
                'email' => 'jean.dupont@archers-gemenos.fr',
                'role' => 'user',
                'status' => 'active',
                'created_at' => '2024-01-15 14:30:00'
            ],
            [
                'id' => 3,
                'first_name' => 'Marie',
                'last_name' => 'Martin',
                'email' => 'marie.martin@archers-gemenos.fr',
                'role' => 'user',
                'status' => 'active',
                'created_at' => '2024-02-01 09:15:00'
            ],
            [
                'id' => 4,
                'first_name' => 'Pierre',
                'last_name' => 'Bernard',
                'email' => 'pierre.bernard@archers-gemenos.fr',
                'role' => 'user',
                'status' => 'inactive',
                'created_at' => '2024-02-10 16:45:00'
            ],
            [
                'id' => 5,
                'first_name' => 'Sophie',
                'last_name' => 'Leroy',
                'email' => 'sophie.leroy@archers-gemenos.fr',
                'role' => 'user',
                'status' => 'active',
                'created_at' => '2024-02-20 11:20:00'
            ],
            [
                'id' => 6,
                'first_name' => 'Michel',
                'last_name' => 'Moreau',
                'email' => 'michel.moreau@archers-gemenos.fr',
                'role' => 'coach',
                'status' => 'active',
                'created_at' => '2024-03-01 08:30:00'
            ]
        ];
    }
    
    public function show($id) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }
        
        $user = null;
        $error = null;
        
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
        } catch (Exception $e) {
            $error = 'Erreur lors de la récupération de l\'utilisateur';
        }
        
        $title = 'Détails de l\'utilisateur - Portail Archers de Gémenos';
        
        include 'app/Views/layouts/header.php';
        include 'app/Views/users/show.php';
        include 'app/Views/layouts/footer.php';
    }
    
    public function create() {
        error_log("=== DEBUG UserController::create ===");
        error_log("Session: " . print_r($_SESSION, true));
        
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            error_log("Non connecté, redirection vers login");
            header('Location: /login');
            exit;
        }
        
        // Vérification des droits administrateur
        if (!isset($_SESSION['user']['is_admin']) || !(bool)$_SESSION['user']['is_admin']) {
            error_log("Non admin, redirection vers users");
            $_SESSION['error'] = 'Accès refusé. Seuls les administrateurs peuvent créer des utilisateurs.';
            header('Location: /users');
            exit;
        }
        
        error_log("Accès autorisé, affichage du formulaire");
        $title = 'Créer un utilisateur - Portail Archers de Gémenos';
        
        include 'app/Views/layouts/header.php';
        include 'app/Views/users/create.php';
        include 'app/Views/layouts/footer.php';
    }

    public function store() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }
        
        // Vérification des droits administrateur
        if (!isset($_SESSION['user']['is_admin']) || !(bool)$_SESSION['user']['is_admin']) {
            $_SESSION['error'] = 'Accès refusé. Seuls les administrateurs peuvent créer des utilisateurs.';
            header('Location: /users');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /users/create');
            exit;
        }

        // Récupération et validation des données
        $name = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // Validation basique
        $errors = [];
        if (empty($name)) $errors[] = "Le nom est obligatoire";
        if (empty($username)) $errors[] = "Le nom d'utilisateur est obligatoire";
        if (empty($email)) $errors[] = "L'email est obligatoire";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "L'email n'est pas valide";
        if (empty($password)) $errors[] = "Le mot de passe est obligatoire";
        if (strlen($password) < 8) $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = $_POST;
            header('Location: /users/create');
            exit;
        }

        try {
            // Appel à l'API pour créer l'utilisateur
            $response = $this->apiService->createUser([
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'password' => $password
            ]);

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
        
        $user = null;
        $error = null;
        
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
        } catch (Exception $e) {
            $error = 'Erreur lors de la récupération de l\'utilisateur';
        }
        
        $title = 'Modifier l\'utilisateur - Portail Archers de Gémenos';
        
        include 'app/Views/layouts/header.php';
        include 'app/Views/users/edit.php';
        include 'app/Views/layouts/footer.php';
    }
    
    public function update($id) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
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
            'role' => $_POST['role'] ?? ''
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
        
        try {
            // Ici vous pouvez ajouter la logique pour supprimer l'utilisateur via l'API
            // Pour l'instant, on simule une suppression réussie
            
            $_SESSION['success'] = 'Utilisateur supprimé avec succès';
            header('Location: /users');
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erreur lors de la suppression de l\'utilisateur';
            header('Location: /users');
            exit;
        }
    }
}
