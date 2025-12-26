<?php

require_once 'app/Services/ApiService.php';

class ClubController {
    private $apiService;
    
    public function __construct() {
        $this->apiService = new ApiService();
    }
    
    public function index() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }

        $clubs = [];
        $error = null;

        try {
            $response = $this->apiService->makeRequest('clubs/list', 'GET');
            $payload = $this->apiService->unwrapData($response);
            if ($response['success'] && is_array($payload)) {
                $clubs = $payload;
            } else {
                $error = $response['message'] ?? 'Erreur lors de la récupération des clubs';
            }
        } catch (Exception $e) {
            $error = 'Erreur lors de la récupération des clubs: ' . $e->getMessage();
        }

        $title = 'Gestion des clubs - Portail Archers de Gémenos';
        
        // Définir les fichiers JS spécifiques
        $additionalJS = ['/public/assets/js/clubs-table.js'];
        
        include 'app/Views/layouts/header.php';
        include 'app/Views/clubs/index.php';
        include 'app/Views/layouts/footer.php';
    }
    
    public function create() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }

        // Vérifier que l'utilisateur est admin
        if (!($_SESSION['user']['is_admin'] ?? false)) {
            $_SESSION['error'] = 'Seuls les administrateurs peuvent créer un club';
            header('Location: /clubs');
            exit;
        }

        $themes = [];
        try {
            // Récupérer les thèmes
            $themesResponse = $this->apiService->makeRequest('themes/list', 'GET');
            if ($themesResponse['success'] && isset($themesResponse['data'])) {
                $themes = is_array($themesResponse['data']) ? $themesResponse['data'] : [];
            }
        } catch (Exception $e) {
            // En cas d'erreur, continuer avec un tableau vide
            error_log('Erreur lors de la récupération des thèmes: ' . $e->getMessage());
        }

        $title = 'Créer un club - Portail Archers de Gémenos';
        
        include 'app/Views/layouts/header.php';
        include 'app/Views/clubs/create.php';
        include 'app/Views/layouts/footer.php';
    }
    
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /clubs');
            exit;
        }

        // Vérifier que l'utilisateur est admin
        if (!($_SESSION['user']['is_admin'] ?? false)) {
            $_SESSION['error'] = 'Seuls les administrateurs peuvent créer un club';
            header('Location: /clubs');
            exit;
        }

        $name = $_POST['name'] ?? '';
        $nameShort = $_POST['nameShort'] ?? '';
        $description = $_POST['description'] ?? '';
        $address = $_POST['address'] ?? '';
        $city = $_POST['city'] ?? '';
        $postalCode = $_POST['postalCode'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $email = $_POST['email'] ?? '';
        $website = $_POST['website'] ?? '';
        $theme = $_POST['theme'] ?? '';
        $presidentId = !empty($_POST['presidentId']) ? (int)$_POST['presidentId'] : null;

        if (empty($name)) {
            $_SESSION['error'] = 'Le nom du club est requis';
            header('Location: /clubs/create');
            exit;
        }

        try {
            $response = $this->apiService->makeRequest('clubs/create', 'POST', [
                'name' => $name,
                'nameShort' => $nameShort,
                'description' => $description,
                'address' => $address,
                'city' => $city,
                'postalCode' => $postalCode,
                'phone' => $phone,
                'email' => $email,
                'website' => $website,
                'theme' => $theme,
                'presidentId' => $presidentId
            ]);
            
            if ($response['success']) {
                $_SESSION['success'] = 'Club créé avec succès';
                header('Location: /clubs');
                exit;
            } else {
                $_SESSION['error'] = $response['message'] ?? 'Erreur lors de la création du club';
                header('Location: /clubs/create');
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erreur lors de la création du club: ' . $e->getMessage();
            header('Location: /clubs/create');
            exit;
        }
    }
    
    public function show($id) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }
        
        $club = null;
        $error = null;
        
        try {
            $response = $this->apiService->makeRequest("clubs/{$id}", 'GET');
            $payload = $this->apiService->unwrapData($response);
            if ($response['success'] && $payload) {
                $club = $payload;
            } else {
                $error = $response['message'] ?? 'Erreur lors de la récupération du club';
            }
        } catch (Exception $e) {
            $error = 'Erreur lors de la récupération du club: ' . $e->getMessage();
        }
        
        if (!$club) {
            $_SESSION['error'] = $error ?? 'Club non trouvé';
            header('Location: /clubs');
            exit;
        }

        $title = 'Détails du club - ' . htmlspecialchars($club['name'] ?? 'Club');
        
        include 'app/Views/layouts/header.php';
        include 'app/Views/clubs/show.php';
        include 'app/Views/layouts/footer.php';
    }
    
    public function edit($id) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }
        
        $club = null;
        $themes = [];
        $error = null;
        
        try {
            $response = $this->apiService->makeRequest("clubs/{$id}", 'GET');
            $payload = $this->apiService->unwrapData($response);
            if ($response['success'] && $payload) {
                $club = $payload;
            } else {
                $error = $response['message'] ?? 'Erreur lors de la récupération du club';
            }
            
            // Récupérer les thèmes
            $themesResponse = $this->apiService->makeRequest('themes/list', 'GET');
            if ($themesResponse['success'] && isset($themesResponse['data'])) {
                $themes = is_array($themesResponse['data']) ? $themesResponse['data'] : [];
            }
        } catch (Exception $e) {
            $error = 'Erreur lors de la récupération du club: ' . $e->getMessage();
        }
        
        if (!$club) {
            $_SESSION['error'] = $error ?? 'Club non trouvé';
            header('Location: /clubs');
            exit;
        }

        $title = 'Modifier le club - ' . htmlspecialchars($club['name'] ?? 'Club');
        
        include 'app/Views/layouts/header.php';
        include 'app/Views/clubs/edit.php';
        include 'app/Views/layouts/footer.php';
    }
    
    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /clubs');
            exit;
        }

        $name = $_POST['name'] ?? '';
        $nameShort = $_POST['nameShort'] ?? '';
        $description = $_POST['description'] ?? '';
        $address = $_POST['address'] ?? '';
        $city = $_POST['city'] ?? '';
        $postalCode = $_POST['postalCode'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $email = $_POST['email'] ?? '';
        $website = $_POST['website'] ?? '';
        $theme = $_POST['theme'] ?? '';
        $presidentId = !empty($_POST['presidentId']) ? (int)$_POST['presidentId'] : null;

        if (empty($name)) {
            $_SESSION['error'] = 'Le nom du club est requis';
            header('Location: /clubs/' . $id . '/edit');
            exit;
        }

        try {
            // Gérer l'upload du logo si un fichier a été fourni
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $logoResponse = $this->apiService->uploadClubLogo($id, $_FILES['logo']);
                if (!$logoResponse['success']) {
                    $_SESSION['error'] = $logoResponse['message'] ?? 'Erreur lors de l\'upload du logo';
                    header('Location: /clubs/' . $id . '/edit');
                    exit;
                }
            }

            // Mettre à jour les autres informations du club
            $response = $this->apiService->makeRequest("clubs/{$id}", 'PUT', [
                'name' => $name,
                'nameShort' => $nameShort,
                'description' => $description,
                'address' => $address,
                'city' => $city,
                'postalCode' => $postalCode,
                'phone' => $phone,
                'email' => $email,
                'website' => $website,
                'theme' => $theme,
                'presidentId' => $presidentId
            ]);
            
            if ($response['success']) {
                $_SESSION['success'] = 'Club modifié avec succès';
                header('Location: /clubs');
                exit;
            } else {
                $_SESSION['error'] = $response['message'] ?? 'Erreur lors de la modification du club';
                header('Location: /clubs/' . $id . '/edit');
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erreur lors de la modification du club: ' . $e->getMessage();
            header('Location: /clubs/' . $id . '/edit');
            exit;
        }
    }
    
    public function destroy($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /clubs');
            exit;
        }

        // Vérifier que l'utilisateur est admin
        if (!($_SESSION['user']['is_admin'] ?? false)) {
            $_SESSION['error'] = 'Seuls les administrateurs peuvent supprimer un club';
            header('Location: /clubs');
            exit;
        }

        try {
            $response = $this->apiService->makeRequest("clubs/{$id}", 'DELETE');
            
            if ($response['success']) {
                $_SESSION['success'] = 'Club supprimé avec succès';
                header('Location: /clubs');
                exit;
            } else {
                $_SESSION['error'] = $response['message'] ?? 'Erreur lors de la suppression du club';
                header('Location: /clubs');
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erreur lors de la suppression du club: ' . $e->getMessage();
            header('Location: /clubs');
            exit;
        }
    }
}
