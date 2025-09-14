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
                // Si l'API ne fonctionne pas, essayer de charger les utilisateurs locaux
                $users = $this->getLocalUsers();
                if (empty($users)) {
                    $users = $this->getSimulatedUsers();
                    $error = 'API backend non accessible - Affichage de données simulées';
                } else {
                    $error = 'API backend non accessible - Affichage des utilisateurs stockés localement';
                }
            }
        } catch (Exception $e) {
            // En cas d'erreur, essayer de charger les utilisateurs locaux
            $users = $this->getLocalUsers();
            if (empty($users)) {
                $users = $this->getSimulatedUsers();
                $error = 'Erreur de connexion à l\'API - Affichage de données simulées';
            } else {
                $error = 'Erreur de connexion à l\'API - Affichage des utilisateurs stockés localement';
            }
        }
        
        $title = 'Gestion des utilisateurs - Portail Archers de Gémenos';
        
        include 'app/Views/layouts/header.php';
        include 'app/Views/users/index.php';
        include 'app/Views/layouts/footer.php';
    }
    
    /**
     * Récupère les utilisateurs stockés localement
     */
    private function getLocalUsers() {
        $usersFile = __DIR__ . '/../Storage/users.json';
        if (file_exists($usersFile)) {
            $content = file_get_contents($usersFile);
            $users = json_decode($content, true);
            return $users ?: [];
        }
        return [];
    }
