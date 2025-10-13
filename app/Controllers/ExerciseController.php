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
            // Préparer les données pour l'API backend
            $postData = [
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'category' => $this->getCategoryNameById($_POST['category'] ?? '')
            ];
            // Test si la méthode existe
            if (method_exists($this->apiService, 'createExerciseWithFile')) {
                $response = $this->apiService->createExerciseWithFile($postData, $_FILES['attachment'] ?? null);
            } else {
                $response = ['success' => false, 'message' => 'Méthode non trouvée'];
            }
            
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
    
    public function update($id) {
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

        $error = null;

        try {
            // Préparer les données pour l'API backend
            $postData = [
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'category' => $this->getCategoryNameById($_POST['category'] ?? '') // Changé de category_id à category et supprimé progression
            ];

            // Vérifier s'il y a un fichier à uploader
            $hasFile = isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK;
            
            if ($hasFile) {
                $response = $this->makePutRequestWithFiles('exercise_sheets?action=update&id=' . $id, $postData, $_FILES['attachment']);
            } else {
                $response = $this->apiService->updateExercise($id, $postData);
            }
            
            // Debug: Afficher la réponse de l'API
            error_log("ExerciseController update - Réponse API: " . json_encode($response));
            
            if (isset($response["success"]) && $response["success"]) {
                header("Location: /exercises?updated=1");
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
        try {
            $categoriesResponse = $this->apiService->getExerciseCategories();
            if (isset($categoriesResponse["success"]) && $categoriesResponse["success"] && isset($categoriesResponse["data"])) {
                $data = $categoriesResponse["data"];
                if (isset($data["success"]) && $data["success"] && isset($data["data"])) {
                    $categories = $data["data"];
                } else {
                    $categories = $data;
                }
                
                foreach ($categories as $category) {
                    if ($category['id'] == $categoryId) {
                        return $category['name'];
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération des catégories: " . $e->getMessage());
        }
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
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token
        ]);
        
        // Préparer les données POST
        $postFields = $data;
        
        // Ajouter le fichier seulement s'il existe et est valide
        if ($fileData && isset($fileData['tmp_name']) && isset($fileData['type']) && isset($fileData['name']) && !empty($fileData['tmp_name'])) {
            $postFields['attachment'] = new CURLFile(
                $fileData['tmp_name'],
                $fileData['type'],
                $fileData['name']
            );
        }
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        
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