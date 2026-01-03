<?php

class DebugSessionController {
    
    public function index() {
        // Ne pas vérifier SessionGuard pour cette page de debug
        // On veut voir l'état réel de la session
        
        // Démarrer la session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Inclure directement la vue de debug
        include __DIR__ . '/../../public/test-session-debug.php';
    }
}
