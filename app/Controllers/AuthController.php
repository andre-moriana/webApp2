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

        $username = trim((string)($_POST['email'] ?? '')); // Champ identifiant (username ou numéro de licence)
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $_SESSION['error'] = 'Veuillez remplir tous les champs';
            header('Location: /login');
            exit;
        }

        try {
            // Normaliser le numéro de licence à 8 caractères si 7 chiffres (ex: 1234567 -> 01234567)
            $loginUsername = $username;
            if (ctype_digit($username) && strlen($username) === 7) {
                $loginUsername = '0' . $username;
            }
            
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
                $_SESSION['last_activity'] = time(); // Initialiser le timestamp d'activité
                
                $returnUrl = $_POST['return'] ?? $_GET['return'] ?? '';
                if (!empty($returnUrl) && preg_match('#^/[a-zA-Z0-9/_-]+$#', $returnUrl)) {
                    header('Location: ' . $returnUrl);
                } else {
                    header('Location: /dashboard');
                }
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
                    $_SESSION['last_activity'] = time(); // Initialiser le timestamp d'activité
                    
                    $returnUrl = $_POST['return'] ?? $_GET['return'] ?? '';
                    if (!empty($returnUrl) && preg_match('#^/[a-zA-Z0-9/_-]+$#', $returnUrl)) {
                        header('Location: ' . $returnUrl);
                    } else {
                        header('Location: /dashboard');
                    }
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
                $_SESSION['last_activity'] = time(); // Initialiser le timestamp d'activité
                $_SESSION['logged_in'] = true;
                
                $returnUrl = $_POST['return'] ?? $_GET['return'] ?? '';
                if (!empty($returnUrl) && preg_match('#^/[a-zA-Z0-9/_-]+$#', $returnUrl)) {
                    header('Location: ' . $returnUrl);
                } else {
                    header('Location: /dashboard');
                }
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
            $returnUrl = $_SERVER['REQUEST_URI'] ?? '';
            $loginUrl = '/login' . (!empty($returnUrl) ? '?return=' . urlencode($returnUrl) : '');
            header('Location: ' . $loginUrl);
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

    public function deleteAccount() {
        // Afficher le formulaire de demande de suppression de compte
        include 'app/Views/auth/delete-account.php';
    }

    public function deleteAccountRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /auth/delete-account');
            exit;
        }

        $email = $_POST['email'] ?? '';
        $reason = $_POST['reason'] ?? '';
        $confirmDelete = isset($_POST['confirmDelete']);

        if (empty($email)) {
            $_SESSION['error'] = 'Veuillez saisir votre email ou identifiant';
            header('Location: /auth/delete-account');
            exit;
        }

        if (!$confirmDelete) {
            $_SESSION['error'] = 'Veuillez confirmer que vous souhaitez supprimer votre compte';
            header('Location: /auth/delete-account');
            exit;
        }

        try {
            // Appeler l'API backend pour créer la demande (l'API envoie aussi les emails)
            require_once __DIR__ . '/../Services/ApiService.php';
            $apiService = new ApiService();
            
            // Construire l'URL de base pour la validation
            $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") 
                . "://" . $_SERVER['HTTP_HOST'];
            
            $response = $apiService->makeRequest('users/deletion-request', 'POST', [
                'email' => $email,
                'reason' => $reason,
                'base_url' => $baseUrl
            ]);
            
            if ($response && isset($response['success']) && $response['success']) {
                $token = $response['data']['token'] ?? null;
                $emailSent = $response['data']['email_sent'] ?? false;
                
                // Log de la demande
                $logMessage = sprintf(
                    "[%s] Demande de suppression de compte - Email: %s - Token: %s - Raison: %s - Emails envoyés: %s\n",
                    date('Y-m-d H:i:s'),
                    $email,
                    $token ?? 'N/A',
                    $reason ?: 'Non spécifiée',
                    $emailSent ? 'OUI' : 'NON'
                );
                
                $logsDir = __DIR__ . '/../../logs';
                if (!file_exists($logsDir)) {
                    mkdir($logsDir, 0755, true);
                }
                
                file_put_contents(
                    $logsDir . '/account-deletion-requests.log',
                    $logMessage,
                    FILE_APPEND
                );

                $_SESSION['success'] = 'Votre demande de suppression de compte a été enregistrée. Vous allez recevoir un email de confirmation avec un lien pour valider définitivement la suppression. Vérifiez vos spams si vous ne le recevez pas.';
            } else {
                $_SESSION['error'] = $response['data']['message'] ?? $response['message'] ?? 'Erreur lors de l\'enregistrement de votre demande.';
            }
            
            header('Location: /auth/delete-account');
            exit;

        } catch (Exception $e) {
            error_log("Erreur lors de la demande de suppression: " . $e->getMessage());
            $_SESSION['error'] = 'Une erreur est survenue lors de l\'enregistrement de votre demande. Veuillez contacter un administrateur.';
            header('Location: /auth/delete-account');
            exit;
        }
    }

    public function validateDeletion($token) {
        try {
            // Appeler l'API backend pour valider la demande
            require_once __DIR__ . '/../Services/ApiService.php';
            $apiService = new ApiService();
            
            $response = $apiService->makeRequest('users/deletion-request/' . $token . '/validate', 'POST');
            
            if ($response && isset($response['success']) && $response['success']) {
                // Log de la validation
                $logMessage = sprintf(
                    "[%s] Validation de suppression de compte - Email: %s - Token: %s\n",
                    date('Y-m-d H:i:s'),
                    $response['data']['email'] ?? 'N/A',
                    $token
                );
                
                $logsDir = __DIR__ . '/../../logs';
                if (!file_exists($logsDir)) {
                    mkdir($logsDir, 0755, true);
                }
                
                file_put_contents(
                    $logsDir . '/account-deletion-requests.log',
                    $logMessage,
                    FILE_APPEND
                );
                
                $_SESSION['success'] = 'Votre demande de suppression a été validée. Un administrateur procédera à la suppression définitive de votre compte dans les 30 jours conformément au RGPD. Vous recevrez un email de confirmation une fois l\'opération effectuée.';
            } else {
                $_SESSION['error'] = $response['data']['message'] ?? $response['message'] ?? 'Lien invalide ou expiré. Veuillez faire une nouvelle demande de suppression.';
            }
           
            header('Location: /auth/delete-account');
            exit;
            
        } catch (Exception $e) {
            error_log("Erreur lors de la validation de suppression: " . $e->getMessage());
            $_SESSION['error'] = 'Une erreur est survenue. Veuillez contacter un administrateur.';
            header('Location: /auth/delete-account');
            exit;
        }
    }
    
    /**
     * Endpoint API pour vérifier la validité du token JWT
     */
    public function verify() {
        // Démarrer la session si nécessaire
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Headers JSON
        header('Content-Type: application/json');
        
        error_log("AuthController::verify - Vérification de session demandée");
        
        // Vérifier si la session existe
        if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
            error_log("AuthController::verify - Utilisateur non connecté");
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Non authentifié'
            ]);
            exit;
        }
        
        // Vérifier si le token existe
        if (!isset($_SESSION['token'])) {
            error_log("AuthController::verify - Token manquant en session");
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Token manquant'
            ]);
            exit;
        }
        
        // Vérifier l'expiration du token JWT
        $token = $_SESSION['token'];
        
        error_log("AuthController::verify - Token présent, vérification de l'expiration...");
        
        try {
            $tokenParts = explode('.', $token);
            if (count($tokenParts) !== 3) {
                error_log("AuthController::verify - Token mal formé (pas 3 parties)");
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Token invalide'
                ]);
                exit;
            }
            
            $payload = json_decode(base64_decode($tokenParts[1]), true);
            
            if (!$payload || !isset($payload['exp'])) {
                error_log("AuthController::verify - Token sans payload exp");
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Token invalide'
                ]);
                exit;
            }
            
            $now = time();
            $exp = $payload['exp'];
            $timeLeft = $exp - $now;
            
            error_log("AuthController::verify - Token exp: $exp, now: $now, reste: $timeLeft secondes");
            
            // Vérifier si le token est expiré
            if ($now >= $exp) {
                // Token expiré, nettoyer la session
                error_log("AuthController::verify - Token EXPIRÉ, nettoyage de la session");
                session_unset();
                session_destroy();
                
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Token expiré'
                ]);
                exit;
            }
            
            // Token valide
            error_log("AuthController::verify - Token VALIDE, expire dans $timeLeft secondes");
            echo json_encode([
                'success' => true,
                'message' => 'Token valide',
                'expires_in' => $timeLeft
            ]);
            
        } catch (Exception $e) {
            error_log("AuthController::verify - Erreur: " . $e->getMessage());
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la vérification du token'
            ]);
        }
        
        exit;
    }
}
