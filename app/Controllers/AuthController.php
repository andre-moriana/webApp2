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
            
            
            $result = $this->apiService->login($username, $password);
            
            if ($result['success']) {
                // Stocker les informations dans la session
                $_SESSION['logged_in'] = true;
                $_SESSION['user'] = $result['user'];
                $_SESSION['token'] = $result['token'];
                
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
            // Gérer le username : si c'est un email, extraire la partie avant @, sinon utiliser tel quel
            $loginUsername = $username;
            if (strpos($username, '@') !== false) {
                // C'est un email, extraire la partie avant @
                $loginUsername = explode('@', $username)[0];
            }
            // Sinon, c'est déjà un username, on l'utilise tel quel
            
            // Créer une nouvelle instance de ApiService
            $apiService = new ApiService();
            
            // Utiliser l'API backend pour l'authentification avec le username
            $loginResult = $apiService->login($loginUsername, $password);

            if ($loginResult['success'] && isset($loginResult['token'])) {
                // Vérifier le statut de l'utilisateur
                $userStatus = $loginResult['user']['status'] ?? $loginResult['user']['user_status'] ?? 'active';
                $isApproved = $loginResult['user']['is_approved'] ?? $loginResult['user']['approved'] ?? true;
                
                // Vérifier si l'utilisateur est approuvé
                if ($userStatus === 'pending' || !$isApproved) {
                    $_SESSION['error'] = 'Votre compte est en attente de validation par un administrateur. Vous recevrez un email une fois votre compte activé.';
                    header('Location: /login');
                    exit;
                }
                
                // Connexion réussie via l'API - Stocker toutes les informations de l'utilisateur
                $userData = $loginResult['user'] ?? [];
                
                // Log pour débogage - voir ce que l'API retourne
                error_log("AuthController - Données utilisateur du login: " . json_encode($userData, JSON_PRETTY_PRINT));
                
                // Extraire l'ID - vérifier tous les formats possibles
                $userId = null;
                if (isset($userData['id'])) {
                    $userId = $userData['id'];
                } elseif (isset($userData['_id'])) {
                    $userId = $userData['_id'];
                } elseif (isset($userData['user_id'])) {
                    $userId = $userData['user_id'];
                }
                
                // Si pas d'ID dans les données, essayer de l'extraire du token JWT
                if (!$userId && isset($loginResult['token'])) {
                    try {
                        $tokenParts = explode('.', $loginResult['token']);
                        if (count($tokenParts) === 3) {
                            $payload = json_decode(base64_decode($tokenParts[1]), true);
                            $userId = $payload['user_id'] ?? $payload['id'] ?? $payload['userId'] ?? null;
                        }
                    } catch (Exception $e) {
                        error_log("Erreur décodage token dans AuthController: " . $e->getMessage());
                    }
                }
                
                if (!$userId) {
                    error_log("ATTENTION: Impossible de récupérer l'ID utilisateur lors de la connexion");
                    $userId = 1; // Fallback temporaire - devrait être corrigé
                }
                
                error_log("AuthController - ID utilisateur extrait: " . $userId);
                
                $_SESSION['user'] = [
                    'id' => $userId,
                    '_id' => $userId,
                    'firstName' => $userData['firstName'] ?? $userData['first_name'] ?? '',
                    'first_name' => $userData['first_name'] ?? $userData['firstName'] ?? '',
                    'name' => $userData['name'] ?? $userData['last_name'] ?? '',
                    'last_name' => $userData['last_name'] ?? $userData['name'] ?? '',
                    'username' => $userData['username'] ?? $loginUsername,
                    'email' => $userData['email'] ?? ($loginUsername . '@archers-gemenos.fr'),
                    'role' => $userData['role'] ?? 'user',
                    'is_admin' => $userData['is_admin'] ?? $userData['isAdmin'] ?? false,
                    'isAdmin' => $userData['isAdmin'] ?? $userData['is_admin'] ?? false,
                    'club_id' => $userData['club_id'] ?? $userData['clubId'] ?? null,
                    'clubId' => $userData['clubId'] ?? $userData['club_id'] ?? null,
                    'status' => $userStatus,
                    'phone' => $userData['phone'] ?? '',
                    'birthDate' => $userData['birthDate'] ?? $userData['birth_date'] ?? '',
                    'gender' => $userData['gender'] ?? '',
                    'licenceNumber' => $userData['licenceNumber'] ?? $userData['licence_number'] ?? '',
                    'ageCategory' => $userData['ageCategory'] ?? $userData['age_category'] ?? '',
                    'bowType' => $userData['bowType'] ?? $userData['bow_type'] ?? '',
                    'profileImage' => $userData['profileImage'] ?? $userData['profile_image'] ?? null,
                ];
                
                // Sauvegarder le token dans la session
                $_SESSION['token'] = $loginResult['token'];
                $_SESSION['logged_in'] = true;
                
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
                    
                    header('Location: /dashboard');
                    exit;
                } else {
                    $_SESSION['error'] = $loginResult['message'] ?? 'Identifiants incorrects';
                    header('Location: /login');
                    exit;
                }
            }
        } catch (Exception $e) {
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
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            return false;
        }
        
        // Vérifier que l'utilisateur est approuvé
        $user = $_SESSION['user'] ?? null;
        if (!$user) {
            return false;
        }
        
        $userStatus = $user['status'] ?? 'active';
        $isApproved = $user['is_approved'] ?? true;
        
        // Si l'utilisateur est en attente ou non approuvé, le déconnecter
        if ($userStatus === 'pending' || !$isApproved) {
            $this->logout();
            return false;
        }
        
        return true;
    }

    public function getCurrentUser() {
        return $_SESSION['user'] ?? null;
    }

    /**
     * Vérifie si l'utilisateur actuel est approuvé
     */
    public function isUserApproved() {
        $user = $this->getCurrentUser();
        if (!$user) {
            return false;
        }
        
        $userStatus = $user['status'] ?? 'active';
        $isApproved = $user['is_approved'] ?? true;
        
        return $userStatus !== 'pending' && $isApproved;
    }

    /**
     * Vérifie l'authentification et l'approbation de l'utilisateur
     * Redirige vers la page de connexion si non authentifié ou non approuvé
     */
    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            $_SESSION['error'] = 'Vous devez être connecté pour accéder à cette page.';
            header('Location: /login');
            exit;
        }
        
        if (!$this->isUserApproved()) {
            $_SESSION['error'] = 'Votre compte est en attente de validation par un administrateur.';
            $this->logout();
            exit;
        }
    }

    public function register() {
        // Afficher le formulaire d'inscription
        include 'app/Views/auth/register.php';
    }

    public function createUser() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /auth/register');
            exit;
        }

        $first_name = $_POST['first_name'] ?? '';
        $name = $_POST['name'] ?? '';
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $licenceNumber = trim($_POST['licenceNumber'] ?? '');
        $clubId = trim($_POST['clubId'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $ageCategory = trim($_POST['ageCategory'] ?? '');
        $birthDate = trim($_POST['birthDate'] ?? '');
        $bowType = trim($_POST['bowType'] ?? '');

        // Validation des champs obligatoires (role n'est plus requis, sera défini automatiquement)
        if (empty($first_name) || empty($name) || empty($username) || empty($email) || empty($password)) {
            $_SESSION['error'] = 'Veuillez remplir tous les champs obligatoires';
            header('Location: /auth/register');
            exit;
        }

        if ($password !== $confirm_password) {
            $_SESSION['error'] = 'Les mots de passe ne correspondent pas';
            header('Location: /auth/register');
            exit;
        }

        if (strlen($password) < 6) {
            $_SESSION['error'] = 'Le mot de passe doit contenir au moins 6 caractères';
            header('Location: /auth/register');
            exit;
        }

        try {
            // Créer une nouvelle instance de ApiService
            $apiService = new ApiService();
            
            // Préparer les données pour l'API backend
            $userData = [
                'first_name' => $first_name,
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'role' => 'Archer', // Rôle par défaut pour les nouveaux utilisateurs
                'status' => 'pending'
            ];
            
            // Ajouter le numéro de licence si fourni
            if (!empty($licenceNumber)) {
                $userData['licenceNumber'] = $licenceNumber;
            }
            
            // Ajouter les champs optionnels
            if (!empty($clubId)) {
                $userData['clubId'] = $clubId;
            }
            if (!empty($gender)) {
                $userData['gender'] = $gender;
            }
            if (!empty($ageCategory)) {
                $userData['ageCategory'] = $ageCategory;
            }
            if (!empty($birthDate)) {
                $userData['birthDate'] = $birthDate;
            }
            if (!empty($bowType)) {
                $userData['bowType'] = $bowType;
            }

            // Log pour debug
            error_log("DEBUG AuthController createUser - Données envoyées: " . json_encode($userData, JSON_PRETTY_PRINT));
            
            // Appeler l'API backend pour créer l'utilisateur
            $result = $apiService->createUser($userData);
            
            // Log pour debug
            error_log("DEBUG AuthController createUser - Réponse API: " . json_encode($result, JSON_PRETTY_PRINT));

            if ($result['success']) {
                $_SESSION['success'] = 'Demande d\'inscription envoyée avec succès ! Votre compte sera activé après validation par un administrateur.';
                header('Location: /login');
                exit;
            } else {
                // Récupérer le message d'erreur détaillé de l'API
                $errorMessage = 'Erreur lors de la création de l\'utilisateur';
                if (isset($result['data'])) {
                    if (is_array($result['data'])) {
                        if (isset($result['data']['message'])) {
                            $errorMessage = $result['data']['message'];
                        } elseif (isset($result['data']['error'])) {
                            $errorMessage = $result['data']['error'];
                        } elseif (isset($result['data']['errors'])) {
                            $errorMessage = is_array($result['data']['errors']) 
                                ? implode(', ', $result['data']['errors']) 
                                : $result['data']['errors'];
                        }
                    } elseif (is_string($result['data'])) {
                        $errorMessage = $result['data'];
                    }
                } elseif (isset($result['message'])) {
                    $errorMessage = $result['message'];
                }
                
                error_log("DEBUG AuthController createUser - Message d'erreur: " . $errorMessage);
                $_SESSION['error'] = $errorMessage;
                header('Location: /auth/register');
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erreur lors de la création de l\'utilisateur: ' . $e->getMessage();
            header('Location: /auth/register');
            exit;
        }
    }
}