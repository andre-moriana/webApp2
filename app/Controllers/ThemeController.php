<?php

require_once 'app/Services/ApiService.php';

class ThemeController {
    private $apiService;
    
    public function __construct() {
        $this->apiService = new ApiService();
    }
    
    public function index() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }

        // Vérifier que l'utilisateur est admin
        if (!($_SESSION['user']['is_admin'] ?? false)) {
            $_SESSION['error'] = 'Seuls les administrateurs peuvent gérer les thèmes';
            header('Location: /dashboard');
            exit;
        }

        $themes = [];
        $error = null;

        try {
            $response = $this->apiService->makeRequest('themes/list', 'GET');
            
            if ($response['success'] && isset($response['data'])) {
                $themes = is_array($response['data']) ? $response['data'] : [];
            } else {
                $error = $response['message'] ?? 'Erreur lors de la récupération des thèmes';
            }
        } catch (Exception $e) {
            $error = 'Erreur lors de la récupération des thèmes: ' . $e->getMessage();
        }

        $title = 'Gestion des thèmes - Portail Archers de Gémenos';
        
        include 'app/Views/layouts/header.php';
        include 'app/Views/themes/index.php';
        include 'app/Views/layouts/footer.php';
    }
    
    public function create() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }

        // Vérifier que l'utilisateur est admin
        if (!($_SESSION['user']['is_admin'] ?? false)) {
            $_SESSION['error'] = 'Seuls les administrateurs peuvent créer un thème';
            header('Location: /themes');
            exit;
        }

        $title = 'Créer un thème - Portail Archers de Gémenos';
        
        include 'app/Views/layouts/header.php';
        include 'app/Views/themes/create.php';
        include 'app/Views/layouts/footer.php';
    }
    
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /themes');
            exit;
        }

        // Vérifier que l'utilisateur est admin
        if (!($_SESSION['user']['is_admin'] ?? false)) {
            $_SESSION['error'] = 'Seuls les administrateurs peuvent créer un thème';
            header('Location: /themes');
            exit;
        }

        $name = $_POST['name'] ?? '';
        $clubName = $_POST['clubName'] ?? '';
        $clubNameShort = $_POST['clubNameShort'] ?? '';
        
        // Récupérer les couleurs
        $colors = [
            'primary' => $_POST['colorPrimary'] ?? '#14532d',
            'secondary' => $_POST['colorSecondary'] ?? '#BBCE00',
            'background' => $_POST['colorBackground'] ?? '#14532d',
            'surface' => $_POST['colorSurface'] ?? '#f8f9fa',
            'text' => $_POST['colorText'] ?? '#333333',
            'textSecondary' => $_POST['colorTextSecondary'] ?? '#666666',
            'accent' => $_POST['colorAccent'] ?? '#BBCE00',
            'error' => $_POST['colorError'] ?? '#dc2626',
            'success' => $_POST['colorSuccess'] ?? '#22c55e',
            'warning' => $_POST['colorWarning'] ?? '#f59e0b',
            'info' => $_POST['colorInfo'] ?? '#3b82f6',
            'button' => $_POST['colorButton'] ?? '#007AFF'
        ];

        if (empty($name) || empty($clubName)) {
            $_SESSION['error'] = 'Le nom et le nom du club sont requis';
            header('Location: /themes/create');
            exit;
        }

        try {
            $response = $this->apiService->makeRequest('themes/create', 'POST', [
                'name' => $name,
                'clubName' => $clubName,
                'clubNameShort' => $clubNameShort,
                'colors' => $colors
            ]);
            
            if ($response['success']) {
                $_SESSION['success'] = 'Thème créé avec succès';
                header('Location: /themes');
                exit;
            } else {
                $_SESSION['error'] = $response['message'] ?? 'Erreur lors de la création du thème';
                header('Location: /themes/create');
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erreur lors de la création du thème: ' . $e->getMessage();
            header('Location: /themes/create');
            exit;
        }
    }
    
    public function show($id) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }
        
        // Vérifier que l'utilisateur est admin
        if (!($_SESSION['user']['is_admin'] ?? false)) {
            $_SESSION['error'] = 'Seuls les administrateurs peuvent voir les détails des thèmes';
            header('Location: /themes');
            exit;
        }
        
        $theme = null;
        $error = null;
        
        try {
            // Récupérer tous les thèmes et trouver celui avec l'ID correspondant
            $response = $this->apiService->makeRequest('themes/list', 'GET');
            
            if ($response['success'] && isset($response['data'])) {
                $themes = is_array($response['data']) ? $response['data'] : [];
                foreach ($themes as $t) {
                    if (($t['id'] ?? '') === $id) {
                        $theme = $t;
                        break;
                    }
                }
            }
            
            if (!$theme) {
                $error = 'Thème non trouvé';
            }
        } catch (Exception $e) {
            $error = 'Erreur lors de la récupération du thème: ' . $e->getMessage();
        }
        
        if (!$theme) {
            $_SESSION['error'] = $error ?? 'Thème non trouvé';
            header('Location: /themes');
            exit;
        }

        $title = 'Détails du thème - ' . htmlspecialchars($theme['name'] ?? 'Thème');
        
        include 'app/Views/layouts/header.php';
        include 'app/Views/themes/show.php';
        include 'app/Views/layouts/footer.php';
    }
    
    public function edit($id) {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }
        
        // Vérifier que l'utilisateur est admin
        if (!($_SESSION['user']['is_admin'] ?? false)) {
            $_SESSION['error'] = 'Seuls les administrateurs peuvent modifier un thème';
            header('Location: /themes');
            exit;
        }
        
        // Ne pas permettre la modification du thème par défaut
        if ($id === 'default') {
            $_SESSION['error'] = 'Le thème par défaut ne peut pas être modifié';
            header('Location: /themes');
            exit;
        }
        
        $theme = null;
        $error = null;
        
        try {
            // Récupérer tous les thèmes et trouver celui avec l'ID correspondant
            $response = $this->apiService->makeRequest('themes/list', 'GET');
            
            if ($response['success'] && isset($response['data'])) {
                $themes = is_array($response['data']) ? $response['data'] : [];
                foreach ($themes as $t) {
                    if (($t['id'] ?? '') === $id) {
                        $theme = $t;
                        break;
                    }
                }
            }
            
            if (!$theme) {
                $error = 'Thème non trouvé';
            }
        } catch (Exception $e) {
            $error = 'Erreur lors de la récupération du thème: ' . $e->getMessage();
        }
        
        if (!$theme) {
            $_SESSION['error'] = $error ?? 'Thème non trouvé';
            header('Location: /themes');
            exit;
        }

        $title = 'Modifier le thème - ' . htmlspecialchars($theme['name'] ?? 'Thème');
        
        include 'app/Views/layouts/header.php';
        include 'app/Views/themes/edit.php';
        include 'app/Views/layouts/footer.php';
    }
    
    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /themes');
            exit;
        }

        // Vérifier que l'utilisateur est admin
        if (!($_SESSION['user']['is_admin'] ?? false)) {
            $_SESSION['error'] = 'Seuls les administrateurs peuvent modifier un thème';
            header('Location: /themes');
            exit;
        }

        // Ne pas permettre la modification du thème par défaut
        if ($id === 'default') {
            $_SESSION['error'] = 'Le thème par défaut ne peut pas être modifié';
            header('Location: /themes');
            exit;
        }

        $name = $_POST['name'] ?? '';
        $clubName = $_POST['clubName'] ?? '';
        $clubNameShort = $_POST['clubNameShort'] ?? '';
        
        // Récupérer les couleurs
        $colors = [
            'primary' => $_POST['colorPrimary'] ?? '#14532d',
            'secondary' => $_POST['colorSecondary'] ?? '#BBCE00',
            'background' => $_POST['colorBackground'] ?? '#14532d',
            'surface' => $_POST['colorSurface'] ?? '#f8f9fa',
            'text' => $_POST['colorText'] ?? '#333333',
            'textSecondary' => $_POST['colorTextSecondary'] ?? '#666666',
            'accent' => $_POST['colorAccent'] ?? '#BBCE00',
            'error' => $_POST['colorError'] ?? '#dc2626',
            'success' => $_POST['colorSuccess'] ?? '#22c55e',
            'warning' => $_POST['colorWarning'] ?? '#f59e0b',
            'info' => $_POST['colorInfo'] ?? '#3b82f6',
            'button' => $_POST['colorButton'] ?? '#007AFF'
        ];

        if (empty($name) || empty($clubName)) {
            $_SESSION['error'] = 'Le nom et le nom du club sont requis';
            header('Location: /themes/' . $id . '/edit');
            exit;
        }

        try {
            $response = $this->apiService->makeRequest('themes/update', 'PUT', [
                'id' => $id,
                'name' => $name,
                'clubName' => $clubName,
                'clubNameShort' => $clubNameShort,
                'colors' => $colors
            ]);
            
            if ($response['success']) {
                $_SESSION['success'] = 'Thème modifié avec succès';
                header('Location: /themes');
                exit;
            } else {
                $_SESSION['error'] = $response['message'] ?? 'Erreur lors de la modification du thème';
                header('Location: /themes/' . $id . '/edit');
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erreur lors de la modification du thème: ' . $e->getMessage();
            header('Location: /themes/' . $id . '/edit');
            exit;
        }
    }
    
    public function destroy($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /themes');
            exit;
        }

        // Vérifier que l'utilisateur est admin
        if (!($_SESSION['user']['is_admin'] ?? false)) {
            $_SESSION['error'] = 'Seuls les administrateurs peuvent supprimer un thème';
            header('Location: /themes');
            exit;
        }

        // Ne pas permettre la suppression du thème par défaut
        if ($id === 'default') {
            $_SESSION['error'] = 'Le thème par défaut ne peut pas être supprimé';
            header('Location: /themes');
            exit;
        }

        try {
            $response = $this->apiService->makeRequest("themes/delete/{$id}", 'DELETE');
            
            if ($response['success']) {
                $_SESSION['success'] = 'Thème supprimé avec succès';
                header('Location: /themes');
                exit;
            } else {
                $_SESSION['error'] = $response['message'] ?? 'Erreur lors de la suppression du thème';
                header('Location: /themes');
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erreur lors de la suppression du thème: ' . $e->getMessage();
            header('Location: /themes');
            exit;
        }
    }
}

