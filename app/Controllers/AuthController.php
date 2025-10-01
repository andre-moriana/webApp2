<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class AuthController {
    private $apiService;

    public function __construct() {
        // Ne pas instancier ApiService ici pour éviter les problèmes d'autoload
        $this->apiService = null;
    }

    private function getApiService() {
        if ($this->apiService === null) {
            $this->apiService = new ApiService();
        }
        return $this->apiService;
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            error_log("Tentative de connexion pour l'utilisateur: " . $username);
            
            $result = $this->apiService->login($username, $password);
            error_log("Résultat de la connexion: " . print_r($result, true));
            
            if ($result['success']) {
                // Stocker les informations dans la session
                $_SESSION['logged_in'] = true;
                $_SESSION['user'] = $result['user'];
                $_SESSION['token'] = $result['token'];
                
                error_log("Session après connexion: " . print_r($_SESSION, true));
                
                // Rediriger vers le tableau de bord
                header('Location: /dashboard');
                exit;
            } else {
                $_SESSION['error'] = $result['message'] ?? 'Erreur de connexion';
                header('Location: /login');
                exit;
            }
        }
        
        // Afficher le formulaire de connexion
        include 'app/Views/auth/login.php';
    }

    public function authenticate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /login');
            exit;
        }

        $username = $_POST['email'] ?? ''; // On reçoit l'email du formulaire
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $_SESSION['error'] = 'Veuillez remplir tous les champs';
            header('Location: /login');
            exit;
        }

        try {
            // Extraire le nom d'utilisateur de l'email
            $username = explode('@', $username)[0];

            error_log("Tentative de connexion avec username: " . $username);
            
            // Créer une nouvelle instance de ApiService
            $apiService = new ApiService();
            
            // Utiliser l'API backend pour l'authentification
            $loginResult = $apiService->login($username, $password);
            error_log("Résultat de la connexion: " . json_encode($loginResult));

            if ($loginResult['success'] && isset($loginResult['token'])) {
                // Connexion réussie via l'API
                $_SESSION['user'] = [
                    'id' => $loginResult['user']['id'] ?? 1,
                    'last_name' => $loginResult['user']['name'] ?? '',
                    'username' => $loginResult['user']['username'] ?? '',
                    'email' => $username . '@archers-gemenos.fr',
                    'role' => $loginResult['user']['role'] ?? 'user',
                    'is_admin' => $loginResult['user']['is_admin'] ?? $loginResult['user']['isAdmin'] ?? false,
                    'status' => 'active'
                ];
                
                // Sauvegarder le token dans la session
                $_SESSION['token'] = $loginResult['token'];
                $_SESSION['logged_in'] = true;
                
                error_log("Session après connexion réussie: " . print_r($_SESSION, true));
                
                header('Location: /dashboard');
                exit;
            } else {
                // Échec de la connexion via l'API, essayer les identifiants de test
                if ($username === 'admin' && $password === 'admin1234') {
                    $_SESSION['user'] = [
                        'id' => 1,
                        'last_name' => 'Gémenos',
                        'username' => 'admin',
                        'email' => 'admin@archers-gemenos.fr',
                        'role' => 'admin',
                        'is_admin' => true,
                        'status' => 'active'
                    ];
                    $_SESSION['token'] = 'demo-token-' . time();
                    $_SESSION['logged_in'] = true;
                    
                    error_log("Connexion avec compte de test réussie");
                    
                    header('Location: /dashboard');
                    exit;
                } else {
                    error_log("Échec de connexion: " . ($loginResult['message'] ?? 'Identifiants incorrects'));
                    $_SESSION['error'] = $loginResult['message'] ?? 'Identifiants incorrects';
                    header('Location: /login');
                    exit;
                }
            }
        } catch (Exception $e) {
            error_log("Exception lors de la connexion: " . $e->getMessage());
            
            // En cas d'erreur API, utiliser les identifiants de test
            if ($username === 'admin' && $password === 'admin1234') {
                $_SESSION['user'] = [
                    'id' => 1,
                    'first_name' => 'Admin',
                    'last_name' => 'Gémenos',
                    'email' => 'admin@archers-gemenos.fr',
                    'role' => 'admin',
                    'is_admin' => true,
                    'status' => 'active'
                ];
                $_SESSION['token'] = 'demo-token-' . time();
                $_SESSION['logged_in'] = true;
                
                error_log("Connexion avec compte de test réussie après exception");
                
                header('Location: /dashboard');
                exit;
            } else {
                $_SESSION['error'] = 'Erreur de connexion à l\'API. Utilisez admin/admin1234';
                header('Location: /login');
                exit;
            }
        }
    }

    public function logout() {
        session_destroy();
        header('Location: /login');
        exit;
    }

    public function resetPassword() {
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            $_SESSION['error'] = 'Token de réinitialisation manquant';
            header('Location: /login');
            exit;
        }

        $title = 'Nouveau mot de passe - Portail Archers de Gémenos';
        include 'app/Views/layouts/header.php';
        include 'app/Views/auth/reset-password.php';
        include 'app/Views/layouts/footer.php';
    }

    public function updatePassword() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /login');
            exit;
        }

        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($token) || empty($password) || empty($confirmPassword)) {
            $_SESSION['error'] = 'Veuillez remplir tous les champs';
            header('Location: /auth/reset-password?token=' . urlencode($token));
            exit;
        }

        if ($password !== $confirmPassword) {
            $_SESSION['error'] = 'Les mots de passe ne correspondent pas';
            header('Location: /auth/reset-password?token=' . urlencode($token));
            exit;
        }

        try {
            $result = $this->getApiService()->resetPassword($token, $password);

            if ($result['success']) {
                $_SESSION['success'] = 'Mot de passe mis à jour avec succès';
                header('Location: /login');
            } else {
                $_SESSION['error'] = $result['message'] ?? 'Erreur lors de la mise à jour du mot de passe';
                header('Location: /auth/reset-password?token=' . urlencode($token));
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erreur lors de la mise à jour du mot de passe';
            header('Location: /auth/reset-password?token=' . urlencode($token));
        }

        exit;
    }

    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public function getCurrentUser() {
        return $_SESSION['user'] ?? null;
    }
}
?>