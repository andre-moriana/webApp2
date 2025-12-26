<?php
require_once 'app/Services/PermissionService.php';

/**
 * Helper pour la gestion des permissions
 */
class PermissionHelper
{
    private static $permissionService = null;
    
    /**
     * Obtenir l'instance du service de permissions
     */
    private static function getService()
    {
        if (self::$permissionService === null) {
            self::$permissionService = new PermissionService();
        }
        return self::$permissionService;
    }
    
    /**
     * Vérifie si l'utilisateur a la permission et redirige sinon
     */
    public static function requirePermission($resource, $action, $clubId = null, $redirectUrl = '/dashboard')
    {
        if (!isset($_SESSION['user'])) {
            $_SESSION['error'] = 'Vous devez être connecté pour accéder à cette page.';
            header('Location: /login');
            exit;
        }
        
        $user = $_SESSION['user'];
        $service = self::getService();
        
        if (!$service->hasPermission($user, $resource, $action, $clubId)) {
            $_SESSION['error'] = 'Vous n\'avez pas les permissions nécessaires pour effectuer cette action.';
            header('Location: ' . $redirectUrl);
            exit;
        }
        
        return true;
    }
    
    /**
     * Vérifie si l'utilisateur a la permission (retourne boolean)
     */
    public static function can($resource, $action, $clubId = null)
    {
        if (!isset($_SESSION['user'])) {
            return false;
        }
        
        $user = $_SESSION['user'];
        $service = self::getService();
        
        return $service->hasPermission($user, $resource, $action, $clubId);
    }
    
    /**
     * Vérifie si l'utilisateur est admin
     */
    public static function isAdmin()
    {
        if (!isset($_SESSION['user'])) {
            return false;
        }
        
        $service = self::getService();
        return $service->isAdmin($_SESSION['user']);
    }
    
    /**
     * Vérifie si l'utilisateur a au moins le niveau de rôle requis
     */
    public static function hasRole($requiredRole)
    {
        if (!isset($_SESSION['user'])) {
            return false;
        }
        
        $service = self::getService();
        return $service->hasRoleLevel($_SESSION['user'], $requiredRole);
    }
    
    /**
     * Vérifie si l'utilisateur appartient au club
     */
    public static function belongsToClub($clubId)
    {
        if (!isset($_SESSION['user'])) {
            return false;
        }
        
        $service = self::getService();
        return $service->belongsToClub($_SESSION['user'], $clubId);
    }
    
    /**
     * Vérifie si l'utilisateur peut voir les infos d'un autre utilisateur
     */
    public static function canViewUser($targetUserId, $clubId = null)
    {
        if (!isset($_SESSION['user'])) {
            return false;
        }
        
        $user = $_SESSION['user'];
        
        // Peut toujours voir ses propres infos
        if ($user['id'] == $targetUserId) {
            return true;
        }
        
        // Admin peut tout voir
        if (self::isAdmin()) {
            return true;
        }
        
        // Vérifier la permission de voir les autres utilisateurs
        return self::can(PermissionService::RESOURCE_USERS, PermissionService::ACTION_VIEW, $clubId);
    }
    
    /**
     * Vérifie si l'utilisateur peut modifier un autre utilisateur
     */
    public static function canEditUser($targetUserId, $clubId = null)
    {
        if (!isset($_SESSION['user'])) {
            return false;
        }
        
        $user = $_SESSION['user'];
        
        // Peut toujours modifier ses propres infos (avec permission users_self)
        if ($user['id'] == $targetUserId) {
            return self::can(PermissionService::RESOURCE_USERS_SELF, PermissionService::ACTION_EDIT, $clubId);
        }
        
        // Admin peut tout modifier
        if (self::isAdmin()) {
            return true;
        }
        
        // Vérifier la permission de modifier tous les utilisateurs
        return self::can(PermissionService::RESOURCE_USERS_ALL, PermissionService::ACTION_EDIT, $clubId);
    }
    
    /**
     * Rendu conditionnel basé sur les permissions
     */
    public static function ifCan($resource, $action, $clubId = null, $callback)
    {
        if (self::can($resource, $action, $clubId)) {
            $callback();
        }
    }
}
