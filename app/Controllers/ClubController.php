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
            
            // Vérifier si c'est une erreur 401 (Unauthorized)
            if (isset($response['status_code']) && $response['status_code'] == 401) {
                $error = 'Erreur d\'authentification. Veuillez vous reconnecter.';
                if (isset($response['data']['error'])) {
                    $error = $response['data']['error'];
                }
                $clubs = []; // S'assurer que clubs est un tableau vide
            } elseif ($response['success'] && isset($response['data'])) {
                // Le backend retourne directement un tableau de clubs
                // Vérifier si data contient une erreur
                if (isset($response['data']['error'])) {
                    $error = $response['data']['error'];
                    $clubs = [];
                } elseif (is_array($response['data'])) {
                    // Si data est un tableau, c'est la liste des clubs
                    $clubs = $response['data'];
                } else {
                    $clubs = [];
                }
            } else {
                // Vérifier si la réponse contient une erreur
                if (isset($response['data']['error'])) {
                    $error = $response['data']['error'];
                } else {
                    $error = $response['message'] ?? 'Erreur lors de la récupération des clubs';
                }
                $clubs = []; // S'assurer que clubs est toujours défini
            }
        } catch (Exception $e) {
            $error = 'Erreur lors de la récupération des clubs: ' . $e->getMessage();
            $clubs = []; // S'assurer que clubs est toujours défini même en cas d'exception
        }
        
        // S'assurer que clubs est toujours un tableau
        if (!isset($clubs) || !is_array($clubs)) {
            $clubs = [];
        }

        $title = 'Gestion des clubs - Portail Archers de Gémenos';
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
            
            if ($response['success'] && isset($response['data'])) {
                $club = $response['data'];
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
        $error = null;
        
        try {
            $response = $this->apiService->makeRequest("clubs/{$id}", 'GET');
            
            if ($response['success'] && isset($response['data'])) {
                $club = $response['data'];
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
        $presidentId = !empty($_POST['presidentId']) ? (int)$_POST['presidentId'] : null;

        if (empty($name)) {
            $_SESSION['error'] = 'Le nom du club est requis';
            header('Location: /clubs/' . $id . '/edit');
            exit;
        }

        try {
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

