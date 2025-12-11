<?php

class PrivacyController {
    
    public function index() {
        // Cette page est publique, pas besoin de vérifier l'authentification
        $title = 'Protection des données personnelles - Portail Archers de Gémenos';
        
        // Définir $pageTitle pour le header
        $pageTitle = $title;
        
        // Utiliser un header simplifié pour les pages publiques
        include 'app/Views/layouts/header-public.php';
        include 'app/Views/privacy/index.php';
        include 'app/Views/layouts/footer.php';
    }
}

