<?php
require_once 'app/Services/ApiService.php';

class PermissionService
{
    private $apiService;
    
    // Définition de la hiérarchie des rôles
    const ROLE_HIERARCHY = [
        'Archer' => 1,
        'Coach' => 2,
        'Dirigeant' => 3,
        'Admin' => 99  // Admin a tous les droits
    ];
    
    // Définition des actions possibles
    const ACTION_VIEW = 'view';
    const ACTION_EDIT = 'edit';
    const ACTION_CREATE = 'create';
    const ACTION_DELETE = 'delete';
    const ACTION_MANAGE = 'manage';
    
    // Définition des ressources
    const RESOURCE_GROUPS = 'groups';
    const RESOURCE_EVENTS = 'events';
    const RESOURCE_USERS = 'users';
    const RESOURCE_USERS_SELF = 'users_self';
    const RESOURCE_USERS_ALL = 'users_all';
    const RESOURCE_EXERCISES = 'exercises';
    const RESOURCE_TRAINING_PROGRESS = 'training_progress';
    const RESOURCE_STATS_OTHER = 'stats_other';
    const RESOURCE_SCORED_TRAINING = 'scored_training';
    const RESOURCE_SCORE_SHEET = 'score_sheet';
    const RESOURCE_TRAININGS = 'trainings';
    
    public function __construct()
    {
        $this->apiService = new ApiService();
    }
    
    /**
     * Vérifie si l'utilisateur a la permission pour une action sur une ressource
     */
    public function hasPermission($user, $resource, $action, $clubId = null)
    {
        // Admin a tous les droits
        if ($this->isAdmin($user)) {
            return true;
        }
        
        // Vérifier que l'utilisateur appartient au club (sauf pour Admin)
        if ($clubId !== null && !$this->belongsToClub($user, $clubId)) {
            return false;
        }

        // Essayer de déléguer au backend Permissions API
        $apiDecision = $this->checkPermissionViaApi($user, $resource, $action, $clubId);
        if ($apiDecision !== null) {
            return (bool) $apiDecision;
        }
        
        // Fallback local : récupérer les permissions du club et évaluer localement
        $clubPermissions = $this->getClubPermissions($clubId ?? ($user['clubId'] ?? null));
        
        return $this->checkPermission($user, $resource, $action, $clubPermissions);
    }
    
    /**
     * Vérifie si l'utilisateur est admin
     */
    public function isAdmin($user)
    {
        // Accept truthy values (bool true, 1, "1") to avoid strictness issues with session serialization
        return !empty($user['is_admin']);
    }
    
    /**
     * Vérifie si l'utilisateur appartient au club
     */
    public function belongsToClub($user, $clubId)
    {
        // Compare as strings to avoid type mismatch (int vs string)
        return isset($user['clubId']) && (string)$user['clubId'] === (string)$clubId;
    }
    
    /**
     * Récupère le niveau hiérarchique du rôle
     */
    public function getRoleLevel($role)
    {
        return self::ROLE_HIERARCHY[$role] ?? 0;
    }
    
    /**
     * Vérifie si le rôle de l'utilisateur est suffisant
     */
    public function hasRoleLevel($user, $requiredLevel)
    {
        if ($this->isAdmin($user)) {
            return true;
        }
        
        $userRole = $user['role'] ?? 'Archer';
        $userLevel = $this->getRoleLevel($userRole);
        
        if (is_string($requiredLevel)) {
            $requiredLevel = $this->getRoleLevel($requiredLevel);
        }
        
        return $userLevel >= $requiredLevel;
    }
    
    /**
     * Récupère les permissions configurées pour un club
     */
    public function getClubPermissions($clubId)
    {
        if (!$clubId) {
            return $this->getDefaultPermissions();
        }
        
        try {
            $response = $this->apiService->get("/permissions/club/{$clubId}");
            if (!empty($response['success']) && isset($response['data'])) {
                // Décodage de la réponse du backend: peut être { success: true, data: {...} }
                $payload = $response['data'];
                if (isset($payload['data']) && is_array($payload['data'])) {
                    return $payload['data'];
                }
                if (is_array($payload)) {
                    return $payload;
                }
            }
        } catch (\Exception $e) {
            error_log("Erreur lors de la récupération des permissions du club: " . $e->getMessage());
        }
        
        return $this->getDefaultPermissions();
    }
    
    /**
     * Retourne les permissions par défaut
     */
    public function getDefaultPermissions()
    {
        return [
            // Groupes
            'groups_view' => 'Archer',
            'groups_edit' => 'Coach',
            'groups_create' => 'Coach',
            'groups_delete' => 'Dirigeant',
            
            // Événements
            'events_view' => 'Archer',
            'events_edit' => 'Coach',
            'events_create' => 'Coach',
            'events_delete' => 'Dirigeant',
            
            // Utilisateurs - Vue
            'users_view' => 'Coach',
            'users_self_edit' => 'Archer',  // Modifier ses propres infos
            'users_edit' => 'Dirigeant',     // Modifier tous les utilisateurs
            'users_create' => 'Dirigeant',
            'users_delete' => 'Dirigeant',
            
            // Exercices
            'exercises_view' => 'Archer',
            'exercises_create' => 'Coach',
            'exercises_edit' => 'Coach',
            'exercises_delete' => 'Coach',
            
            // Progression d'entraînement
            'training_progress_view' => 'Archer',
            'training_progress_edit' => 'Coach',
            
            // Stats des autres archers
            'stats_other_view' => 'Coach',
            
            // Tir compté
            'scored_training_view' => 'Archer',
            'scored_training_create' => 'Archer',
            'scored_training_edit' => 'Archer',
            
            // Feuille de marque
            'score_sheet_view' => 'Archer',
            'score_sheet_create' => 'Archer',
            
            // Entraînements
            'trainings_view' => 'Archer',
            'trainings_create' => 'Coach',
            'trainings_edit' => 'Coach',
        ];
    }
    
    /**
     * Vérifie une permission spécifique
     */
    private function checkPermission($user, $resource, $action, $clubPermissions)
    {
        $permissionKey = $resource . '_' . $action;
        
        // Cas spécial : modification de ses propres informations
        if ($resource === self::RESOURCE_USERS_SELF && $action === self::ACTION_EDIT) {
            $permissionKey = 'users_self_edit';
        }
        
        // Cas spécial : modification de tous les utilisateurs
        if ($resource === self::RESOURCE_USERS_ALL && $action === self::ACTION_EDIT) {
            $permissionKey = 'users_edit';
        }
        
        $requiredRole = $clubPermissions[$permissionKey] ?? null;
        
        if ($requiredRole === null) {
            // Si la permission n'est pas définie, autoriser par défaut pour les Coaches et plus
            return $this->hasRoleLevel($user, 'Coach');
        }
        
        return $this->hasRoleLevel($user, $requiredRole);
    }
    
    /**
     * Met à jour les permissions d'un club
     */
    public function updateClubPermissions($clubId, $permissions, $user)
    {
        // Vérifier que l'utilisateur est Dirigeant du club ou Admin
        if (!$this->isAdmin($user) && !$this->hasRoleLevel($user, 'Dirigeant')) {
            return [
                'success' => false,
                'message' => 'Vous devez être Dirigeant ou Admin pour modifier les permissions'
            ];
        }
        
        if (!$this->isAdmin($user) && !$this->belongsToClub($user, $clubId)) {
            return [
                'success' => false,
                'message' => 'Vous ne pouvez modifier que les permissions de votre club'
            ];
        }
        
        try {
            $response = $this->apiService->put("/permissions/club/{$clubId}", [
                'permissions' => $permissions
            ]);
            
            return $response;
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de la mise à jour des permissions: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Délègue la décision de permission au backend PHP Permissions API.
     * Retourne bool ou null (si indisponible / erreur).
     */
    private function checkPermissionViaApi($user, $resource, $action, $clubId)
    {
        try {
            $payload = [
                'userId' => $user['id'] ?? null,
                'role' => $user['role'] ?? null,
                'isAdmin' => $user['is_admin'] ?? false,
                'clubId' => $clubId ?? ($user['clubId'] ?? null),
                'resource' => $resource,
                'action' => $action,
            ];

            $response = $this->apiService->post('/permissions/check', $payload);

            if (!empty($response['success']) && array_key_exists('allowed', $response)) {
                return (bool) $response['allowed'];
            }

            if (!empty($response['success']) && isset($response['data']['allowed'])) {
                return (bool) $response['data']['allowed'];
            }
        } catch (\Exception $e) {
            error_log('PermissionService checkPermissionViaApi error: ' . $e->getMessage());
        }

        return null; // fallback to local rules
    }
    
    /**
     * Retourne la liste des ressources configurables avec leurs actions
     */
    public function getConfigurablePermissions()
    {
        return [
            'groups' => [
                'label' => 'Groupes',
                'actions' => [
                    'view' => 'Consulter',
                    'edit' => 'Modifier',
                    'create' => 'Créer',
                    'delete' => 'Supprimer'
                ]
            ],
            'events' => [
                'label' => 'Événements',
                'actions' => [
                    'view' => 'Consulter',
                    'edit' => 'Modifier',
                    'create' => 'Créer',
                    'delete' => 'Supprimer'
                ]
            ],
            'users' => [
                'label' => 'Utilisateurs',
                'actions' => [
                    'view' => 'Consulter',
                    'self_edit' => 'Modifier ses propres informations',
                    'edit' => 'Modifier tous les utilisateurs',
                    'create' => 'Créer',
                    'delete' => 'Supprimer'
                ]
            ],
            'exercises' => [
                'label' => 'Exercices',
                'actions' => [
                    'view' => 'Consulter',
                    'create' => 'Créer',
                    'edit' => 'Modifier',
                    'delete' => 'Supprimer'
                ]
            ],
            'training_progress' => [
                'label' => 'Progression d\'entraînement',
                'actions' => [
                    'view' => 'Consulter',
                    'edit' => 'Modifier'
                ]
            ],
            'stats_other' => [
                'label' => 'Stats des autres archers',
                'actions' => [
                    'view' => 'Consulter'
                ]
            ],
            'scored_training' => [
                'label' => 'Tir compté / Chronomètre',
                'actions' => [
                    'view' => 'Consulter',
                    'create' => 'Créer',
                    'edit' => 'Modifier'
                ]
            ],
            'score_sheet' => [
                'label' => 'Feuille de marque',
                'actions' => [
                    'view' => 'Consulter',
                    'create' => 'Créer'
                ]
            ],
            'trainings' => [
                'label' => 'Carnet d\'entraînement',
                'actions' => [
                    'view' => 'Consulter',
                    'create' => 'Créer',
                    'edit' => 'Modifier'
                ]
            ]
        ];
    }
    
    /**
     * Retourne les rôles disponibles
     */
    public function getAvailableRoles()
    {
        return [
            'Archer' => 'Archer',
            'Coach' => 'Coach',
            'Dirigeant' => 'Dirigeant'
        ];
    }
}
