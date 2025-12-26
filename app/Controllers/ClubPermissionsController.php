<?php
require_once 'app/Services/PermissionService.php';
require_once 'app/Services/ApiService.php';

class ClubPermissionsController
{
    private $permissionService;
    
    public function __construct()
    {
        $this->permissionService = new PermissionService();
        
        // Vérifier que l'utilisateur est connecté
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }
    }
    
    /**
     * Affiche la page de configuration des permissions d'un club
     */
    public function edit($clubId)
    {
        $user = $_SESSION['user'];
        
        // Vérifier que l'utilisateur est Dirigeant ou Admin
        if (!$this->permissionService->isAdmin($user) && 
            !$this->permissionService->hasRoleLevel($user, 'Dirigeant')) {
            $_SESSION['error'] = 'Vous devez être Dirigeant ou Administrateur pour accéder à cette page.';
            header('Location: /clubs');
            exit;
        }
        
        // Vérifier que l'utilisateur appartient au club (sauf Admin)
        if (!$this->permissionService->isAdmin($user) && 
            !$this->permissionService->belongsToClub($user, $clubId)) {
            $_SESSION['error'] = 'Vous ne pouvez configurer que les permissions de votre club.';
            header('Location: /clubs');
            exit;
        }
        
        // Récupérer les informations du club
        $apiService = new ApiService();
        $clubResponse = $apiService->get("/clubs/{$clubId}");
        
        if (!$clubResponse['success']) {
            $_SESSION['error'] = 'Club introuvable.';
            header('Location: /clubs');
            exit;
        }
        
        $club = $clubResponse['data'];
        $permissions = $this->permissionService->getClubPermissions($clubId);
        $configurablePermissions = $this->permissionService->getConfigurablePermissions();
        $availableRoles = $this->permissionService->getAvailableRoles();
        
        // Inclure le header
        $title = "Configuration des permissions - " . ($club['name'] ?? 'Club');
        $additionalCSS = ['/public/assets/css/club-permissions.css'];
        $additionalJS = ['/public/assets/js/club-permissions.js'];
        
        require_once __DIR__ . '/../Views/layouts/header.php';
        require_once __DIR__ . '/../Views/clubs/permissions.php';
        require_once __DIR__ . '/../Views/layouts/footer.php';
    }
    
    /**
     * Met à jour les permissions d'un club
     */
    public function update($clubId)
    {
        $user = $_SESSION['user'];
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /clubs/' . $clubId . '/permissions');
            exit;
        }
        
        // Récupérer les permissions depuis le POST
        $permissions = [];
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'perm_') === 0) {
                $permKey = substr($key, 5); // Retirer le préfixe 'perm_'
                $permissions[$permKey] = $value;
            }
        }
        
        // Mettre à jour les permissions
        $result = $this->permissionService->updateClubPermissions($clubId, $permissions, $user);
        
        if ($result['success']) {
            $_SESSION['success'] = 'Les permissions ont été mises à jour avec succès.';
        } else {
            $_SESSION['error'] = $result['message'] ?? 'Erreur lors de la mise à jour des permissions.';
        }
        
        header('Location: /clubs/' . $clubId . '/permissions');
        exit;
    }
}
