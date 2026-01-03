/**
 * Gestionnaire de session pour maintenir l'activité pendant les saisies longues
 * et rediriger vers la page de login quand la session expire
 */

class SessionManager {
    constructor(options = {}) {
        // Interval de vérification (par défaut 5 minutes)
        this.checkInterval = options.checkInterval || 5 * 60 * 1000; // 5 minutes
        
        // Pages où le keep-alive doit être actif (saisie longue)
        this.keepAlivePages = options.keepAlivePages || [
            '/scored-trainings',
            '/score-sheet',
            '/trainings'
        ];
        
        // Vérifier si on est sur une page de saisie longue
        this.isLongFormPage = this.keepAlivePages.some(page => 
            window.location.pathname.includes(page)
        );
        
        this.intervalId = null;
        this.lastActivityTime = Date.now();
        this.isActive = false;
        
        // Événements qui indiquent une activité utilisateur
        this.activityEvents = ['mousedown', 'keydown', 'scroll', 'touchstart'];
        
        this.init();
    }
    
    init() {
        // Écouter les événements d'activité utilisateur
        this.activityEvents.forEach(event => {
            document.addEventListener(event, () => this.updateActivity(), { passive: true });
        });
        
        // Démarrer le keep-alive si on est sur une page de saisie longue
        if (this.isLongFormPage) {
            console.log('[SessionManager] Page de saisie longue détectée, activation du keep-alive');
            this.start();
        } else {
            console.log('[SessionManager] Page normale, vérification périodique uniquement');
            this.startPeriodicCheck();
        }
    }
    
    updateActivity() {
        this.lastActivityTime = Date.now();
    }
    
    /**
     * Démarre le keep-alive pour maintenir la session active
     */
    start() {
        if (this.isActive) {
            console.log('[SessionManager] Keep-alive déjà actif');
            return;
        }
        
        this.isActive = true;
        console.log('[SessionManager] Démarrage du keep-alive');
        
        // Vérifier immédiatement
        this.checkSession();
        
        // Puis vérifier périodiquement
        this.intervalId = setInterval(() => this.checkSession(), this.checkInterval);
    }
    
    /**
     * Arrête le keep-alive
     */
    stop() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
            this.isActive = false;
            console.log('[SessionManager] Keep-alive arrêté');
        }
    }
    
    /**
     * Pour les pages normales, vérifier périodiquement la session sans la maintenir active
     */
    startPeriodicCheck() {
        // Vérifier toutes les 10 secondes si la session est toujours valide
        setInterval(() => this.checkSessionOnly(), 10 * 1000);
    }
    
    /**
     * Vérifie la session et la maintient active si nécessaire
     */
    async checkSession() {
        try {
            const response = await fetch('/keep-alive.php', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            
            if (!data.success || response.status === 401) {
                console.log('[SessionManager] Session expirée, redirection vers login');
                this.handleSessionExpired();
            } else {
                console.log('[SessionManager] Session active maintenue');
            }
        } catch (error) {
            console.error('[SessionManager] Erreur lors de la vérification de session:', error);
            // Ne pas rediriger sur une erreur réseau, seulement sur expiration confirmée
        }
    }
    
    /**
     * Vérifie uniquement la session sans la maintenir active
     */
    async checkSessionOnly() {
        try {
            const response = await fetch('/keep-alive.php', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Check-Only': 'true'
                }
            });
            
            if (response.status === 401) {
                console.log('[SessionManager] Session expirée détectée');
                this.handleSessionExpired();
            }
        } catch (error) {
            // Ignorer les erreurs réseau pour les vérifications passives
        }
    }
    
    /**
     * Gère l'expiration de la session
     */
    handleSessionExpired() {
        // Arrêter le keep-alive
        this.stop();
        
        // Sauvegarder l'URL actuelle pour rediriger après login (optionnel)
        const currentUrl = window.location.pathname + window.location.search;
        if (currentUrl !== '/login' && currentUrl !== '/logout') {
            sessionStorage.setItem('redirectAfterLogin', currentUrl);
        }
        
        // Marquer que la session a expiré pour éviter les multiples redirections
        if (sessionStorage.getItem('sessionExpired') === 'true') {
            return; // Déjà en cours de redirection
        }
        sessionStorage.setItem('sessionExpired', 'true');
        
        // Redirection immédiate vers la page de login
        console.log('[SessionManager] Redirection immédiate vers /login');
        window.location.replace('/login?expired=1');
    }
    
    /**
     * Active manuellement le keep-alive (utile pour les modals de saisie)
     */
    enableKeepAlive() {
        if (!this.isActive) {
            console.log('[SessionManager] Activation manuelle du keep-alive');
            this.isLongFormPage = true;
            this.start();
        }
    }
    
    /**
     * Désactive manuellement le keep-alive
     */
    disableKeepAlive() {
        if (this.isActive && !this.keepAlivePages.some(page => window.location.pathname.includes(page))) {
            console.log('[SessionManager] Désactivation manuelle du keep-alive');
            this.isLongFormPage = false;
            this.stop();
            this.startPeriodicCheck();
        }
    }
}

// Initialiser automatiquement le gestionnaire de session
let sessionManager;

document.addEventListener('DOMContentLoaded', function() {
    sessionManager = new SessionManager({
        checkInterval: 5 * 60 * 1000, // 5 minutes
        keepAlivePages: [
            '/scored-trainings',
            '/score-sheet',
            '/trainings'
        ]
    });
    
    // Exposer l'instance globalement pour un contrôle manuel si nécessaire
    window.sessionManager = sessionManager;
});

// Export pour utilisation en tant que module si nécessaire
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SessionManager;
}
