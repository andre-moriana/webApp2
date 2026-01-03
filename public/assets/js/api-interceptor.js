/**
 * Intercepteur API pour gérer les erreurs 401 (token expiré)
 * À inclure dans toutes les pages protégées
 */

(function() {
    'use strict';
    
    /**
     * Wrapper pour fetch qui gère automatiquement les erreurs 401
     */
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        return originalFetch.apply(this, args)
            .then(response => {
                // Si erreur 401, rediriger vers login
                if (response.status === 401) {
                    console.log('Session expirée (401), redirection vers login...');
                    
                    // Éviter les redirections multiples
                    if (!sessionStorage.getItem('redirectingToLogin')) {
                        sessionStorage.setItem('redirectingToLogin', 'true');
                        
                        // Nettoyer le stockage local
                        sessionStorage.clear();
                        
                        // Rediriger vers login avec message
                        window.location.replace('/login?expired=1');
                    }
                }
                return response;
            });
    };
    
    /**
     * Intercepteur pour XMLHttpRequest
     */
    const XHROpen = XMLHttpRequest.prototype.open;
    const XHRSend = XMLHttpRequest.prototype.send;
    
    XMLHttpRequest.prototype.open = function(method, url, ...rest) {
        this._url = url;
        return XHROpen.apply(this, [method, url, ...rest]);
    };
    
    XMLHttpRequest.prototype.send = function(...args) {
        this.addEventListener('load', function() {
            // Si erreur 401, rediriger vers login
            if (this.status === 401) {
                console.log('Session expirée (401), redirection vers login...');
                
                // Éviter les redirections multiples
                if (!sessionStorage.getItem('redirectingToLogin')) {
                    sessionStorage.setItem('redirectingToLogin', 'true');
                    
                    // Nettoyer le stockage local
                    sessionStorage.clear();
                    
                    // Rediriger vers login avec message
                    window.location.replace('/login?expired=1');
                }
            }
        });
        
        return XHRSend.apply(this, args);
    };
    
    /**
     * Vérifier l'état de la session au chargement de la page
     */
    function checkSessionOnLoad() {
        // Si on est sur une page protégée, vérifier que les données se chargent
        if (window.location.pathname !== '/login' && window.location.pathname !== '/register') {
            // Faire une requête de test vers le backend pour vérifier le token
            fetch('/api/auth/verify', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (response.status === 401) {
                    console.log('Token invalide au chargement, redirection vers login...');
                    
                    // Éviter les redirections multiples
                    if (!sessionStorage.getItem('redirectingToLogin')) {
                        sessionStorage.setItem('redirectingToLogin', 'true');
                        sessionStorage.clear();
                        window.location.replace('/login?expired=1');
                    }
                }
            })
            .catch(error => {
                console.error('Erreur lors de la vérification de session:', error);
            });
        }
    }
    
    // Nettoyer le flag de redirection au chargement
    if (window.location.pathname === '/login') {
        sessionStorage.removeItem('redirectingToLogin');
    }
    
    // Vérifier la session au chargement de la page
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', checkSessionOnLoad);
    } else {
        checkSessionOnLoad();
    }
})();
