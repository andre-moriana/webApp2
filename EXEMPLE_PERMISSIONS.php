<?php

/**
 * EXEMPLE D'UTILISATION DU SYSTÈME DE PERMISSIONS
 * 
 * Ce fichier montre comment intégrer le système de permissions dans les contrôleurs existants.
 * Copiez et adaptez ces exemples dans vos contrôleurs.
 */

require_once __DIR__ . '/../../app/Config/PermissionHelper.php';
require_once __DIR__ . '/../../app/Services/PermissionService.php';

use App\Config\PermissionHelper;
use App\Services\PermissionService;

class ExemplePermissionsController
{
    /**
     * EXEMPLE 1: Vérifier la permission avant d'afficher une page
     */
    public function index()
    {
        // Méthode 1: Rediriger automatiquement si pas de permission
        PermissionHelper::requirePermission(
            PermissionService::RESOURCE_GROUPS,
            PermissionService::ACTION_VIEW,
            $_SESSION['user']['clubId'] ?? null
        );
        
        // Le code continue ici seulement si l'utilisateur a la permission
        // ...
    }
    
    /**
     * EXEMPLE 2: Vérifier la permission et afficher un message personnalisé
     */
    public function create()
    {
        $user = $_SESSION['user'];
        $clubId = $user['clubId'] ?? null;
        
        // Méthode 2: Vérifier manuellement et personnaliser le message
        if (!PermissionHelper::can(PermissionService::RESOURCE_GROUPS, PermissionService::ACTION_CREATE, $clubId)) {
            $_SESSION['error'] = 'Vous devez être Coach ou Dirigeant pour créer un groupe.';
            header('Location: /groups');
            exit;
        }
        
        // Afficher le formulaire de création
        // ...
    }
    
    /**
     * EXEMPLE 3: Afficher du contenu conditionnel dans une vue
     */
    public function show($id)
    {
        $user = $_SESSION['user'];
        $clubId = $user['clubId'] ?? null;
        
        // Récupérer les données
        $group = $this->getGroup($id);
        
        // Passer les permissions à la vue
        $canEdit = PermissionHelper::can(PermissionService::RESOURCE_GROUPS, PermissionService::ACTION_EDIT, $clubId);
        $canDelete = PermissionHelper::can(PermissionService::RESOURCE_GROUPS, PermissionService::ACTION_DELETE, $clubId);
        
        // Dans la vue, utilisez:
        // <?php if ($canEdit): ?>
        //     <a href="/groups/<?php echo $id; ?>/edit">Modifier</a>
        // <?php endif; ?>
    }
    
    /**
     * EXEMPLE 4: Vérifier si l'utilisateur peut modifier un autre utilisateur
     */
    public function editUser($userId)
    {
        $user = $_SESSION['user'];
        $clubId = $user['clubId'] ?? null;
        
        // Vérifier les permissions pour modifier un utilisateur
        if (!PermissionHelper::canEditUser($userId, $clubId)) {
            $_SESSION['error'] = 'Vous ne pouvez pas modifier cet utilisateur.';
            header('Location: /users');
            exit;
        }
        
        // Afficher le formulaire d'édition
        // ...
    }
    
    /**
     * EXEMPLE 5: Vérifier le rôle de l'utilisateur
     */
    public function adminPanel()
    {
        // Vérifier si l'utilisateur est Admin
        if (!PermissionHelper::isAdmin()) {
            $_SESSION['error'] = 'Accès réservé aux administrateurs.';
            header('Location: /dashboard');
            exit;
        }
        
        // Afficher le panneau admin
        // ...
    }
    
    /**
     * EXEMPLE 6: Vérifier si l'utilisateur a un certain rôle
     */
    public function coachPanel()
    {
        // Vérifier si l'utilisateur est au moins Coach (Coach ou Dirigeant)
        if (!PermissionHelper::hasRole('Coach')) {
            $_SESSION['error'] = 'Accès réservé aux Coachs et Dirigeants.';
            header('Location: /dashboard');
            exit;
        }
        
        // Afficher le panneau coach
        // ...
    }
    
    /**
     * EXEMPLE 7: Vérifier l'appartenance au club
     */
    public function clubData($clubId)
    {
        // Admin peut accéder à tous les clubs
        if (!PermissionHelper::isAdmin()) {
            // Les autres utilisateurs doivent appartenir au club
            if (!PermissionHelper::belongsToClub($clubId)) {
                $_SESSION['error'] = 'Vous ne pouvez accéder qu\'aux données de votre club.';
                header('Location: /clubs');
                exit;
            }
        }
        
        // Afficher les données du club
        // ...
    }
    
    /**
     * EXEMPLE 8: Utilisation dans une vue PHP
     */
    public function exampleView()
    {
        $user = $_SESSION['user'];
        $clubId = $user['clubId'] ?? null;
        
        // Dans la vue, vous pouvez utiliser:
        ?>
        
        <!-- Afficher un bouton seulement si l'utilisateur peut créer -->
        <?php if (PermissionHelper::can(PermissionService::RESOURCE_GROUPS, PermissionService::ACTION_CREATE, $clubId)): ?>
            <a href="/groups/create" class="btn btn-primary">Créer un groupe</a>
        <?php endif; ?>
        
        <!-- Afficher une section réservée aux Coachs -->
        <?php if (PermissionHelper::hasRole('Coach')): ?>
            <div class="coach-section">
                <h3>Section Coach</h3>
                <!-- Contenu pour les coachs -->
            </div>
        <?php endif; ?>
        
        <!-- Afficher une section Admin uniquement -->
        <?php if (PermissionHelper::isAdmin()): ?>
            <div class="admin-section">
                <h3>Administration</h3>
                <!-- Contenu admin -->
            </div>
        <?php endif; ?>
        
        <?php
    }
    
    /**
     * EXEMPLE 9: Différentes permissions pour différentes actions
     */
    public function handleAction($action, $resourceId)
    {
        $user = $_SESSION['user'];
        $clubId = $user['clubId'] ?? null;
        
        switch ($action) {
            case 'view':
                PermissionHelper::requirePermission(
                    PermissionService::RESOURCE_EXERCISES,
                    PermissionService::ACTION_VIEW,
                    $clubId
                );
                break;
                
            case 'edit':
                PermissionHelper::requirePermission(
                    PermissionService::RESOURCE_EXERCISES,
                    PermissionService::ACTION_EDIT,
                    $clubId
                );
                break;
                
            case 'delete':
                PermissionHelper::requirePermission(
                    PermissionService::RESOURCE_EXERCISES,
                    PermissionService::ACTION_DELETE,
                    $clubId
                );
                break;
                
            default:
                $_SESSION['error'] = 'Action invalide.';
                header('Location: /exercises');
                exit;
        }
        
        // Exécuter l'action
        // ...
    }
    
    /**
     * EXEMPLE 10: Vérifier les stats des autres archers
     */
    public function viewStats($targetUserId)
    {
        $user = $_SESSION['user'];
        $clubId = $user['clubId'] ?? null;
        
        // Si c'est ses propres stats, toujours autorisé
        if ($user['id'] == $targetUserId) {
            // Afficher ses propres stats
            return;
        }
        
        // Pour voir les stats des autres, il faut la permission
        if (!PermissionHelper::can(PermissionService::RESOURCE_STATS_OTHER, PermissionService::ACTION_VIEW, $clubId)) {
            $_SESSION['error'] = 'Vous ne pouvez pas consulter les statistiques des autres archers.';
            header('Location: /trainings');
            exit;
        }
        
        // Afficher les stats de l'autre archer
        // ...
    }
    
    // Méthode helper privée
    private function getGroup($id)
    {
        // Simulation
        return ['id' => $id, 'name' => 'Groupe test'];
    }
}

/**
 * RÉSUMÉ DES RESSOURCES DISPONIBLES:
 * 
 * PermissionService::RESOURCE_GROUPS           - Groupes
 * PermissionService::RESOURCE_EVENTS           - Événements
 * PermissionService::RESOURCE_USERS            - Utilisateurs (vue générale)
 * PermissionService::RESOURCE_USERS_SELF       - Modification de ses propres infos
 * PermissionService::RESOURCE_USERS_ALL        - Modification de tous les utilisateurs
 * PermissionService::RESOURCE_EXERCISES        - Exercices
 * PermissionService::RESOURCE_TRAINING_PROGRESS - Progression d'entraînement
 * PermissionService::RESOURCE_STATS_OTHER      - Stats des autres archers
 * PermissionService::RESOURCE_SCORED_TRAINING  - Tir compté
 * PermissionService::RESOURCE_SCORE_SHEET      - Feuille de marque
 * PermissionService::RESOURCE_TRAININGS        - Carnet d'entraînement
 * 
 * ACTIONS DISPONIBLES:
 * 
 * PermissionService::ACTION_VIEW    - Consulter
 * PermissionService::ACTION_EDIT    - Modifier
 * PermissionService::ACTION_CREATE  - Créer
 * PermissionService::ACTION_DELETE  - Supprimer
 * PermissionService::ACTION_MANAGE  - Gérer (combinaison de plusieurs actions)
 */
