/**
 * Intercepteur API pour gérer les erreurs 401 (token expiré)
 * À inclure dans toutes les pages protégées
 */

(function() {
    'use strict';
    
    console.log('🔒 API Interceptor activé');
    
    /**
     * Wrapper pour fetch qui gère automatiquement les erreurs 401
     */
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        console.log('🌐 Fetch intercepté:', args[0]);
        return originalFetch.apply(this, args)
            .then(response => {
                console.log('📡 Réponse fetch:', args[0], 'Status:', response.status);
                
                // Si erreur 401, rediriger vers login
                if (response.status === 401) {
                    console.error('❌ Erreur 401 détectée sur:', args[0]);
                    console.log('🔄 Redirection vers login...');
                    
                    // Éviter les redirections multiples
                    if (!sessionStorage.getItem('redirectingToLogin')) {
                        sessionStorage.setItem('redirectingToLogin', 'true');
                        
                        // Nettoyer le stockage local
                        sessionStorage.clear();
                        
                        // Afficher un message avant redirection
                        alert('Votre session a expiré. Vous allez être redirigé vers la page de connexion.');
                        
                        // Rediriger vers login avec message
                        window.location.replace('/login?expired=1');
                    }
                }
                return response;
            })
            .catch(error => {
                console.error('❌ Erreur fetch:', args[0], error);
                throw error;
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
        console.log('🔍 Vérification de session au chargement...');
        console.log('📍 Page actuelle:', window.location.pathname);
        
        // Si on est sur une page protégée, vérifier que les données se chargent
        if (window.location.pathname !== '/login' && window.location.pathname !== '/register') {
            console.log('🔒 Page protégée détectée, vérification du token...');
            
            // Faire une requête de test vers le backend pour vérifier le token
            fetch('/api/auth/verify', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                console.log('✅ Réponse de /api/auth/verify:', response.status);
                
                if (response.status === 401) {
                    console.error('❌ Token invalide au chargement (401)');
                    console.log('🔄 Redirection immédiate vers login...');
                    
                    // Éviter les redirections multiples
                    if (!sessionStorage.getItem('redirectingToLogin')) {
                        sessionStorage.setItem('redirectingToLogin', 'true');
                        sessionStorage.clear();
                        
                        // Afficher un message
                        alert('Votre session a expiré. Vous devez vous reconnecter.');
                        
                        window.location.replace('/login?expired=1');
                    }
                } else if (response.ok) {
                    console.log('✅ Token valide');
                    return response.json();
                }
            })
            .then(data => {
                if (data) {
                    console.log('📊 Données session:', data);
                }
            })
            .catch(error => {
                console.error('❌ Erreur lors de la vérification de session:', error);
            });
        } else {
            console.log('📄 Page publique, pas de vérification nécessaire');
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
