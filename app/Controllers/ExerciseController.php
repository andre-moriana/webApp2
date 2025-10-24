<?php

require_once "app/Services/ApiService.php";

class ExerciseController {
    private $apiService;
    
    public function __construct() {
        $this->apiService = new ApiService();
    }
    
    public function index() {
        if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
            header("Location: /login");
            exit;
        }
        
        // Initialiser les variables
        $exercises = [];
        $categories = [];
        $error = null;

        try {
            // Récupérer tous les exercices disponibles (pas seulement ceux avec progression)
            $response = $this->apiService->getExercises();
            
            if (isset($response["success"]) && $response["success"] && isset($response["data"])) {
                $data = $response["data"];
                
                // Gérer la structure imbriquée de l'API
                if (isset($data["success"]) && $data["success"] && isset($data["data"])) {
                    $exercises = $data["data"];
                } else {
                    $exercises = $data;
                }
                
                // Vérifier que $exercises est un tableau
                if (!is_array($exercises)) {
                    $exercises = [];
                }
 
            } else {
                $error = "Impossible de charger les exercices: " . ($response["message"] ?? "Erreur inconnue");
                $exercises = [];
            }

            // Récupérer les catégories
            $categoriesResponse = $this->apiService->getExerciseCategories();
            if (isset($categoriesResponse["success"]) && $categoriesResponse["success"] && isset($categoriesResponse["data"])) {
                $data = $categoriesResponse["data"];
                if (isset($data["success"]) && $data["success"] && isset($data["data"])) {
                    $categories = $data["data"];
                } else {
                    $categories = $data;
                }
            }
        } catch (Exception $e) {
            $error = "Erreur lors du chargement des exercices: " . $e->getMessage();
            $exercises = [];
        }

        include "app/Views/layouts/header.php";
        include "app/Views/exercises/index.php";
        include "app/Views/layouts/footer.php";
    }
    
    public function create() {
        if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
            header("Location: /login");
            exit;
        }

        // Récupérer les catégories pour le formulaire
        $categories = [];
        try {
            $categoriesResponse = $this->apiService->getExerciseCategories();
            
            if (isset($categoriesResponse["success"]) && $categoriesResponse["success"] && isset($categoriesResponse["data"])) {
                $data = $categoriesResponse["data"];
                
                // Gérer la structure imbriquée de l'ApiService
                if (isset($data["success"]) && $data["success"] && isset($data["data"])) {
                    $categories = $data["data"];
                } else {
                    $categories = $data;
                }
            }
        } catch (Exception $e) {
            error_log("ExerciseController: Erreur lors du chargement des catégories: " . $e->getMessage());
        }

        // Inclure le header
        include "app/Views/layouts/header.php";
        
        // Inclure la vue
        include "app/Views/exercises/create.php";
        
        // Inclure le footer
        include "app/Views/layouts/footer.php";
    }
    
    public function store() {
        if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
            header("Location: /login");
            exit;
        }

        // Vérifier les permissions (admin ou coach seulement)
        $isAdmin = isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'];
        $isCoach = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'Coach'; // Supprimé strtolower et gardé 'Coach' avec C majuscule
        
        if (!$isAdmin && !$isCoach) {
            header("Location: /exercises?error=" . urlencode("Accès refusé. Seuls les administrateurs et les coachs peuvent créer des exercices."));
            exit;
        }

        $error = null;

        try {
            // Debug: Afficher toutes les données reçues
            error_log("DEBUG CREATE: POST data: " . json_encode($_POST));
            error_log("DEBUG CREATE: FILES data: " . json_encode($_FILES));
            
            // Préparer les données pour l'API backend
            $postData = [
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'category' => $this->getCategoryNameById($_POST['category'] ?? '')
            ];
            
            error_log("DEBUG CREATE: Données préparées: " . json_encode($postData));
            
            if (isset($_FILES['attachment'])) {
                error_log("DEBUG CREATE: Détails du fichier - Nom: " . ($_FILES['attachment']['name'] ?? 'N/A') . 
                         ", Taille: " . ($_FILES['attachment']['size'] ?? 'N/A') . 
                         ", Erreur: " . ($_FILES['attachment']['error'] ?? 'N/A'));
            }
            
            // Test si la méthode existe
            if (method_exists($this->apiService, 'createExerciseWithFile')) {
                error_log("DEBUG CREATE: Appel createExerciseWithFile");
                $response = $this->apiService->createExerciseWithFile($postData, $_FILES['attachment'] ?? null);
            } else {
                error_log("DEBUG CREATE: Méthode createExerciseWithFile non trouvée");
                $response = ['success' => false, 'message' => 'Méthode non trouvée'];
            }
            
            error_log("DEBUG CREATE: Réponse API: " . json_encode($response));
            
            if (isset($response["success"]) && $response["success"]) {
                header("Location: /exercises?created=1");
                exit;
            } else {
                $error = "Erreur lors de la création: " . ($response["message"] ?? "Erreur inconnue");
            }
        } catch (Exception $e) {
            $error = "Erreur lors de la création: " . $e->getMessage();
        }

        // Si erreur, rediriger vers la page de création avec l'erreur
        if ($error) {
            header("Location: /exercises/create?error=" . urlencode($error));
            exit;
        }
    }
    
    public function show($id) {
        if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
            header("Location: /login");
            exit;
        }

        $exercise = null;
        $error = null;

        try {
            $response = $this->apiService->getExerciseDetails($id);
            
            if (isset($response["success"]) && $response["success"] && isset($response["data"])) {
                // Gérer la structure imbriquée : data peut contenir encore success/data
                $data = $response["data"];
                if (isset($data["success"]) && $data["success"] && isset($data["data"])) {
                    $exercise = $data["data"]; // Extraction du niveau le plus profond
                } else {
                    $exercise = $data; // Si pas de structure imbriquée, utiliser directement
                }
            } else {
                $error = "Exercice non trouvé: " . ($response["message"] ?? "Erreur inconnue");
            }
        } catch (Exception $e) {
            $error = "Erreur lors du chargement de l'exercice: " . $e->getMessage();
        }

        // Scripts spécifiques à la page
        $page_scripts = ['/public/assets/js/exercises.js'];

        // Inclure le header
        include "app/Views/layouts/header.php";
        
        // Inclure la vue
        include "app/Views/exercises/show.php";
        
        // Inclure le footer
        include "app/Views/layouts/footer.php";
    }
    
    public function edit($id) {
        if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
            header("Location: /login");
            exit;
        }

        // Vérifier les permissions (admin ou coach seulement)
        $isAdmin = isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'];
        $isCoach = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'Coach'; // Supprimé strtolower()
        
        if (!$isAdmin && !$isCoach) {
            header("Location: /exercises?error=" . urlencode("Accès refusé. Seuls les administrateurs et les coachs peuvent modifier les exercices."));
            exit;
        }

        $exercise = null;
        $categories = [];
        $error = null;

        try {
            $response = $this->apiService->getExerciseDetails($id);
            
            if (isset($response["success"]) && $response["success"] && isset($response["data"])) {
                // Gérer la structure imbriquée : data peut contenir encore success/data
                $data = $response["data"];
                if (isset($data["success"]) && $data["success"] && isset($data["data"])) {
                    $exercise = $data["data"]; // Extraction du niveau le plus profond
                } else {
                    $exercise = $data; // Si pas de structure imbriquée, utiliser directement
                }
            } else {
                $error = "Exercice non trouvé: " . ($response["message"] ?? "Erreur inconnue");
            }

            // Récupérer les catégories
            $categoriesResponse = $this->apiService->getExerciseCategories();
            if (isset($categoriesResponse["success"]) && $categoriesResponse["success"] && isset($categoriesResponse["data"])) {
                $data = $categoriesResponse["data"];
                if (isset($data["success"]) && $data["success"] && isset($data["data"])) {
                    $categories = $data["data"];
                } else {
                    $categories = $data;
                }
                // Mettre en cache dans la session pour éviter les appels répétés
                $_SESSION['exercise_categories'] = $categories;
            }
        } catch (Exception $e) {
            $error = "Erreur lors du chargement de l'exercice: " . $e->getMessage();
        }

        // Inclure le header
        include "app/Views/layouts/header.php";
        
        // Inclure la vue
        include "app/Views/exercises/edit.php";
        
        // Inclure le footer
        include "app/Views/layouts/footer.php";
    }
    
    public function update($id = null) {
        error_log("DEBUG UPDATE: Début de la fonction update avec ID: " . $id);
        
        if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
            error_log("DEBUG UPDATE: Utilisateur non connecté, redirection vers login");
            header("Location: /login");
            exit;
        }

        // Récupérer l'ID depuis $_POST si pas fourni en paramètre
        if ($id === null) {
            $id = $_POST['id'] ?? null;
        }
        
        if (!$id) {
            header("Location: /exercises?error=" . urlencode("ID d'exercice manquant"));
            exit;
        }

        // Vérifier les permissions (admin ou coach seulement)
        $isAdmin = isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'];
        $isCoach = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'Coach'; // Supprimé strtolower()
        
        if (!$isAdmin && !$isCoach) {
            error_log("DEBUG UPDATE: Accès refusé - utilisateur n'est ni admin ni coach");
            header("Location: /exercises?error=" . urlencode("Accès refusé. Seuls les administrateurs et les coachs peuvent modifier les exercices."));
            exit;
        }

        error_log("DEBUG UPDATE: Permissions OK, début du traitement");
        $error = null;

        try {
            error_log("DEBUG UPDATE: Début du try, préparation des données");
            
            // Debug: Afficher toutes les données reçues
            error_log("DEBUG UPDATE: POST data: " . json_encode($_POST));
            error_log("DEBUG UPDATE: FILES data: " . json_encode($_FILES));
            
            // Préparer les données pour l'API backend
            $postData = [
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'category' => $this->getCategoryNameById($_POST['category'] ?? '') // L'API backend attend le nom de la catégorie
            ];
            
            error_log("DEBUG UPDATE: Données préparées: " . json_encode($postData));

            // Vérifier s'il y a un fichier à uploader
            $hasFile = isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK;
            error_log("DEBUG UPDATE: Fichier à uploader: " . ($hasFile ? 'OUI' : 'NON'));
            
            if (isset($_FILES['attachment'])) {
                error_log("DEBUG UPDATE: Détails du fichier - Nom: " . ($_FILES['attachment']['name'] ?? 'N/A') . 
                         ", Taille: " . ($_FILES['attachment']['size'] ?? 'N/A') . 
                         ", Erreur: " . ($_FILES['attachment']['error'] ?? 'N/A'));
            }
            
            if ($hasFile) {
                error_log("DEBUG UPDATE: Appel API avec fichier");
                $response = $this->makePutRequestWithFiles('exercise_sheets?action=update&id=' . $id, $postData, $_FILES['attachment']);
            } else {
                error_log("DEBUG UPDATE: Appel API sans fichier");
                $response = $this->apiService->updateExercise($id, $postData);
            }
            
            // Debug: Afficher la réponse de l'API
            error_log("ExerciseController update - NOUVEAU CODE ACTIF - Réponse API: " . json_encode($response));
            
            if (isset($response["success"]) && $response["success"]) {
                // Succès - recharger la page d'édition avec un message de succès
                header("Location: /exercises/" . $id . "/edit?success=1");
                exit;
            } else {
                $error = "Erreur lors de la mise à jour: " . ($response["message"] ?? "Erreur inconnue");
                error_log("ExerciseController update - Erreur: " . $error);
            }
        } catch (Exception $e) {
            $error = "Erreur lors de la mise à jour: " . $e->getMessage();
        }

        // Si erreur, rediriger vers la page d'édition avec l'erreur
        if ($error) {
            header("Location: /exercises/" . $id . "/edit?error=" . urlencode($error));
            exit;
        }
    }

    private function getCategoryNameById($categoryId) {
        error_log("DEBUG getCategoryNameById: Début avec categoryId: " . $categoryId);
        
        // Utiliser les catégories déjà chargées dans la session ou les données de la page
        if (isset($_SESSION['exercise_categories'])) {
            error_log("DEBUG getCategoryNameById: Utilisation du cache de session");
            $categories = $_SESSION['exercise_categories'];
        } else {
            error_log("DEBUG getCategoryNameById: Appel API pour récupérer les catégories");
            // Fallback : récupérer depuis l'API (une seule fois)
            try {
                $categoriesResponse = $this->apiService->getExerciseCategories();
                if (isset($categoriesResponse["success"]) && $categoriesResponse["success"] && isset($categoriesResponse["data"])) {
                    $data = $categoriesResponse["data"];
                    if (isset($data["success"]) && $data["success"] && isset($data["data"])) {
                        $categories = $data["data"];
                    } else {
                        $categories = $data;
                    }
                    // Mettre en cache dans la session
                    $_SESSION['exercise_categories'] = $categories;
                } else {
                    return '';
                }
            } catch (Exception $e) {
                error_log("Erreur lors de la récupération des catégories: " . $e->getMessage());
                return '';
            }
        }
        
        foreach ($categories as $category) {
            if ($category['id'] == $categoryId) {
                error_log("DEBUG getCategoryNameById: Catégorie trouvée: " . $category['name']);
                return $category['name'];
            }
        }
        error_log("DEBUG getCategoryNameById: Catégorie non trouvée pour ID: " . $categoryId);
        return '';
    }

    private function makePutRequestWithFiles($endpoint, $data, $fileData) {
        $apiUrl = $_ENV['API_BASE_URL'] ?? 'http://82.67.123.22:25000/api';
        $url = $apiUrl . '/' . $endpoint;
        
        // Récupérer le token d'authentification
        $token = $_SESSION['token'] ?? null;
        if (!$token) {
            throw new Exception("Token d'authentification manquant");
        }

        $ch = curl_init();
        
        // Configuration de base
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true); // Utiliser POST au lieu de PUT
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        // Headers - ne pas définir Content-Type pour multipart/form-data
        $headers = [
            'Accept: */*',
            'Authorization: Bearer ' . $token
        ];
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Préparer les données POST
        $postFields = $data;
        
        // Ajouter le fichier seulement s'il existe et est valide
        if ($fileData && isset($fileData['tmp_name']) && isset($fileData['type']) && isset($fileData['name']) && !empty($fileData['tmp_name']) && $fileData['error'] === UPLOAD_ERR_OK) {
            $postFields['attachment'] = new CURLFile(
                $fileData['tmp_name'],
                $fileData['type'],
                $fileData['name']
            );
            error_log("DEBUG makePutRequestWithFiles: Fichier ajouté - " . $fileData['name']);
        } else {
            error_log("DEBUG makePutRequestWithFiles: Aucun fichier valide fourni");
        }
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        error_log("DEBUG makePutRequestWithFiles: HTTP Code: " . $httpCode);
        error_log("DEBUG makePutRequestWithFiles: Response: " . $response);
        
        if ($response === false) {
            throw new Exception("Erreur cURL: " . $curlError);
        }
        
        $decodedResponse = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Réponse JSON invalide: " . $response);
        }
        
        return $decodedResponse;
    }

    private function makePostRequestWithFormData($endpoint, $data, $fileData) {
        $apiUrl = $_ENV['API_BASE_URL'] ?? 'http://82.67.123.22:25000/api';
        $url = $apiUrl . '/' . $endpoint;
        
        // Récupérer le token d'authentification
        $token = $_SESSION['token'] ?? null;
        if (!$token) {
            throw new Exception("Token d'authentification manquant");
        }

        $ch = curl_init();
        
        // Configuration de base
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/x-www-form-urlencoded' // Indiquer que les données sont en form-urlencoded
        ]);
        
        // Préparer les données POST
        $postData = http_build_query($data);

        // Ajouter le fichier seulement s'il existe et est valide
        if ($fileData && isset($fileData['tmp_name']) && isset($fileData['type']) && isset($fileData['name']) && !empty($fileData['tmp_name'])) {
            $postData .= '&attachment=' . urlencode(file_get_contents($fileData['tmp_name'])); // Utiliser file_get_contents pour lire le contenu du fichier
            $postData .= '&attachment_type=' . urlencode($fileData['type']);
            $postData .= '&attachment_name=' . urlencode($fileData['name']);
        }
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($response === false) {
            throw new Exception("Erreur cURL: " . $curlError);
        }
        
        $decodedResponse = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Réponse JSON invalide: " . $response);
        }
        
        return $decodedResponse;
    }
    
    public function destroy($id) {
        if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
            header("Location: /login");
            exit;
        }

        // Vérifier les permissions (admin ou coach seulement)
        $isAdmin = isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'];
        $isCoach = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'Coach'; // Supprimé strtolower()
        
        if (!$isAdmin && !$isCoach) {
            header("Location: /exercises?error=" . urlencode("Accès refusé. Seuls les administrateurs et les coachs peuvent supprimer les exercices."));
            exit;
        }

        try {
            $response = $this->apiService->deleteExercise($id);
            
            if ($response["success"]) {
                header("Location: /exercises?deleted=1");
                exit;
            } else {
                $error = "Erreur lors de la suppression de l'exercice: " . ($response["message"] ?? "Erreur inconnue");
                header("Location: /exercises?error=" . urlencode($error));
                exit;
            }
        } catch (Exception $e) {
            $error = "Erreur lors de la suppression de l'exercice: " . $e->getMessage();
            header("Location: /exercises?error=" . urlencode($error));
            exit;
        }
    }
}
?>